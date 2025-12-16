<?php
session_start();

if (!isset($_SESSION["logado"])) {
    header("Location: index.php");
    exit();
}

require_once "classes/atletaService.php";
require_once "classes/AssasService.php";
require_once "func/database.php";

$inscritos = [];
$erro = null;

try {
    $conn = new Conexao();
    $atleta = new Atleta();
    $atletaService = new atletaService($conn, $atleta);
    $asaasService = new AssasService($conn);

    $resultado = $atletaService->listarCampeonatos($_SESSION["id"]);
    $inscritos = is_array($resultado) ? $resultado : [];

} catch (Exception $e) {
    $erro = "Erro ao obter inscrições: " . $e->getMessage();
    $_SESSION['erro'] = $erro;
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
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-title {
            color: var(--white) !important;
            margin-bottom: 20px;
            text-align: center;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
            font-size: 2rem;
        }

        .table-responsive {
            overflow-x: auto;
            margin-bottom: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            background: var(--white);
            position: relative;
        }

        .scroll-hint {
            text-align: center;
            padding: 8px;
            background: var(--primary-light);
            color: white;
            font-size: 12px;
            display: none;
            border-top-left-radius: var(--border-radius);
            border-top-right-radius: var(--border-radius);
        }

        .scroll-hint i {
            margin-right: 5px;
        }

        .inscricoes-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .inscricoes-table th {
            background-color: var(--primary-dark);
            color: var(--white);
            padding: 12px;
            text-align: left;
            font-weight: 600;
            white-space: nowrap;
        }

        .inscricoes-table td {
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
            white-space: nowrap;
        }

        .inscricoes-table tr:hover {
            background-color: #f8f9fa;
        }

        .status {
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .status-pago {
            color: var(--success);
        }

        .status-pendente {
            color: var(--warning);
        }

        .status-erro {
            color: var(--danger);
        }

        .text-center {
            text-align: center;
        }

        .btn {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            transition: var(--transition);
            margin: 2px;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-weight: 500;
        }

        .btn-pagar {
            background-color: var(--success);
            color: white;
        }

        .btn-pagar:hover {
            background-color: #218838;
            transform: translateY(-1px);
        }

        .btn-visualizar {
            background-color: var(--primary);
            color: white;
        }

        .btn-visualizar:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-editar {
            background-color: var(--accent);
            color: var(--dark);
        }

        .btn-editar:hover {
            background-color: #d4a017;
            transform: translateY(-1px);
        }

        .valor-pago {
            display: block;
            font-size: 12px;
            color: var(--gray);
            margin-top: 4px;
        }

        .modalidade-cell {
            font-size: 12px;
            line-height: 1.3;
        }

        /* Indicador de rolagem personalizado */
        .table-responsive::-webkit-scrollbar {
            height: 8px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-bottom-left-radius: var(--border-radius);
            border-bottom-right-radius: var(--border-radius);
        }

        /* Estados vazios */
        .nenhuma-inscricao {
            text-align: center;
            padding: 60px 20px;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin: 20px 0;
        }

        .nenhuma-inscricao i {
            font-size: 48px;
            color: var(--gray);
            margin-bottom: 20px;
        }

        .nenhuma-inscricao h3 {
            color: var(--dark);
            margin-bottom: 10px;
        }

        .nenhuma-inscricao p {
            color: var(--gray);
            margin-bottom: 20px;
        }

        /* Cards alternativos para mobile */
        .cards-inscricoes {
            display: none;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 30px;
        }

        .card-inscricao {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 15px;
            box-shadow: var(--box-shadow);
            border-left: 4px solid var(--primary);
        }

        .card-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .card-title {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 5px;
            flex: 1;
        }

        .card-status {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: 600;
        }

        .card-details {
            display: grid;
            gap: 8px;
            margin-bottom: 15px;
        }

        .card-detail {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .card-detail:last-child {
            border-bottom: none;
        }

        .card-label {
            font-weight: 600;
            color: var(--primary-dark);
            font-size: 12px;
        }

        .card-value {
            color: var(--dark);
            font-size: 12px;
            text-align: right;
        }

        .card-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .card-actions .btn {
            flex: 1;
            min-width: 120px;
            justify-content: center;
            font-size: 12px;
            padding: 8px 12px;
        }

        /* Responsividade para a tabela */
        @media (max-width: 992px) {
            .inscricoes-table {
                font-size: 14px;
            }

            .btn {
                padding: 5px 10px;
                font-size: 13px;
            }
        }

        @media (max-width: 768px) {
            .scroll-hint {
                display: block;
            }

            .table-responsive {
                margin: 0 -15px;
                border-radius: 0;
            }

            .inscricoes-table {
                font-size: 13px;
                min-width: 700px;
            }

            .inscricoes-table th,
            .inscricoes-table td {
                padding: 8px 6px;
            }

            /* Mostra apenas colunas essenciais em mobile */
            .inscricoes-table th:nth-child(1),
            .inscricoes-table td:nth-child(1),
            .inscricoes-table th:nth-child(2),
            .inscricoes-table td:nth-child(2),
            .inscricoes-table th:nth-child(4),
            .inscricoes-table td:nth-child(4),
            .inscricoes-table th:nth-child(7),
            .inscricoes-table td:nth-child(7),
            .inscricoes-table th:nth-child(8),
            .inscricoes-table td:nth-child(8) {
                display: table-cell;
            }

            /* Oculta colunas menos importantes */
            .inscricoes-table th:nth-child(3),
            .inscricoes-table td:nth-child(3),
            .inscricoes-table th:nth-child(5),
            .inscricoes-table td:nth-child(5),
            .inscricoes-table th:nth-child(6),
            .inscricoes-table td:nth-child(6) {
                display: none;
            }

            .btn {
                display: inline-block;
                margin: 2px;
                font-size: 12px;
                padding: 6px 10px;
            }

            /* Alterna para cards em telas muito pequenas */
            @media (max-width: 576px) {
                .table-responsive {
                    display: none;
                }

                .cards-inscricoes {
                    display: flex;
                }
            }
        }

        @media (max-width: 576px) {
            .container {
                padding: 0 15px;
            }

            .page-title {
                font-size: 1.6rem;
                margin-bottom: 15px;
            }

            .card-actions {
                flex-direction: column;
            }

            .card-actions .btn {
                min-width: 100%;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 10px;
            }

            .page-title {
                font-size: 1.4rem;
            }

            .card-inscricao {
                padding: 12px;
            }

            .card-actions .btn {
                font-size: 11px;
                padding: 6px 8px;
            }
        }

        /* Animações */
        .card-inscricao {
            transition: var(--transition);
        }

        .card-inscricao:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <?php include "menu/add_menu.php"; ?>
    <?php include "include_hamburger.php"; ?>

    <div class="container">
        <h1 class="page-title">Meus Campeonatos Inscritos</h1>

        <?php if (isset($_SESSION['sucesso'])): ?>
            <div class="alert-message success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['sucesso']); ?>
                <?php unset($_SESSION['sucesso']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['erro'])): ?>
            <div class="alert-message error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['erro']); ?>
                <?php unset($_SESSION['erro']); ?>
            </div>
        <?php elseif ($erro): ?>
            <div class="alert-message error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($inscritos)): ?>
            <div class="nenhuma-inscricao">
                <i class="fas fa-calendar-times"></i>
                <h3>Nenhuma inscrição encontrada</h3>
                <p>Você não está inscrito em nenhum campeonato no momento.</p>
                <a href="eventos.php" class="botao-acao">
                    <i class="fas fa-trophy"></i> Ver Campeonatos Disponíveis
                </a>
            </div>
        <?php else: ?>
            <!-- Versão Cards para Mobile -->
            <div class="cards-inscricoes">
                <?php foreach ($inscritos as $inscrito) {
                    if (!is_object($inscrito)) continue;

                    $statusPagamento = $inscrito->status_pagamento ?? 'PENDENTE';
                    $cobrancaId = $inscrito->assas ?? null;
                    $valorPago = isset($inscrito->valor_pago) ? 'R$ ' . number_format($inscrito->valor_pago, 2, ',', '.') : '--';

                    if ($cobrancaId) {
                        try {
                            $statusInfo = $asaasService->verificarStatusCobranca($cobrancaId);
                            $statusPagamento = $statusInfo['traduzido'];
                        } catch (Exception $e) {
                            $statusPagamento = 'ERRO';
                        }
                    }

                    $statusClass = 'status-pendente';
                    $statusIcon = 'fa-clock';

                    switch (strtoupper($statusPagamento)) {
                        case 'PAGO':
                        case 'RECEIVED':
                            $statusClass = 'status-pago';
                            $statusIcon = 'fa-check-circle';
                            break;
                        case 'PENDENTE':
                        case 'PENDING':
                            $statusClass = 'status-pendente';
                            $statusIcon = 'fa-clock';
                            break;
                        default:
                            $statusClass = 'status-erro';
                            $statusIcon = 'fa-exclamation-circle';
                    }

                    // Processar modalidades para mobile
                    $modalidades_p = "";
                    !empty($inscrito->mcom) ? $modalidades_p .= "C. Com<br>" : "";
                    !empty($inscrito->msem) ? $modalidades_p .= "C. Sem<br>" : "";
                    !empty($inscrito->macom) ? $modalidades_p .= "C.+Abs. Com<br>" : "";
                    !empty($inscrito->masem) ? $modalidades_p .= "C.+Abs. Sem<br>" : "";
                    ?>
                    <div class="card-inscricao">
                        <div class="card-header">
                            <div class="card-title">
                                <?= htmlspecialchars($inscrito->campeonato ?? ''); ?>
                            </div>
                            <span class="card-status <?= $statusClass; ?>">
                                <i class="fas <?= $statusIcon; ?>"></i> <?= htmlspecialchars($statusPagamento); ?>
                            </span>
                        </div>
                        
                        <div class="card-details">
                            <div class="card-detail">
                                <span class="card-label">Nº Inscrição:</span>
                                <span class="card-value"><?= htmlspecialchars($_SESSION["id"] . ($inscrito->idC ?? '')); ?></span>
                            </div>
                            <div class="card-detail">
                                <span class="card-label">Data:</span>
                                <span class="card-value"><?= isset($inscrito->dia) ? date('d/m/Y', strtotime($inscrito->dia)) : ''; ?></span>
                            </div>
                            <div class="card-detail">
                                <span class="card-label">Categoria:</span>
                                <span class="card-value"><?= htmlspecialchars($inscrito->modalidade ?? ''); ?></span>
                            </div>
                            <div class="card-detail">
                                <span class="card-label">Modalidades:</span>
                                <span class="card-value modalidade-cell"><?= $modalidades_p; ?></span>
                            </div>
                            <div class="card-detail">
                                <span class="card-label">Valor:</span>
                                <span class="card-value"><?= htmlspecialchars($valorPago); ?></span>
                            </div>
                        </div>

                        <div class="card-actions">
                            <?php if ($cobrancaId): ?>
                                <a href="pagamento.php?cobranca_id=<?= htmlspecialchars($cobrancaId); ?>" class="btn btn-visualizar">
                                    <i class="fas fa-eye"></i> Ver Pagamento
                                </a>
                            <?php else: ?>
                                <a href="pagamento.php?evento_id=<?= (int) ($inscrito->idC ?? 0); ?>" class="btn btn-pagar">
                                    <i class="fas fa-money-bill-wave"></i> Pagar
                                </a>
                            <?php endif; ?>
                            <a href="inscricao.php?inscricao=<?= (int) ($inscrito->idC ?? 0); ?>" class="btn btn-editar">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="eventos.php?id=<?= (int) ($inscrito->idC ?? 0); ?>" class="btn btn-visualizar">
                                <i class="fas fa-info-circle"></i> Detalhes
                            </a>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <!-- Versão Tabela para Desktop -->
            <div class="table-responsive">
                <div class="scroll-hint">
                    <i class="fas fa-arrows-left-right"></i> Arraste para ver mais colunas
                </div>
                <table class="inscricoes-table">
                    <thead>
                        <tr>
                            <th>Nº Inscrição</th>
                            <th>Campeonato</th>
                            <th>Local</th>
                            <th>Data</th>
                            <th>Categoria</th>
                            <th>Modalidade</th>
                            <th>Status Pagamento</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inscritos as $inscrito) {
                            if (!is_object($inscrito)) continue;

                            $statusPagamento = $inscrito->status_pagamento ?? 'PENDENTE';
                            $cobrancaId = $inscrito->assas ?? null;
                            $valorPago = isset($inscrito->valor_pago) ? 'R$ ' . number_format($inscrito->valor_pago, 2, ',', '.') : '--';

                            if ($cobrancaId) {
                                try {
                                    $statusInfo = $asaasService->verificarStatusCobranca($cobrancaId);
                                    $statusPagamento = $statusInfo['traduzido'];
                                } catch (Exception $e) {
                                    $statusPagamento = 'ERRO';
                                }
                            }

                            $statusClass = 'status-pendente';
                            $statusIcon = 'fa-clock';

                            switch (strtoupper($statusPagamento)) {
                                case 'PAGO':
                                case 'RECEIVED':
                                    $statusClass = 'status-pago';
                                    $statusIcon = 'fa-check-circle';
                                    break;
                                case 'PENDENTE':
                                case 'PENDING':
                                    $statusClass = 'status-pendente';
                                    $statusIcon = 'fa-clock';
                                    break;
                                default:
                                    $statusClass = 'status-erro';
                                    $statusIcon = 'fa-exclamation-circle';
                            }

                            // Processar modalidades para desktop
                            $modalidades_p = "";
                            !empty($inscrito->mcom) ? $modalidades_p .= "Categoria Com Quimono<br>" : "";
                            !empty($inscrito->msem) ? $modalidades_p .= "Categoria Sem Quimono<br>" : "";
                            !empty($inscrito->macom) ? $modalidades_p .= "Categoria + Absoluto Com Quimono<br>" : "";
                            !empty($inscrito->masem) ? $modalidades_p .= "Categoria + Absoluto Sem Quimono<br>" : "";
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($_SESSION["id"] . ($inscrito->idC ?? '')); ?></td>
                                <td>
                                    <a href="eventos.php?id=<?= (int) ($inscrito->idC ?? 0); ?>" class="link">
                                        <?= htmlspecialchars($inscrito->campeonato ?? ''); ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($inscrito->lugar ?? ''); ?></td>
                                <td><?= isset($inscrito->dia) ? date('d/m/Y', strtotime($inscrito->dia)) : ''; ?></td>
                                <td><?= htmlspecialchars($inscrito->modalidade ?? ''); ?></td>
                                <td class="modalidade-cell"><?= $modalidades_p; ?></td>
                                <td>
                                    <span class="status <?= $statusClass; ?>">
                                        <i class="fas <?= $statusIcon; ?>"></i> <?= htmlspecialchars($statusPagamento); ?>
                                    </span>
                                    <span class="valor-pago"><?= htmlspecialchars($valorPago); ?></span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <?php if ($cobrancaId): ?>
                                            <a href="pagamento.php?cobranca_id=<?= htmlspecialchars($cobrancaId); ?>" class="btn btn-visualizar">
                                                <i class="fas fa-eye"></i> Ver Pagamento
                                            </a>
                                        <?php else: ?>
                                            <a href="pagamento.php?evento_id=<?= (int) ($inscrito->idC ?? 0); ?>" class="btn btn-pagar">
                                                <i class="fas fa-money-bill-wave"></i> Pagar
                                            </a>
                                        <?php endif; ?>
                                        <a href="inscricao.php?inscricao=<?= (int) ($inscrito->idC ?? 0); ?>" class="btn btn-editar">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php include "menu/footer.php"; ?>
</body>

</html>