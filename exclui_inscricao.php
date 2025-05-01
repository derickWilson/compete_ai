<?php
session_start();
if (!isset($_SESSION["logado"]) || !$_SESSION["logado"]) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET["id"])) {
    $_SESSION['erro'] = "ID do evento não informado";
    header("Location: eventos_cadastrados.php");
    exit();
}

require_once "classes/atletaService.php";
require_once "classes/AssasService.php";
require_once "func/clearWord.php";
require_once "func/database.php";

try {
    $conn = new Conexao();
    $at = new Atleta();
    $atserv = new atletaService($conn, $at);
    $asaasService = new AssasService($conn);
} catch (\Throwable $th) {
    $_SESSION['erro'] = "Erro ao iniciar serviços: " . $th->getMessage();
    header("Location: eventos_cadastrados.php");
    exit();
}

// Pega dados do atleta e evento
$eventoId = (int) cleanWords($_GET["id"]);
$atletaId = $_SESSION["id"];

// Busca dados da inscrição
try {
    $inscricao = $atserv->getInscricao($eventoId, $atletaId);
    
    if (!$inscricao) {
        $_SESSION['erro'] = "Inscrição não encontrada";
        header("Location: eventos_cadastrados.php");
        exit();
    }

    // Se existir cobrança no Asaas, tenta deletar
    if (!empty($inscricao->id_cobranca_asaas)) {
        try {
            // Verifica status da cobrança antes de deletar
            $statusCobranca = $asaasService->verificarStatusCobranca($inscricao->id_cobranca_asaas);
            
            // Só deleta se o status for PENDING ou OVERDUE
            if (in_array($statusCobranca['status'], ['PENDING', 'OVERDUE'])) {
                $response = $asaasService->deletarCobranca($inscricao->id_cobranca_asaas);
                
                if (!$response['deleted']) {
                    error_log("Falha ao deletar cobrança no Asaas. ID: " . $inscricao->id_cobranca_asaas);
                }
            } else {
                error_log("Cobrança não pode ser deletada - Status: " . $statusCobranca['status']);
            }
        } catch (Exception $e) {
            error_log("Erro ao deletar cobrança no Asaas: " . $e->getMessage());
            // Continua o processo mesmo se falhar em deletar a cobrança
        }
    }

    // Exclui inscrição do banco de dados
    $atserv->excluirInscricao($eventoId, $atletaId);
} catch (Exception $e) {
    $_SESSION['erro'] = "Erro ao processar solicitação: " . $e->getMessage();
}

header("Location: eventos_cadastrados.php");
exit();
?>
