<?php
session_start();
require_once "classes/AssasService.php";
require_once "func/database.php";

// Verifica autenticação
if (!isset($_SESSION["logado"])) {
    header("Location: index.php");
    exit();
}

// Obtém ID da cobrança e modo de visualização
$cobrancaId = $_GET['cobranca_id'] ?? null;
$modoVisualizacao = isset($_GET['view']);

if (empty($cobrancaId)) {
    $_SESSION['erro'] = "ID da cobrança não informado";
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
        /* Estilos mantidos do arquivo original */
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
        /* ... (outros estilos permanecem iguais) ... */
        
        /* Novo estilo para modo visualização */
        .view-mode .pix-container {
            opacity: 0.7;
        }
        .view-mode .btn-pagar {
            display: none;
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
            <p>Não foi possível gerar o PIX para esta cobrança.</p>
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
                .then(() => alert('Código PIX copiado!'))
                .catch(err => console.error('Erro ao copiar: ', err));
        }
        
        <?php if (!$modoVisualizacao): ?>
        // Atualiza o status a cada 30 segundos apenas no modo de pagamento
        setInterval(() => {
            fetch(`atualiza_status.php?cobranca_id=<?= $cobrancaId ?>`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'RECEIVED' || data.status === 'CONFIRMED') {
                        window.location.href = `comprovante.php?id=<?= $cobrancaId ?>`;
                    }
                });
        }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>