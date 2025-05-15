<?php
declare(strict_types=1);

// Output buffering to prevent premature headers
ob_start();

// Start session before any output
session_start();

// Error handling configuration
ini_set('display_errors', 0);
file_put_contents('asaas_debug.log', "\n\n" . date('Y-m-d H:i:s') . " - Início da inscrição", FILE_APPEND);

// Check if headers were already sent
if (headers_sent($filename, $linenum)) {
    die("Erro crítico: Headers já enviados em $filename na linha $linenum");
}

// Authentication check
if (!isset($_SESSION['logado'])) {
    $_SESSION['erro'] = "Acesso não autorizado";
    header("Location: login.php");
    ob_end_flush();
    exit();
}

// Method validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['erro'] = "Método inválido";
    header("Location: eventos.php");
    ob_end_flush();
    exit();
}

// Include required files
require_once __DIR__ . "/classes/eventosServices.php";
require_once __DIR__ . "/classes/AssasService.php";
require_once __DIR__ . "/func/clearWord.php";
require_once __DIR__ . "/config_taxa.php";
require_once __DIR__ . "/func/database.php";

// Double check headers after includes
if (headers_sent()) {
    die("Erro crítico: Headers enviados após includes");
}

// Initialize services
try {
    $conn = new Conexao();
    $pdo = $conn->conectar();
    $pdo->beginTransaction();

    $ev = new Evento();
    $evserv = new eventosService($conn, $ev);
    $asaasService = new AssasService($conn);

    // Validate event
    $evento_id = (int) cleanWords($_POST['evento_id']);
    $eventoDetails = $evserv->getById($evento_id);

    if (!$eventoDetails) {
        throw new Exception("Evento não encontrado");
    }

    // Process modalities
    $modalidades = [
        'com' => isset($_POST['com']) ? 1 : 0,
        'sem' => isset($_POST['sem']) ? 1 : 0,
        'abs_com' => isset($_POST['abs_com']) ? 1 : 0,
        'abs_sem' => isset($_POST['abs_sem']) ? 1 : 0
    ];

    $modalidade_escolhida = cleanWords($_POST['modalidade']);

    // Check if event is free
    $eventoGratuito = ($eventoDetails->preco == 0 && $eventoDetails->preco_menor == 0 && $eventoDetails->preco_abs == 0);

    // Calculate price with tax (only for paid events)
    $valor = 0;
    if (!$eventoGratuito) {
        // Regular event pricing
        if ($modalidades['com'] || $modalidades['sem']) {
            $valor = ($_SESSION['idade'] > 15)
                ? $eventoDetails->preco * $taxa
                : $eventoDetails->preco_menor * $taxa;
        }
        // Absolute event pricing
        elseif (($modalidades['abs_com'] || $modalidades['abs_sem']) && $eventoDetails->preco_abs > 0) {
            $valor = $eventoDetails->preco_abs * $taxa;
        } else {
            throw new Exception("Modalidade inválida selecionada");
        }
    }

    // Validate session data
    $requiredSession = ['id', 'nome', 'cpf', 'email', 'fone'];
    foreach ($requiredSession as $field) {
        if (empty($_SESSION[$field])) {
            throw new Exception("Dados incompletos na sessão - Campo $field faltando");
        }
    }

    // Check terms acceptance
    $aceite_regulamento = isset($_POST['aceite_regulamento']) ? 1 : 0;
    $aceite_responsabilidade = isset($_POST['aceite_responsabilidade']) ? 1 : 0;

    if (!$aceite_regulamento || !$aceite_responsabilidade) {
        throw new Exception("Você deve aceitar todos os termos para se inscrever");
    }

    // Register local subscription
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

    // Handle free events differently
    if ($eventoGratuito) {
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
        // Paid event flow with Asaas
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
            $pdo->rollBack();

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
?>