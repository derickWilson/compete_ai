<?php
declare(strict_types=1);

// Buffer de saída para evitar envio prematuro de headers
ob_start();

// Iniciar sessão ANTES de qualquer possível saída
session_start();

// Configuração de logs
ini_set('display_errors', 0);
file_put_contents('asaas_debug.log', "\n\n" . date('Y-m-d H:i:s') . " - Início da inscrição", FILE_APPEND);

// Verificar se headers já foram enviados
if (headers_sent($filename, $linenum)) {
    die("Erro crítico: Headers já enviados em $filename na linha $linenum");
}

// Verificações iniciais
if (!isset($_SESSION['logado'])) {
    $_SESSION['erro'] = "Acesso não autorizado";
    header("Location: login.php");
    ob_end_flush();
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['erro'] = "Método inválido";
    header("Location: eventos.php");
    ob_end_flush();
    exit();
}

// Incluir arquivos necessários
require_once __DIR__ . "/classes/eventosServices.php";
require_once __DIR__ . "/classes/AssasService.php";
require_once __DIR__ . "/func/clearWord.php";
require_once __DIR__ . "/config_taxa.php";
require_once __DIR__ . "/func/database.php";

// Verificar novamente se headers foram enviados após includes
if (headers_sent()) {
    die("Erro crítico: Headers enviados após includes");
}
try {
    $conn = new Conexao();
    $pdo = $conn->conectar();

    // Inicia transação
    $pdo->beginTransaction();

    $ev = new Evento();
    $evserv = new eventosService($conn, $ev);
    $asaasService = new AssasService($conn);

    // Validação do evento
    $evento_id = (int) cleanWords($_POST['evento_id']);
    $eventoDetails = $evserv->getById($evento_id);

    if (!$eventoDetails) {
        throw new Exception("Evento não encontrado");
    }

    // Processa modalidades - lógica diferente para eventos normais
    if ($eventoDetails->normal) {
        // Evento normal - não tem modalidades com/sem kimono
        $modalidades = [
            'com' => 0,
            'sem' => 0,
            'abs_com' => 0,
            'abs_sem' => 0
        ];
    } else {
        // Evento tradicional - processa modalidades normais
        $modalidades = [
            'com' => isset($_POST['com']) ? 1 : 0,
            'sem' => isset($_POST['sem']) ? 1 : 0,
            'abs_com' => isset($_POST['abs_com']) ? 1 : 0,
            'abs_sem' => isset($_POST['abs_sem']) ? 1 : 0
        ];
    }

    $modalidade_escolhida = cleanWords($_POST['modalidade']);

    // Verifica se o evento é gratuito
    $eventoGratuito = ($eventoDetails->normal)
        ? ($eventoDetails->normal_preco == 0)
        : ($eventoDetails->preco == 0 && $eventoDetails->preco_menor == 0 &&
            $eventoDetails->preco_abs == 0 && $eventoDetails->preco_sem == 0 &&
            $eventoDetails->preco_sem_menor == 0 && $eventoDetails->preco_sem_abs == 0);

    // Cálculo do valor com taxa
    $valor = 0;
    if (!$eventoGratuito) {
        if ($eventoDetails->normal) {
            $valor = $eventoDetails->normal_preco * TAXA;
        } else {
            // Prepara dados para a função de cálculo
            $dadosInscricao = (object) [
                'data_nascimento' => $_SESSION['data_nascimento'],
                'mod_com' => $modalidades['com'],
                'mod_sem' => $modalidades['sem'],
                'mod_ab_com' => $modalidades['abs_com'],
                'mod_ab_sem' => $modalidades['abs_sem']
            ];

            $dadosEventoArray = [
                'normal' => $eventoDetails->normal,
                'normal_preco' => $eventoDetails->normal_preco,
                'preco' => $eventoDetails->preco,
                'preco_menor' => $eventoDetails->preco_menor,
                'preco_abs' => $eventoDetails->preco_abs,
                'preco_sem' => $eventoDetails->preco_sem,
                'preco_sem_menor' => $eventoDetails->preco_sem_menor,
                'preco_sem_abs' => $eventoDetails->preco_sem_abs
            ];

            $valor = calcularNovoValor($dadosInscricao, $dadosEventoArray);

            // Validação de segurança
            if ($valor <= 0) {
                throw new Exception("Valor da inscrição inválido");
            }
        }
    }

    // Validação dos dados da sessão
    $requiredSession = ['id', 'nome', 'cpf', 'email', 'fone'];
    foreach ($requiredSession as $field) {
        if (empty($_SESSION[$field])) {
            throw new Exception("Dados incompletos na sessão - Campo $field faltando");
        }
    }

    // Verifica termos aceitos
    $aceite_regulamento = isset($_POST['aceite_regulamento']) ? 1 : 0;
    $aceite_responsabilidade = isset($_POST['aceite_responsabilidade']) ? 1 : 0;

    if (!$aceite_regulamento || !$aceite_responsabilidade) {
        throw new Exception("Você deve aceitar todos os termos para se inscrever");
    }

    // 1. Inscreve no banco de dados local
    $inscricaoSucesso = $evserv->inscrever(
        $_SESSION['id'],
        $evento_id,
        $modalidades['com'],
        $modalidades['abs_com'],
        $modalidades['sem'],
        $modalidades['abs_sem'],
        $modalidade_escolhida,
        $aceite_regulamento,
        $aceite_responsabilidade
    );

    if ($inscricaoSucesso === false) {
        throw new Exception("Falha ao registrar inscrição no banco local");
    }

    // 2. Processamento diferente para eventos gratuitos
    if ($eventoGratuito) {
        // Para eventos gratuitos, marca como CONFIRMADO sem criar cobrança
        $atualizacao = $asaasService->atualizarInscricaoComPagamento(
            $_SESSION['id'],
            $evento_id,
            null,
            AssasService::STATUS_GRATUITO,
            0
        );

        if (!$atualizacao) {
            throw new Exception("Falha ao atualizar inscrição gratuita");
        }

        file_put_contents(
            'asaas_debug.log',
            "\nInscrição gratuita confirmada - Evento: $evento_id, Atleta: " . $_SESSION['id'],
            FILE_APPEND
        );
    } else {
        // Para eventos pagos, fluxo normal com Asaas
        $dadosAtleta = [
            'id' => $_SESSION['id'],
            'nome' => $_SESSION['nome'],
            'cpf' => $_SESSION['cpf'],
            'email' => $_SESSION['email'],
            'fone' => $_SESSION['fone'],
            'academia' => $_SESSION['academia'] ?? null
        ];

        try {
            $customerId = $asaasService->buscarOuCriarCliente($dadosAtleta);
            file_put_contents('asaas_debug.log', "\nCustomer ID: $customerId", FILE_APPEND);

            $descricao = "Inscrição: " . $eventoDetails->nome . " (" . $modalidade_escolhida . ")";
            $cobranca = $asaasService->criarCobranca([
                'customer' => $customerId,
                'value' => $valor,
                'dueDate' => $eventoDetails->data_limite,
                'description' => $descricao,
                'externalReference' => 'EV_' . $evento_id . '_AT_' . $_SESSION['id'],
                'billingType' => 'PIX'
            ]);

            // Atualiza inscrição com dados do pagamento
            $valorNumerico = (float) number_format($valor, 2, '.', '');
            $atualizacao = $asaasService->atualizarInscricaoComPagamento(
                $_SESSION['id'],
                $evento_id,
                $cobranca['payment']['id'],
                AssasService::STATUS_PENDENTE,
                $valorNumerico
            );

            if (!$atualizacao) {
                throw new Exception("Falha ao atualizar dados de pagamento");
            }
        } catch (Exception $e) {
            // Em caso de erro, desfaz a transação (remove a inscrição)
            $pdo->rollBack();

            // Remove a inscrição manualmente se o rollback não foi suficiente
            try {
                $atletaService = new atletaService($conn, new Atleta());
                $atletaService->excluirInscricao($evento_id, $_SESSION['id']);
            } catch (Exception $ex) {
                file_put_contents(
                    'asaas_error.log',
                    "\nERRO AO REMOVER INSCRIÇÃO: " . date('Y-m-d H:i:s') .
                    "\nMensagem: " . $ex->getMessage() . "\n",
                    FILE_APPEND
                );
            }

            throw $e;
        }
    }

    // Se tudo ocorreu bem, confirma a transação
    $pdo->commit();

    ob_clean();
    header("Location: eventos_cadastrados.php");
    ob_end_flush();
    exit();

} catch (Exception $e) {
    file_put_contents(
        'asaas_error.log',
        "\nERRO: " . date('Y-m-d H:i:s') .
        "\nMensagem: " . $e->getMessage() .
        "\nArquivo: " . $e->getFile() .
        "\nLinha: " . $e->getLine() .
        "\nTrace: " . $e->getTraceAsString() . "\n",
        FILE_APPEND
    );

    $_SESSION['erro_inscricao'] = "Erro na inscrição: " . $e->getMessage();
    header("Location: evento_detalhes.php?id=" . $evento_id);
    ob_end_flush();
    exit();
}

function calcularNovoValor($inscricao, $dadosEvento)
{
    // Se for evento normal
    if ($dadosEvento['normal']) {
        return $dadosEvento['normal_preco'] * TAXA;
    }

    // Se for evento gratuito
    if (
        $dadosEvento['preco'] == 0 && $dadosEvento['preco_menor'] == 0 &&
        $dadosEvento['preco_abs'] == 0 && $dadosEvento['preco_sem'] == 0 &&
        $dadosEvento['preco_sem_menor'] == 0 && $dadosEvento['preco_sem_abs'] == 0
    ) {
        return 0;
    }

    $idade = calcularIdade($inscricao->data_nascimento);
    $menorIdade = ($idade <= 15);

    $valorTotal = 0;
    $modalidadesSelecionadas = 0;

    // Modalidade COM kimono
    if ($inscricao->mod_com) {
        $precoCom = $menorIdade ? $dadosEvento['preco_menor'] : $dadosEvento['preco'];
        $valorTotal += $precoCom;
        $modalidadesSelecionadas++;
    }

    // Modalidade SEM kimono
    if ($inscricao->mod_sem) {
        $precoSem = $menorIdade ? $dadosEvento['preco_sem_menor'] : $dadosEvento['preco_sem'];
        $valorTotal += $precoSem;
        $modalidadesSelecionadas++;
    }

    // Absoluto COM kimono
    if ($inscricao->mod_ab_com) {
        $valorTotal += $dadosEvento['preco_abs'];
        $modalidadesSelecionadas++;
    }

    // Absoluto SEM kimono
    if ($inscricao->mod_ab_sem) {
        $valorTotal += $dadosEvento['preco_sem_abs'];
        $modalidadesSelecionadas++;
    }

    // Aplica desconto de 40% se fez mais de uma modalidade
    if ($modalidadesSelecionadas > 2) {
        $valorTotal *= 0.6; // 40% de desconto
    }

    // Aplica a taxa
    $valorTotal *= TAXA;

    // Validação de segurança
    if ($valorTotal <= 0) {
        error_log("Valor calculado inválido para inscrição {$inscricao->id}: $valorTotal");
        return $inscricao->valor_pago; // Mantém o valor original em caso de erro
    }

    return $valorTotal;
}
?>