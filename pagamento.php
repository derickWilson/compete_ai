<?php
session_start();
require_once "classes/AssasService.php";
require_once "func/database.php";

// Verifica autenticação
if (!isset($_SESSION["logado"])) {
    header("Location: index.php");
    exit();
}

// Obtém e sanitiza os parâmetros
$cobrancaId = filter_input(INPUT_GET, 'cobranca_id', FILTER_SANITIZE_STRING);
$eventoId = filter_input(INPUT_GET, 'evento_id', FILTER_VALIDATE_INT);
$modoVisualizacao = isset($_GET['view']);

// Se não tem cobrança mas tem evento_id, gera nova cobrança
if (empty($cobrancaId) && !empty($eventoId)) {
    try {
        $conn = new Conexao();
        $asaasService = new AssasService($conn);
        $cobrancaId = $asaasService->criarCobrancaParaEvento($eventoId, $_SESSION['id']);
        
        // Redireciona com o ID da cobrança
        header("Location: pagamento.php?cobranca_id=" . urlencode($cobrancaId));
        exit();
    } catch (Exception $e) {
        $_SESSION['erro'] = "Erro ao gerar cobrança: " . $e->getMessage();
        header("Location: eventos_cadastrados.php");
        exit();
    }
}

// Verificação final do ID da cobrança
if (empty($cobrancaId)) {
    $_SESSION['erro'] = "Identificador de pagamento não informado.";
    header("Location: eventos_cadastrados.php");
    exit();
}

try {
    $conn = new Conexao();
    $asaasService = new AssasService($conn);
    
    // 1. Verifica status atual
    $status = $asaasService->verificarStatusCobranca($cobrancaId);
    
    // 2. Busca dados completos da cobrança
    $cobranca = $asaasService->buscarCobrancaCompleta($cobrancaId);
    
    if (!$cobranca['success']) {
        throw new Exception("Erro ao buscar cobrança");
    }
    
    $dadosCobranca = $cobranca['payment'];
    
    // Se já estiver pago e não for modo visualização, redireciona para o comprovante
    if (!$modoVisualizacao && in_array($status['status'], ['RECEIVED', 'CONFIRMED'])) {
        header("Location: comprovante.php?id=" . $cobrancaId);
        exit();
    }
    
} catch (Exception $e) {
    $_SESSION['erro'] = "Erro ao processar pagamento: " . $e->getMessage();
    header("Location: eventos_cadastrados.php");
    exit();
}

// Determina o título da página
$tituloPagina = $modoVisualizacao ? "Detalhes do Pagamento" : "Pagamento via PIX";
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $tituloPagina ?> - <?= htmlspecialchars($dadosCobranca['description']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f7fa;
            color: #333;
        }
        
        .container {
            max-width: 800px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-top: 0;
        }
        
        .payment-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .payment-info h2 {
            margin-top: 0;
            color: #2980b9;
        }
        
        .pix-container {
            text-align: center;
            padding: 20px;
            border: 2px dashed #3498db;
            border-radius: 8px;
            background: #f0f8ff;
        }
        
        .pix-qrcode {
            margin: 20px auto;
            padding: 15px;
            background: white;
            display: inline-block;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .pix-qrcode img {
            width: 250px;
            height: 250px;
        }
        
        .pix-code {
            background: #2c3e50;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            word-break: break-all;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 20px;
            margin: 10px 5px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            font-size: 16px;
        }
        
        .btn-copy {
            background: #3498db;
            color: white;
        }
        
        .btn-print {
            background: #95a5a6;
            color: white;
        }
        
        .btn-comprovante {
            background: #27ae60;
            color: white;
        }
        
        .btn-back {
            background: #e74c3c;
            color: white;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
        }
        
        .status-pago {
            background: #d4edda;
            color: #155724;
        }
        
        .status-confirmado {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-pendente {
            background: #fff3cd;
            color: #856404;
        }
        
        .view-mode .pix-container {
            opacity: 0.7;
            pointer-events: none;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .btn {
                display: block;
                width: 100%;
                margin: 10px 0;
            }
        }
    </style>
</head>
<body class="<?= $modoVisualizacao ? 'view-mode' : '' ?>">
    <div class="container">
        <h1><?= $tituloPagina ?></h1>
        
        <div class="payment-info">
            <h2><?= htmlspecialchars($dadosCobranca['description']) ?></h2>
            <p><strong>Valor:</strong> R$ <?= number_format($dadosCobranca['value'], 2, ',', '.') ?></p>
            <p><strong>Status:</strong> 
                <span class="status-badge status-<?= strtolower($status['traduzido']) ?>">
                    <?= $status['traduzido'] ?>
                </span>
            </p>
            <p><strong>Vencimento:</strong> <?= date('d/m/Y', strtotime($dadosCobranca['dueDate'])) ?></p>
            <?php if (!empty($dadosCobranca['paymentDate'])): ?>
                <p><strong>Pagamento realizado em:</strong> <?= date('d/m/Y H:i', strtotime($dadosCobranca['paymentDate'])) ?></p>
            <?php endif; ?>
        </div>
        
        <?php if ($dadosCobranca['billingType'] === 'PIX' && isset($dadosCobranca['pix'])): ?>
        <div class="pix-container">
            <h3><i class="fas fa-qrcode"></i> Pague com PIX</h3>
            <div class="pix-qrcode">
                <img src="data:image/png;base64,<?= $dadosCobranca['pix']['encodedImage'] ?>" alt="QR Code PIX">
            </div>
            
            <p class="expiration">
                <i class="fas fa-clock"></i> Validade: <?= date('d/m/Y H:i', strtotime($dadosCobranca['pix']['expirationDate'])) ?>
            </p>
            
            <div class="pix-code">
                <p><strong><i class="fas fa-barcode"></i> Código PIX (copie e cole no seu app):</strong></p>
                <p id="pix-code"><?= $dadosCobranca['pix']['payload'] ?></p>
            </div>
            
            <?php if (!$modoVisualizacao): ?>
                <button onclick="copyPixCode()" class="btn btn-copy">
                    <i class="fas fa-copy"></i> Copiar Código
                </button>
            <?php endif; ?>
            
            <button onclick="window.print()" class="btn btn-print">
                <i class="fas fa-print"></i> Imprimir
            </button>
            
            <a href="comprovante.php?id=<?= $cobrancaId ?>" class="btn btn-comprovante">
                <i class="fas fa-receipt"></i> Comprovante
            </a>
            
            <a href="eventos_cadastrados.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
        <?php else: ?>
        <div class="payment-info">
            <p><i class="fas fa-exclamation-triangle"></i> Não foi possível gerar o PIX para esta cobrança.</p>
            <p>Por favor, tente novamente mais tarde ou entre em contato com o suporte.</p>
            <a href="eventos_cadastrados.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function copyPixCode() {
            const pixCode = document.getElementById('pix-code').innerText;
            navigator.clipboard.writeText(pixCode)
                .then(() => alert('Código PIX copiado com sucesso!'))
                .catch(err => alert('Erro ao copiar: ' + err));
        }
        
        <?php if (!$modoVisualizacao && $status['status'] === 'PENDING'): ?>
        // Atualiza o status a cada 30 segundos
        setInterval(() => {
            fetch(`atualiza_status.php?cobranca_id=<?= $cobrancaId ?>`)
                .then(response => {
                    if (!response.ok) throw new Error('Erro na requisição');
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'RECEIVED' || data.status === 'CONFIRMED') {
                        window.location.href = `comprovante.php?id=<?= $cobrancaId ?>`;
                    }
                })
                .catch(error => console.error('Erro:', error));
        }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>