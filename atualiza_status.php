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

// Obtém ID da cobrança
$cobrancaId = $_GET['cobranca_id'] ?? null;
if (empty($cobrancaId)) {
    echo json_encode(['error' => 'ID da cobrança não informado']);
    exit();
}

try {
    $conn = new Conexao();
    $asaasService = new AssasService($conn);
    
    $status = $asaasService->verificarStatusCobranca($cobrancaId);
    
    echo json_encode([
        'status' => $status['status'],
        'traduzido' => $status['traduzido']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>