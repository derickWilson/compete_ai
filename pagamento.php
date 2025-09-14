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

// Verificação do ID da cobrança
if (empty($cobrancaId)) {
    $_SESSION['erro'] = "ID da cobrança não informado";
    header("Location: eventos_cadastrados.php");
    exit();
}

try {
    $conn = new Conexao();
    $asaasService = new AssasService($conn);
    
    // 1. Busca os dados completos da cobrança
    $response = $asaasService->buscarCobrancaCompleta($cobrancaId);
    
    if (!$response['success']) {
        throw new Exception("Cobrança não encontrada ou erro ao acessar");
    }
    
    $dadosCobranca = $response['payment'];
    
    // 2. Verifica status atual
    $statusInfo = $asaasService->verificarStatusCobranca($cobrancaId);
    $status = $statusInfo['status'];
    $statusTraduzido = $statusInfo['traduzido'];
    
    // 3. Se já estiver pago e não for modo visualização, atualiza banco e redireciona
    if (!$modoVisualizacao && in_array($status, ['RECEIVED', 'CONFIRMED'])) {
        atualizarStatusInscricao($asaasService, $dadosCobranca, $cobrancaId, $statusTraduzido);
        header("Location: comprovante.php?id=" . $cobrancaId);
        exit();
    }

    // 4. Se for PIX e ainda estiver pendente, obtém QR Code se não tiver
    if ($dadosCobranca['billingType'] === 'PIX' && $status === 'PENDING' && empty($dadosCobranca['pix'])) {
        $pixInfo = $asaasService->buscarQrCodePix($cobrancaId);
        $dadosCobranca['pix'] = $pixInfo;
    }

} catch (Exception $e) {
    error_log("Erro no pagamento.php - ID: $cobrancaId - Erro: " . $e->getMessage());
    $_SESSION['erro'] = "Erro ao processar pagamento: " . $e->getMessage();
    header("Location: eventos_cadastrados.php");
    exit();
}

// Função para atualizar status da inscrição no banco de dados
function atualizarStatusInscricao($asaasService, $dadosCobranca, $cobrancaId, $statusTraduzido) {
    $externalReference = $dadosCobranca['externalReference'] ?? '';
    
    if (preg_match('/EV_(\d+)_AT_(\d+)/', $externalReference, $matches)) {
        $idEvento = $matches[1];
        $idAtleta = $matches[2];
        
        $asaasService->atualizarInscricaoComPagamento(
            $idAtleta,
            $idEvento,
            $cobrancaId,
            $statusTraduzido,
            $dadosCobranca['value']
        );
    } else {
        error_log("Formato inválido de externalReference: " . $externalReference);
        throw new Exception("Não foi possível identificar a inscrição relacionada");
    }
}

// Determina o título da página
$tituloPagina = $modoVisualizacao ? "Detalhes do Pagamento" : "Pagamento via PIX";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina) ?> - <?= htmlspecialchars($dadosCobranca['description'] ?? '') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/pagamento.css">
    <link rel="icon" href="/estilos/icone.jpeg">
    <style>
    /* Estilos gerais */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f5f7fa;
    color: #333;
    line-height: 1.6;
}

.container {
    max-width: 800px;
    margin: 20px auto;
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

h1, h2, h3 {
    color: #1e3c64;
    margin-top: 0;
}

/* Informações do pagamento */
.payment-info {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.info-item {
    display: flex;
    flex-direction: column;
}

.info-label {
    font-weight: bold;
    color: #666;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-label i {
    width: 20px;
    text-align: center;
}

.info-value {
    font-size: 16px;
}

/* Status */
.status-badge {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.status-pago, .status-received {
    background-color: #d4edda;
    color: #155724;
}

.status-confirmado, .status-confirmed {
    background-color: #cce5ff;
    color: #004085;
}

.status-pendente, .status-pending {
    background-color: #fff3cd;
    color: #856404;
}

.status-vencido, .status-overdue {
    background-color: #f8d7da;
    color: #721c24;
}

/* Container PIX */
.pix-container {
    text-align: center;
    margin-top: 30px;
}

.pix-qrcode-container {
    display: flex;
    justify-content: center;
    gap: 30px;
    margin: 25px 0;
    flex-wrap: wrap;
}

.pix-qrcode {
    background: white;
    padding: 15px;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

.pix-qrcode img {
    width: 200px;
    height: 200px;
}

.pix-instructions {
    text-align: left;
    max-width: 300px;
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
}

.pix-instructions ol {
    padding-left: 20px;
    margin: 0;
}

.pix-instructions li {
    margin-bottom: 10px;
}

.pix-expiration {
    color: #dc3545;
    font-weight: bold;
    margin: 15px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.pix-code-container {
    margin: 25px 0;
}

.pix-code-label {
    font-weight: bold;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.pix-code-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 10px;
}

#pix-code {
    background: #f8f9fa;
    padding: 10px 15px;
    border-radius: 5px;
    word-break: break-all;
    font-family: monospace;
    text-align: center;
    flex-grow: 1;
    max-width: 500px;
}

/* Botões */
.action-buttons {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 30px;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 5px;
    text-decoration: none;
    color: white;
    cursor: pointer;
    border: none;
    font-size: 16px;
    transition: all 0.3s ease;
}

.btn-copy {
    background: #6c757d;
    color: white;
    padding: 10px;
    border-radius: 5px;
}

.btn-copy.copied {
    background: #28a745;
}

.btn-print {
    background: #17a2b8;
}

.btn-print:hover {
    background: #138496;
}

.btn-receipt {
    background: #28a745;
}

.btn-receipt:hover {
    background: #218838;
}

.btn-back {
    background: #343a40;
}

.btn-back:hover {
    background: #23272b;
}

/* Alertas */
.alert {
    padding: 15px;
    border-radius: 5px;
    margin: 20px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-warning {
    background: #fff3cd;
    color: #856404;
    border-left: 4px solid #ffeeba;
}

/* Responsividade */
@media (max-width: 768px) {
    .container {
        padding: 20px;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .pix-qrcode-container {
        flex-direction: column;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .btn {
        width: 100%;
        max-width: 300px;
    }
}
    </style>
</head>
<body class="<?= $modoVisualizacao ? 'view-mode' : '' ?>">
    <div class="container">
        <h1><?= htmlspecialchars($tituloPagina) ?></h1>
        
        <div class="payment-info">
            <h2><?= htmlspecialchars($dadosCobranca['description'] ?? '') ?></h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-money-bill-wave"></i> Valor:</span>
                    <span class="info-value">R$ <?= number_format($dadosCobranca['value'] ?? 0, 2, ',', '.') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-info-circle"></i> Status:</span>
                    <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $statusTraduzido)) ?>">
                        <?= htmlspecialchars($statusTraduzido) ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-calendar-day"></i> Vencimento:</span>
                    <span class="info-value"><?= isset($dadosCobranca['dueDate']) ? date('d/m/Y', strtotime($dadosCobranca['dueDate'])) : '--' ?></span>
                </div>
                <?php if (isset($dadosCobranca['paymentDate']) && in_array($status, ['RECEIVED', 'CONFIRMED'])): ?>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-check-circle"></i> Pagamento:</span>
                    <span class="info-value"><?= date('d/m/Y H:i', strtotime($dadosCobranca['paymentDate'])) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($dadosCobranca['billingType'] === 'PIX' && !empty($dadosCobranca['pix'])): ?>
        <div class="pix-container">
            <h3><i class="fas fa-qrcode"></i> Pague com PIX</h3>
            
            <div class="pix-qrcode-container">
                <div class="pix-qrcode">
                    <img src="data:image/png;base64,<?= $dadosCobranca['pix']['encodedImage'] ?>" alt="QR Code PIX">
                </div>
                <div class="pix-instructions">
                    <ol>
                        <li>Abra o app do seu banco</li>
                        <li>Selecione a opção PIX</li>
                        <li>Escolha "Pagar com QR Code"</li>
                        <li>Aponte a câmera para o código</li>
                        <li>Confirme o pagamento</li>
                    </ol>
                </div>
            </div>
            
            <div class="pix-expiration">
                <i class="fas fa-clock"></i> Validade: <?= date('d/m/Y H:i', strtotime($dadosCobranca['pix']['expirationDate'])) ?>
            </div>
            
            <div class="pix-code-container">
                <p class="pix-code-label"><i class="fas fa-barcode"></i> Código PIX (copie e cole no seu app):</p>
                <div class="pix-code-wrapper">
                    <code id="pix-code"><?= htmlspecialchars($dadosCobranca['pix']['payload']) ?></code>
                    <button onclick="copyPixCode()" class="btn btn-copy" title="Copiar código PIX">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
            
            <div class="action-buttons">
                <?php if (!$modoVisualizacao): ?>
                <button onclick="window.print()" class="btn btn-print">
                    <i class="fas fa-print"></i> Imprimir
                </button>
                <?php endif; ?>
                
                <a href="comprovante.php?id=<?= $cobrancaId ?>" class="btn btn-receipt">
                    <i class="fas fa-receipt"></i> Comprovante
                </a>
                
                <a href="eventos_cadastrados.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>
        </div>
        <?php elseif ($dadosCobranca['billingType'] === 'PIX'): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> Não foi possível obter os dados do PIX para esta cobrança.
        </div>
        <?php endif; ?>
    </div>

    <script>
        function copyPixCode() {
            const pixCode = document.getElementById('pix-code').innerText;
            navigator.clipboard.writeText(pixCode)
                .then(() => {
                    const btn = document.querySelector('.btn-copy');
                    btn.innerHTML = '<i class="fas fa-check"></i>';
                    btn.classList.add('copied');
                    setTimeout(() => {
                        btn.innerHTML = '<i class="fas fa-copy"></i>';
                        btn.classList.remove('copied');
                    }, 2000);
                })
                .catch(err => {
                    alert('Não foi possível copiar o código: ' + err);
                });
        }
        
        <?php if (!$modoVisualizacao && $status === 'PENDING'): ?>
        // Atualiza o status a cada 30 segundos
        function checkPaymentStatus() {
            fetch(`atualiza_status.php?cobranca_id=<?= $cobrancaId ?>`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Erro:', data.error);
                        return;
                    }
                    
                    if (data.status === 'RECEIVED' || data.status === 'CONFIRMED') {
                        window.location.href = `comprovante.php?id=<?= $cobrancaId ?>`;
                    }
                })
                .catch(error => console.error('Erro:', error));
        }
        
        // Verifica imediatamente e depois a cada 30 segundos
        checkPaymentStatus();
        setInterval(checkPaymentStatus, 30000);
        <?php endif; ?>
    </script>
</body>
</html>