<?php
session_start();
if (!isset($_SESSION["logado"])){
    header("Location: index.php");
    exit();
}else{
    require_once "classes/atletaService.php";
    try {
        $conn = new Conexao();
        $atleta = new Atleta();
        $ev = new atletaService($conn, $atleta);
        $inscritos = $ev->listarCampeonatos($_SESSION["id"]);
    } catch (Exception $e) {
        die("Erro ao obter inscritos: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
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
        }
    </style>
</head>
<body>
<?php include "menu/add_menu.php"; ?>
<h3>Campeonato Inscritos</h3>
<div class="principal">
<table border="1">
    <tr>
        <th>Nº de inscricao</th>
        <th>Campeonato</th>
        <th>Local</th>
        <th>Data</th>
        <th>Modalidade</th>
        <th>Com Quimono</th>
        <th>Sem Quimono</th>
        <th>Absoluto sem Quimono</th>
        <th>Absoluto com Quimono</th>
        <th>Status Pagamento</th>
        <th>Ações</th>
    </tr>
    <?php foreach ($inscritos as $key => $inscrito) { 
        $statusPagamento = $ev->verificarStatusPagamento($_SESSION["id"], $inscrito->idC);
    ?>
    <tr>
        <td><h5><?php echo $_SESSION["id"].$inscrito->idC; ?></h5></td>
        <td><h5><a href="eventos.php?id=<?php echo (int)$inscrito->idC; ?>"><?php echo $inscrito->campeonato; ?></a></h5></td>
        <td><h5><?php echo $inscrito->lugar; ?></h5></td>
        <td><h5><?php echo $inscrito->dia; ?></h5></td>
        <td><h5><?php echo $inscrito->modalidade; ?></h5></td>
        <td><h5><?php echo $inscrito->mcom ? "X": ""; ?></h5></td>
        <td><h5><?php echo $inscrito->msem ? "X": ""; ?></h5></td>
        <td><h5><?php echo $inscrito->macom ? "X": ""; ?></h5></td>
        <td><h5><?php echo $inscrito->masem ? "X": ""; ?></h5></td>
        <td class="<?php echo $statusPagamento == 'PAGO' ? 'pago' : 'pendente'; ?>">
            <h5><?php echo $statusPagamento; ?></h5>
        </td>
        <td>
            <?php if ($statusPagamento != 'PAGO'): ?>
                <a href="pagamento.php?evento=<?php echo $inscrito->idC; ?>&inscricao=<?php echo $_SESSION["id"].$inscrito->idC; ?>" class="btn-pagar">Pagar</a>
            <?php endif; ?>
            <a href="inscricao.php?inscricao=<?php echo htmlspecialchars($inscrito->idC); ?>">Editar</a>
        </td>
    </tr>
    <?php } ?>
</table>
</div>
<?php include "menu/footer.php"; ?>
</body>
</html>