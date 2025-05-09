<?php
session_start();

// DEBUG: Verificar se a sessão está iniciada corretamente
error_log("DEBUG: Iniciando eventos_cadastrados.php");
error_log("DEBUG: Dados da sessão: " . print_r($_SESSION, true));

if (!isset($_SESSION["logado"], $_SESSION["id"])) {
    error_log("ERRO: Sessão inválida - redirecionando");
    header("Location: index.php");
    exit();
}

// DEBUG: Verificar includes
error_log("DEBUG: Carregando dependências...");
require_once "classes/atletaService.php";
require_once "classes/AssasService.php";
require_once "func/database.php";

// Inicializa variáveis
$inscritos = [];
$erro = null;

try {
    // DEBUG: Conexão com banco
    error_log("DEBUG: Criando conexão...");
    $conn = new Conexao();
    
    $atleta = new Atleta();
    $atletaService = new atletaService($conn, $atleta);
    $asaasService = new AssasService($conn);

    // DEBUG: Antes de buscar inscrições
    error_log("DEBUG: Buscando inscrições para atleta ID: " . $_SESSION["id"]);
    
    $resultado = $atletaService->listarCampeonatos($_SESSION["id"]);
    
    // DEBUG: Verificar resultado
    error_log("DEBUG: Tipo do resultado: " . gettype($resultado));
    if (is_array($resultado)) {
        error_log("DEBUG: Número de inscrições encontradas: " . count($resultado));
        if (!empty($resultado)) {
            error_log("DEBUG: Exemplo de dados da primeira inscrição:");
            error_log(print_r($resultado[0], true));
        }
    } else {
        error_log("DEBUG: Resultado não é array");
    }
    
    $inscritos = is_array($resultado) ? $resultado : [];

} catch (Exception $e) {
    $erro = "Erro ao obter inscrições: " . $e->getMessage();
    error_log("ERRO CRÍTICO: " . $erro);
    error_log("Stack trace: " . $e->getTraceAsString());
    $_SESSION['erro'] = $erro;
    header("Location: index.php");
    exit();
}

// DEBUG: Verificar dados antes de exibir
error_log("DEBUG: Dados que serão exibidos:");
error_log(print_r($inscritos, true));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Campeonatos Inscritos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="/estilos/icone.jpeg">
    <style>
        /* Estilos mantidos iguais */
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .page-title { color: #1e3c64; margin-bottom: 20px; text-align: center; }
        .table-responsive { overflow-x: auto; margin-bottom: 30px; box-shadow: 0 0 10px rgba(0,0,0,0.1); border-radius: 8px; }
        .inscricoes-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .inscricoes-table th { background-color: #1e3c64; color: white; padding: 12px 15px; text-align: left; }
        .inscricoes-table td { padding: 10px 15px; border-bottom: 1px solid #e0e0e0; vertical-align: middle; }
        .inscricoes-table tr:nth-child(even) { background-color: #f8f9fa; }
        .inscricoes-table tr:hover { background-color: #e9f7fe; }
        .status { font-weight: bold; display: flex; align-items: center; gap: 5px; }
        .status-pago { color: #28a745; }
        .status-pendente { color: #ffc107; }
        .status-vencido { color: #dc3545; }
        .status-confirmado { color: #17a2b8; }
        .status-erro { color: #6c757d; }
        .valor-pago { font-size: 12px; color: #6c757d; display: block; margin-top: 3px; }
        .btn { padding: 6px 12px; border-radius: 4px; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; margin-right: 5px; transition: all 0.3s ease; }
        .btn-pagar { background-color: #28a745; color: white; }
        .btn-pagar:hover { background-color: #218838; }
        .btn-visualizar { background-color: #17a2b8; color: white; }
        .btn-visualizar:hover { background-color: #138496; }
        .btn-editar { background-color: #ffc107; color: #212529; }
        .btn-editar:hover { background-color: #e0a800; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .text-center { text-align: center; }
        
        @media (max-width: 768px) {
            .inscricoes-table { font-size: 13px; }
            .inscricoes-table th, .inscricoes-table td { padding: 8px 10px; }
            .btn { padding: 5px 8px; font-size: 12px; margin-bottom: 5px; width: 100%; justify-content: center; }
            .acoes-cell { display: flex; flex-direction: column; gap: 5px; }
        }
    </style>
</head>
<body>
<?php include "menu/add_menu.php"; ?>

<div class="container">
    <h1 class="page-title">Meus Campeonatos Inscritos</h1>
    
    <?php if (isset($_SESSION['sucesso'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['sucesso']); ?>
            <?php unset($_SESSION['sucesso']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['erro'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['erro']); ?>
            <?php unset($_SESSION['erro']); ?>
        </div>
    <?php elseif ($erro): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($erro); ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($inscritos)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Nenhuma inscrição encontrada para seu usuário.
        </div>
        <?php error_log("DEBUG: Nenhuma inscrição encontrada para exibição"); ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="inscricoes-table">
                <thead>
                    <tr>
                        <th>Nº Inscrição</th>
                        <th>Campeonato</th>
                        <th>Local</th>
                        <th>Data</th>
                        <th>Modalidade</th>
                        <th>C/ Quimono</th>
                        <th>S/ Quimono</th>
                        <th>Absoluto c/</th>
                        <th>Absoluto s/</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inscritos as $index => $inscrito): 
                        // DEBUG: Verificar cada inscrição
                        error_log("DEBUG: Processando inscrição #$index: " . print_r($inscrito, true));
                        
                        if (!is_object($inscrito)) {
                            error_log("ERRO: Inscrição #$index não é objeto válido");
                            continue;
                        }
                        
                        $statusPagamento = $inscrito->status_pagamento ?? 'PENDENTE';
                        $cobrancaId = $inscrito->id_cobranca_asaas ?? null;
                        $valorPago = isset($inscrito->valor_pago) ? 'R$ ' . number_format($inscrito->valor_pago, 2, ',', '.') : '--';
                        
                        if ($cobrancaId) {
                            try {
                                error_log("DEBUG: Verificando status da cobrança $cobrancaId");
                                $statusInfo = $asaasService->verificarStatusCobranca($cobrancaId);
                                $statusPagamento = $statusInfo['traduzido'];
                                error_log("DEBUG: Status atualizado para: $statusPagamento");
                            } catch (Exception $e) {
                                $statusPagamento = 'ERRO';
                                error_log("ERRO ao verificar cobrança: " . $e->getMessage());
                            }
                        }
                        
                        // Determinar ícone e classe do status
                        $statusClass = 'status-' . strtolower(str_replace(' ', '-', $statusPagamento));
                        $statusIcon = 'fa-question-circle';
                        
                        switch (strtoupper($statusPagamento)) {
                            case 'PAGO':
                            case 'RECEIVED':
                                $statusClass = 'status-pago';
                                $statusIcon = 'fa-check-circle';
                                break;
                            case 'CONFIRMADO':
                            case 'CONFIRMED':
                                $statusClass = 'status-confirmado';
                                $statusIcon = 'fa-check-double';
                                break;
                            case 'PENDENTE':
                            case 'PENDING':
                                $statusClass = 'status-pendente';
                                $statusIcon = 'fa-clock';
                                break;
                            case 'VENCIDO':
                            case 'OVERDUE':
                                $statusClass = 'status-vencido';
                                $statusIcon = 'fa-exclamation-circle';
                                break;
                            case 'ERRO':
                                $statusClass = 'status-erro';
                                $statusIcon = 'fa-times-circle';
                                break;
                        }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($_SESSION["id"] . ($inscrito->idC ?? '')) ?></td>
                        <td>
                            <a href="eventos.php?id=<?= (int)($inscrito->idC ?? 0) ?>">
                                <?= htmlspecialchars($inscrito->campeonato ?? 'N/A') ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($inscrito->lugar ?? 'N/A') ?></td>
                        <td><?= isset($inscrito->dia) ? date('d/m/Y', strtotime($inscrito->dia)) : 'N/A' ?></td>
                        <td><?= htmlspecialchars($inscrito->modalidade ?? 'N/A') ?></td>
                        <td class="text-center"><?= !empty($inscrito->mcom) ? "X" : "" ?></td>
                        <td class="text-center"><?= !empty($inscrito->msem) ? "X" : "" ?></td>
                        <td class="text-center"><?= !empty($inscrito->macom) ? "X" : "" ?></td>
                        <td class="text-center"><?= !empty($inscrito->masem) ? "X" : "" ?></td>
                        <td>
                            <span class="status <?= $statusClass ?>">
                                <i class="fas <?= $statusIcon ?>"></i> <?= htmlspecialchars($statusPagamento) ?>
                            </span>
                            <span class="valor-pago"><?= htmlspecialchars($valorPago) ?></span>
                        </td>
                        <td class="acoes-cell">
                            <?php if ($cobrancaId): ?>
                                <a href="pagamento.php?cobranca_id=<?= htmlspecialchars($cobrancaId) ?>" 
                                   class="btn btn-visualizar">
                                    <i class="fas fa-eye"></i> Ver Pagamento
                                </a>
                            <?php else: ?>
                                <a href="pagamento.php?evento_id=<?= (int)($inscrito->idC ?? 0) ?>" 
                                   class="btn btn-pagar">
                                    <i class="fas fa-money-bill-wave"></i> Pagar
                                </a>
                            <?php endif; ?>
                            <a href="inscricao.php?inscricao=<?= (int)($inscrito->idC ?? 0) ?>" 
                               class="btn btn-editar" title="Editar inscrição">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include "menu/footer.php"; ?>
</body>
</html>