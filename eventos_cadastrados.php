<?php
session_start();
if (!isset($_SESSION["logado"])) {
    header("Location: index.php");
    exit();
}

require_once "classes/atletaService.php";
require_once "classes/AssasService.php";
require_once "func/database.php";

// Configuração para exibir erros (apenas para desenvolvimento)
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $conn = new Conexao();
    $atleta = new Atleta();
    $atletaService = new atletaService($conn, $atleta);
    $asaasService = new AssasService($conn);
    
    $inscritos = $atletaService->listarCampeonatos($_SESSION["id"]);
} catch (Exception $e) {
    $_SESSION['erro'] = "Erro ao obter inscrições: " . $e->getMessage();
    header("Location: index.php");
    exit();
}
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
        /* Estilos específicos para esta página */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-title {
            color: #1e3c64;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .table-responsive {
            overflow-x: auto;
            margin-bottom: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        
        .inscricoes-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        .inscricoes-table th {
            background-color: #1e3c64;
            color: white;
            padding: 12px 15px;
            text-align: left;
        }
        
        .inscricoes-table td {
            padding: 10px 15px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: middle;
        }
        
        .inscricoes-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .inscricoes-table tr:hover {
            background-color: #e9f7fe;
        }
        
        /* Status de pagamento */
        .status {
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .status-pago {
            color: #28a745;
        }
        
        .status-pendente {
            color: #ffc107;
        }
        
        .status-vencido {
            color: #dc3545;
        }
        
        .status-confirmado {
            color: #17a2b8;
        }
        
        .status-erro {
            color: #6c757d;
        }
        
        .valor-pago {
            font-size: 12px;
            color: #6c757d;
            display: block;
            margin-top: 3px;
        }
        
        /* Botões de ação */
        .btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-right: 5px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-pagar {
            background-color: #28a745;
            color: white;
            border: none;
        }
        
        .btn-pagar:hover {
            background-color: #218838;
        }
        
        .btn-visualizar {
            background-color: #17a2b8;
            color: white;
            border: none;
        }
        
        .btn-visualizar:hover {
            background-color: #138496;
        }
        
        .btn-editar {
            background-color: #ffc107;
            color: #212529;
            border: none;
        }
        
        .btn-editar:hover {
            background-color: #e0a800;
        }
        
        .btn-gerar {
            background-color: #6c757d;
            color: white;
            border: none;
        }
        
        .btn-gerar:hover {
            background-color: #5a6268;
        }
        
        /* Mensagens de feedback */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .inscricoes-table {
                font-size: 13px;
            }
            
            .inscricoes-table th,
            .inscricoes-table td {
                padding: 8px 10px;
            }
            
            .btn {
                padding: 5px 8px;
                font-size: 12px;
                margin-bottom: 5px;
                width: 100%;
                justify-content: center;
            }
            
            .acoes-cell {
                display: flex;
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
<?php include "menu/add_menu.php"; ?>

<div class="container">
    <h1 class="page-title">Meus Campeonatos Inscritos</h1>
    
    <?php if (isset($_SESSION['sucesso'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['sucesso']; unset($_SESSION['sucesso']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['erro'])): ?>
        <div class="alert alert-error">
            <?= $_SESSION['erro']; unset($_SESSION['erro']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($inscritos)): ?>
        <div class="alert alert-info">
            Você não está inscrito em nenhum campeonato no momento.
        </div>
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
                        <th>Status Pagamento</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inscritos as $inscrito): 
                        $statusPagamento = $inscrito->status_pagamento ?? 'PENDENTE';
                        $cobrancaId = $inscrito->id_cobranca_asaas ?? null;
                        $valorPago = isset($inscrito->valor_pago) ? 'R$ ' . number_format($inscrito->valor_pago, 2, ',', '.') : '--';
                        
                        // Verificar status atualizado se existir cobrança
                        if ($cobrancaId) {
                            try {
                                $statusInfo = $asaasService->verificarStatusCobranca($cobrancaId);
                                $statusPagamento = $statusInfo['traduzido'];
                            } catch (Exception $e) {
                                $statusPagamento = 'ERRO';
                                error_log("Erro ao verificar status cobrança: " . $e->getMessage());
                            }
                        }
                        
                        // Determinar classe e ícone do status
                        $statusClass = 'status-' . strtolower($statusPagamento);
                        $statusIcon = '';
                        
                        switch (strtoupper($statusPagamento)) {
                            case 'PAGO':
                            case 'RECEIVED':
                                $statusClass = 'status-pago';
                                $statusIcon = '<i class="fas fa-check-circle"></i>';
                                break;
                            case 'CONFIRMADO':
                            case 'CONFIRMED':
                                $statusClass = 'status-confirmado';
                                $statusIcon = '<i class="fas fa-check-double"></i>';
                                break;
                            case 'PENDENTE':
                            case 'PENDING':
                                $statusClass = 'status-pendente';
                                $statusIcon = '<i class="fas fa-clock"></i>';
                                break;
                            case 'VENCIDO':
                            case 'OVERDUE':
                                $statusClass = 'status-vencido';
                                $statusIcon = '<i class="fas fa-exclamation-circle"></i>';
                                break;
                            case 'ERRO':
                                $statusClass = 'status-erro';
                                $statusIcon = '<i class="fas fa-times-circle"></i>';
                                break;
                            default:
                                $statusClass = 'status-pendente';
                                $statusIcon = '<i class="fas fa-question-circle"></i>';
                        }
                    ?>
                    <tr>
                        <td><?= $_SESSION["id"] . $inscrito->idC ?></td>
                        <td>
                            <a href="eventos.php?id=<?= (int)$inscrito->idC ?>">
                                <?= htmlspecialchars($inscrito->campeonato) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($inscrito->lugar) ?></td>
                        <td><?= date('d/m/Y', strtotime($inscrito->dia)) ?></td>
                        <td><?= htmlspecialchars($inscrito->modalidade) ?></td>
                        <td class="text-center"><?= $inscrito->mcom ? "X" : "" ?></td>
                        <td class="text-center"><?= $inscrito->msem ? "X" : "" ?></td>
                        <td class="text-center"><?= $inscrito->macom ? "X" : "" ?></td>
                        <td class="text-center"><?= $inscrito->masem ? "X" : "" ?></td>
                        <td>
                            <span class="status <?= $statusClass ?>">
                                <?= $statusIcon ?> <?= $statusPagamento ?>
                            </span>
                            <span class="valor-pago"><?= $valorPago ?></span>
                        </td>
                        <td class="acoes-cell">
                            <?php if (!in_array(strtoupper($statusPagamento), ['PAGO', 'RECEIVED', 'CONFIRMADO', 'CONFIRMED'])): ?>
                                <?php if ($cobrancaId): ?>
                                    <a href="pagamento.php?cobranca_id=<?= $cobrancaId ?>" class="btn btn-pagar">
                                        <i class="fas fa-money-bill-wave"></i> Pagar
                                    </a>
                                    <a href="visualizar_cobranca.php?cobranca_id=<?= $cobrancaId ?>" class="btn btn-visualizar" title="Ver cobrança">
                                        <i class="fas fa-eye"></i> Detalhes
                                    </a>
                                <?php else: ?>
                                    <a href="gerar_cobranca.php?evento_id=<?= $inscrito->idC ?>&atleta_id=<?= $_SESSION["id"] ?>" class="btn btn-gerar">
                                        <i class="fas fa-file-invoice-dollar"></i> Gerar Cobrança
                                    </a>
                                <?php endif; ?>
                            <?php elseif ($cobrancaId): ?>
                                <a href="visualizar_cobranca.php?cobranca_id=<?= $cobrancaId ?>" class="btn btn-visualizar" title="Ver recibo">
                                    <i class="fas fa-receipt"></i> Recibo
                                </a>
                            <?php endif; ?>
                            
                            <a href="inscricao.php?inscricao=<?= $inscrito->idC ?>" class="btn btn-editar" title="Editar inscrição">
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