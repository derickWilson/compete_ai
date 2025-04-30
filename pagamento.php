<?php
session_start();
require_once "classes/AssasService.php";
require_once "func/database.php";

// Verifica autenticação
if (!isset($_SESSION["logado"])) {
    header("Location: index.php");
    exit();
}

// Obtém ID da cobrança
$cobrancaId = $_GET['cobranca_id'] ?? null;
if (empty($cobrancaId)) {
    die("ID da cobrança não informado");
}

try {
    $conn = new Conexao();
    $asaasService = new AssasService($conn);
    
    // 1. Verifica status atual
    $status = $asaasService->verificarStatusCobranca($cobrancaId);
    
    // Se já estiver pago, redireciona para o comprovante
    if (in_array($status['status'], ['RECEIVED', 'CONFIRMED'])) {
        header("Location: comprovante.php?id=" . $cobrancaId);
        exit();
    }
    
    // 2. Busca dados completos da cobrança
    $cobranca = $asaasService->buscarCobrancaCompleta($cobrancaId);
    
    if (!$cobranca['success']) {
        throw new Exception("Erro ao buscar cobrança");
    }
    
    $dadosCobranca = $cobranca['payment'];
    
} catch (Exception $e) {
    die("Erro ao processar pagamento: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento - <?= htmlspecialchars($dadosCobranca['description']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .payment-info {
            margin-bottom: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        .pix-container {
            text-align: center;
            margin: 20px 0;
        }
        .pix-qrcode {
            max-width: 300px;
            margin: 0 auto;
        }
        .pix-code {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            word-break: break-all;
            margin: 15px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 5px;
        }
        .btn-copy {
            background: #2ecc71;
        }
        .btn-print {
            background: #e67e22;
        }
        .btn-back {
            background: #95a5a6;
        }
        .expiration {
            color: #e74c3c;
            font-weight: bold;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            color: white;
            font-weight: bold;
            display: inline-block;
        }
        .status-pending {
            background: #f39c12;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Pagamento via PIX</h1>
        
        <div class="payment-info">
            <h2><?= htmlspecialchars($dadosCobranca['description']) ?></h2>
            <p><strong>Valor:</strong> R$ <?= number_format($dadosCobranca['value'], 2, ',', '.') ?></p>
            <p><strong>Status:</strong> <span class="status-badge status-pending"><?= $status['traduzido'] ?></span></p>
            <p><strong>Vencimento:</strong> <?= date('d/m/Y', strtotime($dadosCobranca['dueDate'])) ?></p>
        </div>
        
        <?php if ($dadosCobranca['pix']): ?>
        <div class="pix-container">
            <h3>Pague com PIX</h3>
            <div class="pix-qrcode">
                <img src="<?= $dadosCobranca['pix']['encodedImage'] ?>" alt="QR Code PIX" style="max-width:100%;">
            </div>
            
            <p class="expiration">Validade: <?= date('d/m/Y H:i', strtotime($dadosCobranca['pix']['expirationDate'])) ?></p>
            
            <div class="pix-code">
                <p><strong>Código PIX (copie e cole no seu app):</strong></p>
                <p id="pix-code"><?= $dadosCobranca['pix']['payload'] ?></p>
            </div>
            
            <button onclick="copyPixCode()" class="btn btn-copy">Copiar Código</button>
            <button onclick="window.print()" class="btn btn-print">Imprimir</button>
            <a href="comprovante.php?id=<?= $cobrancaId ?>" class="btn btn-comprovante">Ver Comprovante</a>
            <a href="eventos_cadastrados.php" class="btn btn-back">Voltar</a>
        </div>
        <?php else: ?>
        <div class="payment-info">
            <p>Não foi possível gerar o PIX para esta cobrança.</p>
            <p>Por favor, tente novamente mais tarde ou entre em contato com o suporte.</p>
            <a href="eventos_cadastrados.php" class="btn btn-back">Voltar</a>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function copyPixCode() {
            const pixCode = document.getElementById('pix-code').innerText;
            navigator.clipboard.writeText(pixCode)
                .then(() => alert('Código PIX copiado!'))
                .catch(err => console.error('Erro ao copiar: ', err));
        }
        
        // Atualiza o status a cada 30 segundos
        setInterval(() => {
            fetch(`atualiza_status.php?cobranca_id=<?= $cobrancaId ?>`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'RECEIVED' || data.status === 'CONFIRMED') {
                        window.location.href = `comprovante.php?id=<?= $cobrancaId ?>`;
                    }
                });
        }, 30000);
    </script>
</body>
</html>