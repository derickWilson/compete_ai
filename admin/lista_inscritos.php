<?php
session_start();
require "../func/is_adm.php";
is_adm();
require_once __DIR__ . "/../classes/eventosServices.php";
require_once __DIR__ . "/../classes/AssasService.php";
require_once __DIR__ . "/../func/calcularIdade.php";
require_once __DIR__ . "/../func/clearWord.php";
require_once __DIR__ . "/../func/database.php";

// Verifica se o ID do evento foi especificado
if (!isset($_GET['id'])) {
    $_SESSION['erro'] = "ID do evento não especificado";
    header("Location: eventos.php");
    exit();
}

$idEvento = cleanWords($_GET['id']);
try {
    $conn = new Conexao();
    $evento = new Evento();
    $ev = new eventosService($conn, $evento);
    $asaasService = new AssasService($conn);

    // 1. PROCESSAR AÇÕES ADMINISTRATIVAS
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $idAtleta = (int) cleanWords($_POST['id_atleta']);
        $action = cleanWords($_POST['action']);

        try {
            if ($action === 'marcar_pago') {
                $valor = (float) str_replace(',', '.', cleanWords($_POST['valor']));

                $asaasService->atualizarInscricaoComPagamento(
                    $idAtleta,
                    $idEvento,
                    null, // Sem ID de cobrança Asaas (pagamento manual)
                    AssasService::STATUS_PAGO,
                    $valor
                );
                $_SESSION['mensagem'] = "Inscrição marcada como paga com sucesso!";

            } elseif ($action === 'marcar_isento') {
                // Buscar dados da inscrição para ver se tem cobrança no Asaas
                $inscricoes = $ev->getInscritos($idEvento);
                $inscricao = null;
                foreach ($inscricoes as $i) {
                    if ($i->id == $idAtleta) {
                        $inscricao = $i;
                        break;
                    }
                }

                // Se existir cobrança no Asaas, deletar
                if ($inscricao && $inscricao->id_cobranca_asaas) {
                    $resultado = $asaasService->deletarCobranca($inscricao->id_cobranca_asaas);
                    if (!$resultado['success']) {
                        error_log("Falha ao deletar cobrança: " . ($resultado['message'] ?? ''));
                        // Continua mesmo se falhar em deletar a cobrança
                    }
                }

                // Atualizar no banco de dados
                $asaasService->atualizarInscricaoComPagamento(
                    $idAtleta,
                    $idEvento,
                    null, // Remove referência à cobrança
                    AssasService::STATUS_ISENTO,
                    0 // Valor zero para isenção
                );
                $_SESSION['mensagem'] = "Inscrição marcada como isenta com sucesso!";
            }

        } catch (Exception $e) {
            $_SESSION['erro'] = "Erro ao processar ação: " . $e->getMessage();
        }

        header("Location: lista_inscritos.php?id=" . $idEvento);
        exit();
    }

    // 2. ATUALIZAR STATUS DE PAGAMENTOS PENDENTES
    $inscritos = $ev->getInscritos($idEvento);
    foreach ($inscritos as $inscrito) {
        if ($inscrito->id_cobranca_asaas && $inscrito->status_pagamento === 'PENDING') {
            try {
                $statusInfo = $asaasService->verificarStatusCobranca($inscrito->id_cobranca_asaas);

                if (in_array($statusInfo['status'], ['RECEIVED', 'CONFIRMED'])) {
                    $asaasService->atualizarInscricaoComPagamento(
                        $inscrito->id,
                        $idEvento,
                        $inscrito->id_cobranca_asaas,
                        $statusInfo['status'],
                        $inscrito->valor_pago
                    );
                    $inscrito->status_pagamento = $statusInfo['status'];
                }
            } catch (Exception $e) {
                error_log("Erro ao verificar status da cobrança {$inscrito->id_cobranca_asaas}: " . $e->getMessage());
            }
        }
    }

    // Recarregar lista após atualizações
    $inscritos = $ev->getInscritos($idEvento);

} catch (Exception $e) {
    $_SESSION['erro'] = "Erro ao obter inscritos: " . $e->getMessage();
    header("Location: eventos.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/style.css">
    <link rel="icon" href="/estilos/icone.jpeg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Lista de Inscritos</title>
    <style>
        .status-pago {
            color: green;
            font-weight: bold;
        }

        .status-pendente {
            color: orange;
            font-weight: bold;
        }

        .status-confirmado {
            color: blue;
            font-weight: bold;
        }

        .status-isento {
            color: purple;
            font-weight: bold;
        }

        .status-outros {
            color: #666;
        }

        .refresh-btn {
            margin: 10px 0;
            padding: 5px 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .action-form {
            display: inline-block;
            margin: 2px;
        }

        .action-btn {
            padding: 3px 6px;
            margin: 0 2px;
            cursor: pointer;
            border-radius: 3px;
            border: 1px solid #ddd;
        }

        .pago-btn {
            background-color: #4CAF50;
            color: white;
        }

        .isento-btn {
            background-color: #9C27B0;
            color: white;
        }

        .valor-input {
            width: 70px;
            padding: 3px;
            text-align: right;
        }

        .mensagem {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }

        .mensagem.sucesso {
            background-color: #dff0d8;
            color: #3c763d;
        }

        .mensagem.erro {
            background-color: #f2dede;
            color: #a94442;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: var(--white) !important;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            color: var(--dark) !important;
        }

        th {
            background-color: var(--primary-dark) !important;
            color: var(--white) !important;
        }

        /* CORREÇÃO: Garantir que o texto nas células fique sempre com cor escura */
        table td {
            color: var(--dark) !important;
        }

        /* Manter o hover com um efeito sutil */
        tr:hover td {
            background-color: rgba(0, 0, 0, 0.05) !important;
        }

        /* Estilizar os links dentro da tabela */
        table a {
            color: var(--primary) !important;
            text-decoration: none;
        }

        table a:hover {
            color: var(--primary-dark) !important;
            text-decoration: underline;
        }
        
        /* CORREÇÃO: Garantir que os títulos fiquem brancos no fundo azul */
        h1, h3 {
            color: var(--white) !important;
        }
        </style>
</head>

<body>
    <?php include "../menu/menu_admin.php"; ?>
    <div class="container">
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="mensagem sucesso"><?= htmlspecialchars($_SESSION['mensagem']) ?></div>
            <?php unset($_SESSION['mensagem']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['erro'])): ?>
            <div class="mensagem erro"><?= htmlspecialchars($_SESSION['erro']) ?></div>
            <?php unset($_SESSION['erro']); ?>
        <?php endif; ?>

        <h1>Inscritos no Evento</h1>

        <?php if ($inscritos && !empty($inscritos)): ?>
            <h3>Evento: <?= htmlspecialchars($inscritos[0]->evento) ?></h3>

            <button onclick="location.reload()" class="refresh-btn">
                Atualizar Status de Pagamentos
            </button>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Atleta</th>
                        <th>Idade</th>
                        <th>Modalidade</th>
                        <th>Academia</th>
                        <th>Status</th>
                        <th>Valor</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inscritos as $inscrito):
                        $statusClass = 'status-outros';
                        $statusText = $inscrito->status_pagamento;

                        switch ($inscrito->status_pagamento) {
                            case 'RECEIVED':
                                $statusClass = 'status-pago';
                                $statusText = 'Pago';
                                break;
                            case 'PENDING':
                                $statusClass = 'status-pendente';
                                $statusText = 'Pendente';
                                break;
                            case 'CONFIRMED':
                                $statusClass = 'status-confirmado';
                                $statusText = 'Confirmado';
                                break;
                            case 'ISENTO':
                                $statusClass = 'status-isento';
                                $statusText = 'Isento';
                                break;
                        }

                        $valorExibicao = ($inscrito->valor_pago > 0)
                            ? 'R$ ' . number_format($inscrito->valor_pago, 2, ',', '.')
                            : '-';
                        ?>
                        <tr>
                            <td><?= $inscrito->id ?></td>
                            <td><?= htmlspecialchars($inscrito->inscrito) ?></td>
                            <td><?= calcularIdade($inscrito->data_nascimento) ?></td>
                            <td><?= htmlspecialchars($inscrito->modalidade) ?></td>
                            <td><?= htmlspecialchars($inscrito->academia) ?></td>
                            <td class="<?= $statusClass ?>"><?= $statusText ?></td>
                            <td><?= $valorExibicao ?></td>
                            <td>
                                <?php if ($inscrito->id_cobranca_asaas): ?>
                                    <a href="/pagamento.php?cobranca_id=<?= urlencode($inscrito->id_cobranca_asaas) ?>&view=1"
                                        title="Ver detalhes do pagamento">
                                        Detalhes
                                    </a>
                                <?php endif; ?>

                                <?php if ($inscrito->status_pagamento === 'PENDING'): ?>
                                    <form class="action-form" method="POST"
                                        onsubmit="return confirm('Confirmar marcação como PAGO?')">
                                        <input type="hidden" name="id_atleta" value="<?= $inscrito->id ?>">
                                        <input type="hidden" name="action" value="marcar_pago">
                                        <input type="number" name="valor" class="valor-input" step="0.01" min="0"
                                            value="<?= number_format($inscrito->valor_pago ?? 0, 2, '.', '') ?>" required>
                                        <button type="submit" class="action-btn pago-btn" title="Marcar como pago">
                                            Pago
                                        </button>
                                    </form>

                                    <form class="action-form" method="POST"
                                        onsubmit="return confirm('Confirmar isenção? A cobrança será cancelada.')">
                                        <input type="hidden" name="id_atleta" value="<?= $inscrito->id ?>">
                                        <input type="hidden" name="action" value="marcar_isento">
                                        <button type="submit" class="action-btn isento-btn" title="Marcar como isento">
                                            Isento
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        </div>
        <div style="margin-top: 20px;">
            <form action="baixar_chapa.php" method="GET" style="display: inline-block;">
                <input type="hidden" name="id" value="<?= htmlspecialchars($idEvento) ?>">
                <input type="submit" value="Baixar Planilha" class="action-btn">
            </form>

            <a href="eventos.php" style="margin-left: 15px;" class="action-btn">Voltar para Eventos</a>
        </div>
    <?php else: ?>
        <div class="container">
            <p>Nenhum inscrito encontrado para este evento.</p>
            <a href="eventos.php" class="action-btn">Voltar para Eventos</a>

        </div>
    <?php endif; ?>

    <?php include "../menu/footer.php"; ?>

    <script>
        // Atualiza automaticamente a página a cada 2 minutos
        setTimeout(function () {
            location.reload();
        }, 120000);
    </script>
</body>

</html>