<?php
session_start();

if (!isset($_SESSION['logado']) || !$_SESSION['logado']) {
    header("Location: login.php");
    exit();
}

require_once "classes/conexao.php";
require_once "classes/AsaasService.php";
require_once "classes/atletaService.php";

try {
    $eventoId = (int)$_GET['evento'];
    $inscricaoId = $_GET['inscricao'];
    $atletaId = $_SESSION['id'];

    $conn = new Conexao();
    $asaasService = new AsaasService($conn);
    $atletaService = new atletaService($conn, new Atleta());

    // Busca dados da inscrição
    $inscricao = $atletaService->getInscricao($eventoId, $atletaId);
    if (!$inscricao) {
        throw new Exception("Inscrição não encontrada");
    }

    // Se já tem cobrança criada, redireciona para o link de pagamento
    if (!empty($inscricao->id_cobranca_asaas)) {
        $cobranca = $asaasService->buscarCobranca($inscricao->id_cobranca_asaas);
        if ($cobranca && isset($cobranca['invoiceUrl'])) {
            header("Location: " . $cobranca['invoiceUrl']);
            exit();
        }
    }

    // Cria nova cobrança se não existir
    $valor = $inscricao->valor_pago;
    $descricao = "Inscrição no evento ID: " . $eventoId;
    
    $cobranca = $asaasService->criarCobranca([
        'customer' => $_SESSION['id'], // Ou outro identificador do atleta
        'value' => $valor,
        'description' => $descricao,
        'billingType' => 'BOLETO' // Ou outro método de pagamento
    ]);

    // Atualiza a inscrição com o ID da cobrança
    $asaasService->atualizarInscricaoComPagamento(
        $atletaId,
        $eventoId,
        $cobranca['id'],
        'PENDING',
        $valor
    );

    // Redireciona para o link de pagamento
    if (isset($cobranca['invoiceUrl'])) {
        header("Location: " . $cobranca['invoiceUrl']);
        exit();
    } else {
        throw new Exception("Não foi possível gerar o link de pagamento");
    }

} catch (Exception $e) {
    $_SESSION['erro'] = "Erro ao processar pagamento: " . $e->getMessage();
    header("Location: eventos_cadastrados.php");
    exit();
}
?>