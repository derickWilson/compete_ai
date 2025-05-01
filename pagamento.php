<?php
session_start();
require_once "classes/AssasService.php";
require_once "func/database.php";

// Verifica autenticação
if (!isset($_SESSION["logado"])) {
    header("Location: index.php");
    exit();
}

// Obtém o ID da cobrança de forma segura
$cobrancaId = filter_input(INPUT_GET, 'cobranca_id', FILTER_SANITIZE_STRING);
$modoVisualizacao = isset($_GET['view']);

// Verificação robusta do ID da cobrança
if (empty($cobrancaId)) {
    $_SESSION['erro'] = "ID da cobrança não informado";
    header("Location: eventos_cadastrados.php");
    exit();
}

try {
    $conn = new Conexao();
    $asaasService = new AssasService($conn);
    
    // 1. Busca os dados completos da cobrança existente
    $response = $asaasService->buscarCobrancaCompleta($cobrancaId);
    
    if (!$response['success']) {
        throw new Exception("Cobrança não encontrada");
    }
    
    $dadosCobranca = $response['payment'];
    
    // 2. Verifica se é PIX e obtém os dados de pagamento
    if ($dadosCobranca['billingType'] === 'PIX') {
        // Se não tem dados PIX, tenta obter
        if (empty($dadosCobranca['pix'])) {
            $pixInfo = $asaasService->buscarQrCodePix($cobrancaId);
            $dadosCobranca['pix'] = $pixInfo;
        }
    }
    
    // 3. Verifica status atual
    $status = $asaasService->verificarStatusCobranca($cobrancaId);
    
    // Se já estiver pago e não for modo visualização, redireciona
    if (!$modoVisualizacao && in_array($status['status'], ['RECEIVED', 'CONFIRMED'])) {
        header("Location: comprovante.php?id=" . $cobrancaId);
        exit();
    }

} catch (Exception $e) {
    error_log("Erro no pagamento.php - ID: $cobrancaId - Erro: " . $e->getMessage());
    $_SESSION['erro'] = "Erro ao acessar cobrança: " . $e->getMessage();
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
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="/estilos/icone.jpeg">
    <style>
        /* Estilos mantidos conforme versão anterior */
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
        /* ... (outros estilos permanecem iguais) ... */
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
        </div>
        
        <?php if ($dadosCobranca['billingType'] === 'PIX' && !empty($dadosCobranca['pix'])): ?>
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
                <p id="pix-code"><?= htmlspecialchars($dadosCobranca['pix']['payload']) ?></p>
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
        </div>
        <?php elseif ($dadosCobranca['billingType'] === 'PIX'): ?>
        <div class="payment-info">
            <p><i class="fas fa-exclamation-triangle"></i> Não foi possível obter os dados do PIX para esta cobrança.</p>
        </div>
        <?php endif; ?>
        
        <a href="eventos_cadastrados.php" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>

    <script>
        function copyPixCode() {
            const pixCode = document.getElementById('pix-code').innerText;
            navigator.clipboard.writeText(pixCode)
                .then(() => {
                    const btn = document.querySelector('.btn-copy');
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-check"></i> Copiado!';
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                    }, 2000);
                })
                .catch(err => {
                    alert('Não foi possível copiar o código: ' + err);
                });
        }
        
        <?php if (!$modoVisualizacao && $status['status'] === 'PENDING'): ?>
        // Atualiza o status a cada 30 segundos
        setInterval(() => {
            fetch(`atualiza_status.php?cobranca_id=<?= $cobrancaId ?>`)
                .then(response => response.json())
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