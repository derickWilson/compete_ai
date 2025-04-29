<?php
session_start();
// Verificação de autenticação
if (!isset($_SESSION['logado']) || !$_SESSION['logado']) {
    header("Location: login.php");
    exit();
}

require_once "classes/conexao.php";
require_once "classes/eventosServices.php";
require_once "classes/AsaasService.php";
require_once "classes/atletaService.php";

try {
    // Validação dos parâmetros
    if (!isset($_GET['evento']) || !isset($_GET['inscricao'])) {
        throw new Exception("Parâmetros inválidos para pagamento");
    }

    $eventoId = (int)$_GET['evento'];
    $inscricaoId = $_GET['inscricao'];

    // Configura serviços
    $conn = new Conexao();
    $att = new Atleta();
    $ev = new Evento();
    $asaasService = new AsaasService($conn);
    $eventoService = new eventosService($conn, $ev);
    $atletaService = new atletaService($conn, $att);

    // Obtém dados da inscrição
    $inscricao = $atletaService->getInscricao($eventoId, $_SESSION['id']);
    if (!$inscricao) {
        throw new Exception("Inscrição não encontrada");
    }

    // Se já estiver pago, redireciona
    if ($inscricao->status_pagamento === 'RECEIVED') {
        $_SESSION['mensagem'] = "Esta inscrição já foi paga";
        header("Location: eventos_cadastrados.php");
        exit();
    }

    // Obtém dados da cobrança no Asaas
    $cobranca = $asaasService->buscarCobranca($inscricao->id_cobranca_asaas);
    if (!$cobranca) {
        throw new Exception("Cobrança não encontrada");
    }

    // Obtém detalhes do evento
    $evento = $eventoService->getById($eventoId);

} catch (Exception $e) {
    $_SESSION['erro'] = $e->getMessage();
    header("Location: eventos_cadastrados.php");
    exit();
}

// Inclui o cabeçalho
include "menu/add_menu.php";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento da Inscrição</title>
    <link rel="stylesheet" href="assets/css/estilo.css">
    <style>
        .container-pagamento {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .payment-info {
            margin: 20px 0;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .btn-pagar {
            display: inline-block;
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 5px;
        }
        .btn-voltar {
            background-color: #6c757d;
        }
        .status-pendente {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-pagamento">
        <h2>Pagamento da Inscrição</h2>
        
        <div class="status-pendente">
            <h3>Pagamento Pendente</h3>
            <p>Evento: <strong><?= htmlspecialchars($evento->nome) ?></strong></p>
            <p>Valor: <strong>R$ <?= number_format($inscricao->valor_pago, 2, ',', '.') ?></strong></p>
        </div>

        <div class="payment-info">
            <h3>Como pagar:</h3>
            <p>1. Clique no botão "Pagar Agora" abaixo</p>
            <p>2. Você será redirecionado para o sistema de pagamentos</p>
            <p>3. Siga as instruções para completar o pagamento</p>
            <p>4. Após o pagamento, seu status será atualizado automaticamente</p>
        </div>

        <div class="text-center">
            <a href="processar_pagamento.php?evento=<?= $eventoId ?>&inscricao=<?= $inscricaoId ?>" class="btn-pagar">
                Pagar Agora
            </a>
            <a href="eventos_cadastrados.php" class="btn-pagar btn-voltar">
                Voltar
            </a>
        </div>
    </div>
    <?php include "menu/footer.php"; ?>
</body>
</html>