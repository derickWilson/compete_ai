<?php
use function PHPSTORM_META\type;
session_start();
require "../func/is_adm.php";
is_adm();
require_once __DIR__ . "/../classes/eventosServices.php";
require_once __DIR__ . "/../func/calcularIdade.php";
require_once __DIR__ . "/../func/clearWord.php";

if (!isset($_GET['id'])) {
    exit();
}

$idEvento = cleanWords($_GET['id']);
try {
    $conn = new Conexao();
    $evento = new Evento();
    $ev = new eventosService($conn, $evento);
    $inscritos = $ev->getInscritos($idEvento);
} catch (Exception $e) {
    die("Erro ao obter inscritos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/style.css">
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
        .status-outros {
            color: #666;
        }
    </style>
</head>
<body>
<?php include "../menu/menu_admin.php"; ?>
<h1>Inscritos no Evento</h1>
<?php if ($inscritos && !empty($inscritos)) { ?>
    <h3>Inscritos no Evento <?php echo htmlspecialchars($inscritos[0]->evento); ?></h3>
    <table border="1">
        <tr>
            <th>Inscrição</th>
            <th>Nome do Atleta</th>
            <th>Idade</th>
            <th>Faixa</th>
            <th>Peso</th>
            <th>Modalidade</th>
            <th>Academia</th>
            <th>Modo Com</th>
            <th>Modo Sem</th>
            <th>Abst. Com</th>
            <th>Abst. Sem</th>
            <th>Status Pagamento</th>
        </tr>
        <?php foreach ($inscritos as $inscrito) { 
    $statusClass = 'status-outros';
    if ($inscrito->status_pagamento === 'RECEIVED') {
        $statusClass = 'status-pago';
    } elseif ($inscrito->status_pagamento === 'PENDING') {
        $statusClass = 'status-pendente';
    }
?>
<tr>
    <td><?= $inscrito->id.$inscrito->ide ?></td>
    <td><?= htmlspecialchars($inscrito->inscrito) ?></td>
    <td><?= calcularIdade($inscrito->data_nascimento) ?></td>
    <td><?= htmlspecialchars($inscrito->faixa) ?></td>
    <td><?= htmlspecialchars($inscrito->peso) ?></td>
    <td><?= htmlspecialchars($inscrito->modalidade) ?></td>
    <td><?= htmlspecialchars($inscrito->academia) ?></td>
    <td><?= $inscrito->mod_com ? "Sim" : "Não" ?></td>
    <td><?= $inscrito->mod_sem ? "Sim" : "Não" ?></td>
    <td><?= $inscrito->mod_ab_com ? "Sim" : "Não" ?></td>
    <td><?= $inscrito->mod_ab_sem ? "Sim" : "Não" ?></td>
    <td class="<?= $statusClass ?>">
        <?= $inscrito->status_pagamento === 'RECEIVED' ? 'Pago' : 'Pendente' ?>
        <?php if ($inscrito->status_pagamento === 'RECEIVED'): ?>
            <br>(R$ <?= number_format($inscrito->valor_pago, 2, ',', '.') ?>)
        <?php endif; ?>
    </td>
</tr>
<?php } ?>
    </table>
    <form action="baixar.php" method="GET">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($_GET['id'], ENT_QUOTES, 'UTF-8'); ?>">
        <input type="submit" value="Baixar Planilha">
    </form>
    <br><a href="/compete_ai/eventos.php">Voltar</a>
<?php } else {
    echo "<p>Nenhum inscrito encontrado para este campeonato.</p>";
} 
?>
<?php include "/menu/footer.php"; ?>
</body>
</html>