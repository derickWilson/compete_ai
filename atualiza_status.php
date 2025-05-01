<?php
session_start();
require_once "classes/AssasService.php";
require_once "func/database.php";

header('Content-Type: application/json');

// Verifica autenticação
if (!isset($_SESSION["logado"])) {
    echo json_encode(['error' => 'Não autorizado']);
    exit();
}

// Obtém ID da cobrança de forma segura
$cobrancaId = filter_input(INPUT_GET, 'cobranca_id', FILTER_SANITIZE_STRING);
if (empty($cobrancaId)) {
    echo json_encode(['error' => 'ID da cobrança não informado']);
    exit();
}

try {
    $conn = new Conexao();
    $asaasService = new AssasService($conn);
    
    // 1. Verifica o status da cobrança
    $statusInfo = $asaasService->verificarStatusCobranca($cobrancaId);
    $status = $statusInfo['status'];
    $statusTraduzido = $statusInfo['traduzido'];
    
    // 2. Se o pagamento foi confirmado, atualiza o banco de dados
    if (in_array($status, ['RECEIVED', 'CONFIRMED'])) {
        // Obtém os dados completos da cobrança para pegar o externalReference
        $response = $asaasService->buscarCobrancaCompleta($cobrancaId);
        
        if ($response['success']) {
            $cobranca = $response['payment'];
            $externalReference = $cobranca['externalReference'] ?? '';
            
            // Extrai os IDs do externalReference (formato esperado: EV_[id_evento]_AT_[id_atleta])
            if (preg_match('/EV_(\d+)_AT_(\d+)/', $externalReference, $matches)) {
                $idEvento = $matches[1];
                $idAtleta = $matches[2];
                
                // Atualiza a inscrição no banco de dados
                $asaasService->atualizarInscricaoComPagamento(
                    $idAtleta,
                    $idEvento,
                    $cobrancaId,
                    $statusTraduzido,
                    $cobranca['value']
                );
            } else {
                error_log("Formato inválido de externalReference: " . $externalReference);
            }
        }
    }
    
    // 3. Retorna o status atualizado
    echo json_encode([
        'status' => $status,
        'traduzido' => $statusTraduzido,
        'updated' => in_array($status, ['RECEIVED', 'CONFIRMED'])
    ]);
    
} catch (Exception $e) {
    error_log("Erro em atualiza_status.php: " . $e->getMessage());
    echo json_encode([
        'error' => 'Erro ao verificar status',
        'details' => $e->getMessage()
    ]);
}
?>