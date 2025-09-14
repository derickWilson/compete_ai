<?php
/**
 * Arquivo: editar_inscricao.php
 * Descrição: Processa edição e exclusão de inscrições com tratamento adequado de cobranças no Asaas
 */

declare(strict_types=1);

session_start();

// Verificação de autenticação
if (!isset($_SESSION["logado"]) || !$_SESSION["logado"]) {
    header("Location: index.php");
    exit();
}

// Carrega dependências
try {
    require_once "classes/atletaService.php";
    require_once "classes/eventosServices.php";
    require_once "classes/AssasService.php";
    require_once "func/clearWord.php";
    require_once __DIR__ . '/config_taxa.php';
} catch (Throwable $th) {
    error_log("Erro ao carregar dependências: " . $th->getMessage());
    $_SESSION['erro'] = "Erro ao carregar recursos do sistema";
    header("Location: eventos_cadastrados.php");
    exit();
}

// Validação dos dados do formulário
if (!isset($_POST["evento_id"]) || !isset($_POST["modalidade"])) {
    $_SESSION['erro'] = "Dados incompletos para processamento";
    header("Location: eventos_cadastrados.php");
    exit();
}

// Inicializa serviços
$conn = new Conexao();
$atserv = new atletaService($conn, new Atleta());
$eventoServ = new eventosService($conn, new Evento());
$assasService = new AssasService($conn);

// Sanitiza inputs
$eventoId = (int) cleanWords($_POST["evento_id"]);
$idAtleta = $_SESSION["id"];
$modalidade = cleanWords($_POST["modalidade"]);
$com = isset($_POST["com"]) ? 1 : 0;
$abCom = isset($_POST["abs_com"]) ? 1 : 0;
$sem = isset($_POST["sem"]) ? 1 : 0;
$abSem = isset($_POST["abs_sem"]) ? 1 : 0;

try {
    // Obtém dados atuais
    $dadosEvento = $eventoServ->getById($eventoId);
    $inscricao = $atserv->getInscricao($eventoId, $idAtleta);

    if (!$dadosEvento || !$inscricao) {
        throw new Exception("Registro não encontrado no sistema");
    }

    // Verifica se é uma solicitação de exclusão
    if (isset($_POST['action']) && $_POST['action'] === 'Excluir Inscrição') {
        handleDeletion($assasService, $atserv, $inscricao, $eventoId, $idAtleta);
    }

    // Processamento normal de edição
    handleUpdate($dadosEvento, $inscricao, $assasService, $atserv, $eventoId, $idAtleta, $com, $abCom, $sem, $abSem, $modalidade);

} catch (Exception $e) {
    error_log("Erro em editar_inscricao: " . $e->getMessage());
    $_SESSION['erro'] = $e->getMessage();
    header("Location: " . (isset($eventoId) ? "inscricao.php?inscricao=" . $eventoId : "eventos_cadastrados.php"));
    exit();
}

/**
 * Processa a exclusão de uma inscrição
 */
function handleDeletion(AssasService $assasService, atletaService $atserv, $inscricao, int $eventoId, int $idAtleta): void
{
    // Verifica status da cobrança se existir
    if (!empty($inscricao->id_cobranca_asaas)) {
        $status = $assasService->verificarStatusCobranca($inscricao->id_cobranca_asaas);

        // Bloqueia exclusão se pagamento já foi recebido
        if ($status['status'] === 'RECEIVED') {
            throw new Exception("Inscrição já paga não pode ser excluída");
        }

        // Tenta cancelar a cobrança no Asaas
        $resultado = $assasService->deletarCobranca($inscricao->id_cobranca_asaas);
        if (!$resultado['deleted']) {
            throw new Exception("Não foi possível cancelar a cobrança: " . ($resultado['message'] ?? ''));
        }
    }

    // Remove a inscrição do banco de dados
    if (!$atserv->excluirInscricao($eventoId, $idAtleta)) {
        throw new Exception("Falha ao remover inscrição do sistema");
    }

    $_SESSION['sucesso'] = "Inscrição excluída com sucesso";
    header("Location: eventos_cadastrados.php");
    exit();
}

/**
 * Processa atualização de uma inscrição
 */
function handleUpdate(
    $dadosEvento,
    $inscricao,
    AssasService $assasService,
    atletaService $atserv,
    int $eventoId,
    int $idAtleta,
    int $com,
    int $abCom,
    int $sem,
    int $abSem,
    string $modalidade
): void {
    // Verifica se é evento gratuito
    $eventoGratuito = ($dadosEvento->preco == 0 && $dadosEvento->preco_menor == 0 && $dadosEvento->preco_abs == 0);
    
    $valor = 0;
    if (!$eventoGratuito) {
        if ($dadosEvento->normal) {
            $valor = $dadosEvento->normal_preco * TAXA;
        } else {
            $valor = $abCom == 1 ? $dadosEvento->preco_abs : $dadosEvento->preco;
            $valor *= TAXA;
            // Validação de segurança
            if ($valor <= 0) {
                throw new Exception("Valor da inscrição inválido");
            }
        }
    }
    // Atualiza modalidades no banco de dados
    $atserv->editarInscricao($eventoId, $idAtleta, $com, $abCom, $sem, $abSem, $modalidade);

    // Para eventos pagos com cobrança existente
    if (!$eventoGratuito && !empty($inscricao->id_cobranca_asaas)) {
        $valorAtual = (float) $inscricao->valor_pago;
        $valorMudou = abs($valor - $valorAtual) > 0.01; // Considera diferenças > 1 centavo

        if ($valorMudou) {
            $resultado = $assasService->editarCobranca($inscricao->id_cobranca_asaas, [
                'value' => number_format($valor, 2, '.', ''),
                'dueDate' => $dadosEvento->data_limite,
                'description' => 'Inscrição: ' . $dadosEvento->nome . ' (' . $modalidade . ')'
            ]);

            if (!$resultado['success']) {
                throw new Exception("Erro ao atualizar cobrança: " . ($resultado['message'] ?? ''));
            }

            // Atualiza valor no banco de dados
            $atserv->atualizarValorInscricao($eventoId, $idAtleta, $valor);
        }
    }

    $_SESSION['sucesso'] = "Inscrição atualizada" . ($valorMudou ?? false ? " (valor ajustado)" : "");
    header("Location: inscricao.php?inscricao=" . $eventoId);
    exit();
}
?>