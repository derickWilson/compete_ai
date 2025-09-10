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
        }

        .table-responsive {
            overflow-x: auto;
            margin-bottom: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            background: var(--white);
        }

        .inscricoes-table {
            width: 100%;
            border-collapse: collapse;
        }

        .inscricoes-table th {
            background-color: var(--primary-dark);
            color: var(--white);
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }

        .inscricoes-table td {
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
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
        }

        .btn-pagar {
            background-color: var(--success);
            color: white;
        }

        .btn-pagar:hover {
            background-color: #218838;
        }

        .btn-visualizar {
            background-color: var(--primary);
            color: white;
        }

        .btn-visualizar:hover {
            background-color: var(--primary-dark);
        }

        .btn-editar {
            background-color: var(--accent);
            color: var(--dark);
        }

        .btn-editar:hover {
            background-color: #d4a017;
        }

        .valor-pago {
            display: block;
            font-size: 12px;
            color: var(--gray);
            margin-top: 4px;
        }

        /* Responsividade para a tabela */
        @media (max-width: 992px) {
            .inscricoes-table {
                font-size: 14px;
            }

            .btn {
                padding: 4px 8px;
                font-size: 12px;
            }
        }

        @media (max-width: 768px) {
            .table-responsive {
                margin: 0 -15px;
                border-radius: 0;
            }

            .inscricoes-table th,
            .inscricoes-table td {
                padding: 8px 6px;
                font-size: 13px;
            }

            /* Mostra apenas colunas essenciais em mobile */
            .inscricoes-table th:nth-child(1),
            .inscricoes-table td:nth-child(1),
            .inscricoes-table th:nth-child(2),
            .inscricoes-table td:nth-child(2),
            .inscricoes-table th:nth-child(10),
            .inscricoes-table td:nth-child(10),
            .inscricoes-table th:nth-child(11),
            .inscricoes-table td:nth-child(11) {
                display: table-cell;
            }

            .inscricoes-table th:nth-child(n+3):nth-child(-n+9),
            .inscricoes-table td:nth-child(n+3):nth-child(-n+9) {
                display: none;
            }

            .btn {
                display: block;
                margin-bottom: 5px;
                text-align: center;
                font-size: 12px;
                padding: 4px 8px;
            }
        }
    </style>
</head>

<body>
    <?php include "menu/add_menu.php"; ?>

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
            <div class="alert-message info">
                <i class="fas fa-info-circle"></i> Você não está inscrito em nenhum campeonato no momento.
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
                            if (!is_object($inscrito))
                                continue;

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

                            $statusClass = 'status-' . strtolower(str_replace(' ', '-', $statusPagamento));
                            $statusIcon = 'fa-question-circle';

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
                            }
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($_SESSION["id"] . ($inscrito->idC ?? '')); ?></td>
                                <td>
                                    <a href="eventos.php?id=<?= (int) ($inscrito->idC ?? 0); ?>">
                                        <?= htmlspecialchars($inscrito->campeonato ?? ''); ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($inscrito->lugar ?? ''); ?></td>
                                <td><?= isset($inscrito->dia) ? date('d/m/Y', strtotime($inscrito->dia)) : ''; ?></td>
                                <td><?= htmlspecialchars($inscrito->modalidade ?? ''); ?></td>
                                <td class="text-center"><?= !empty($inscrito->mcom) ? "X" : ""; ?></td>
                                <td class="text-center"><?= !empty($inscrito->msem) ? "X" : ""; ?></td>
                                <td class="text-center"><?= !empty($inscrito->macom) ? "X" : ""; ?></td>
                                <td class="text-center"><?= !empty($inscrito->masem) ? "X" : ""; ?></td>
                                <td>
                                    <span class="status <?= $statusClass; ?>">
                                        <i class="fas <?= $statusIcon; ?>"></i> <?= htmlspecialchars($statusPagamento); ?>
                                    </span>
                                    <span class="valor-pago"><?= htmlspecialchars($valorPago); ?></span>
                                </td>
                                <td>
                                    <?php if ($cobrancaId): ?>
                                        <a href="pagamento.php?cobranca_id=<?= htmlspecialchars($cobrancaId); ?>"
                                            class="btn btn-visualizar">
                                            <i class="fas fa-eye"></i> Ver Pagamento
                                        </a>
                                    <?php else: ?>
                                        <a href="pagamento.php?evento_id=<?= (int) ($inscrito->idC ?? 0); ?>" class="btn btn-pagar">
                                            <i class="fas fa-money-bill-wave"></i> Pagar
                                        </a>
                                    <?php endif; ?>
                                    <a href="inscricao.php?inscricao=<?= (int) ($inscrito->idC ?? 0); ?>"
                                        class="btn btn-editar">
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