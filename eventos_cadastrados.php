<?php
session_start();
if (!isset($_SESSION["logado"])){
    header("Location: index.php");
    exit();
}

require_once "classes/atletaService.php";
require_once "classes/AssasService.php";
require_once "func/database.php";

try {
    $conn = new Conexao();
    $atleta = new Atleta();
    $atletaService = new atletaService($conn, $atleta);
    $asaasService = new AssasService($conn);
    
    $inscritos = $atletaService->listarCampeonatos($_SESSION["id"]);
} catch (Exception $e) {
    die("Erro ao obter inscritos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campeonatos cadastrados</title>
    <style>
        .pago { color: green; font-weight: bold; }
        .pendente { color: orange; font-weight: bold; }
        .btn-pagar {
            background-color: #4CAF50;
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            margin-right: 5px;
        }
    </style>
</head>
<body>
<?php include "menu/add_menu.php"; ?>
<h3>Campeonatos Inscritos</h3>
<div class="principal">
<table border="1">
    <tr>
        <th>Nº Inscrição</th>
        <th>Campeonato</th>
        <th>Local</th>
        <th>Data</th>
        <th>Modalidade</th>
        <th>Com Quimono</th>
        <th>Sem Quimono</th>
        <th>Absoluto c/ Quimono</th>
        <th>Absoluto s/ Quimono</th>
        <th>Status</th>
        <th>Ações</th>
    </tr>
    <?php foreach ($inscritos as $inscrito) { 
        $statusPagamento = $inscrito->status_pagamento ?? 'PENDENTE';
        $cobrancaId = $inscrito->id_cobranca_asaas ?? null;
        
        // Se existir cobrança, verifica status atualizado
        if ($cobrancaId) {
            try {
                $statusInfo = $asaasService->verificarStatusCobranca($cobrancaId);
                $statusPagamento = $statusInfo['traduzido'];
            } catch (Exception $e) {
                $statusPagamento = 'ERRO';
            }
        }
    ?>
    <tr>
        <td><?= $_SESSION["id"].$inscrito->idC ?></td>
        <td><a href="eventos.php?id=<?= (int)$inscrito->idC ?>"><?= $inscrito->campeonato ?></a></td>
        <td><?= $inscrito->lugar ?></td>
        <td><?= $inscrito->dia ?></td>
        <td><?= $inscrito->modalidade ?></td>
        <td><?= $inscrito->mcom ? "X" : "" ?></td>
        <td><?= $inscrito->msem ? "X" : "" ?></td>
        <td><?= $inscrito->macom ? "X" : "" ?></td>
        <td><?= $inscrito->masem ? "X" : "" ?></td>
        <td class="<?= $statusPagamento == 'PAGO' ? 'pago' : 'pendente' ?>">
            <?= $statusPagamento ?>
        </td>
        <td>
            <?php if ($statusPagamento != 'PAGO'): ?>
                <?php if ($cobrancaId): ?>
                    <a href="pagamento.php?cobranca_id=<?= $cobrancaId ?>" class="btn-pagar">Pagar</a>
                <?php else: ?>
                    <a href="gerar_cobranca.php?evento_id=<?= $inscrito->idC ?>&atleta_id=<?= $_SESSION["id"] ?>" class="btn-pagar">Gerar Pagamento</a>
                <?php endif; ?>
            <?php endif; ?>
            <a href="inscricao.php?inscricao=<?= $inscrito->idC ?>">Editar</a>
        </td>
    </tr>
    <?php } ?>
</table>
</div>
<?php include "menu/footer.php"; ?>
</body>
</html>