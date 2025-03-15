<?php
session_start();
if (!isset($_SESSION["logado"])){
    header("Location: index.php");
    exit();
}else{
    require_once "classes/atletaService.php";
    try {
        // Obtenha os inscritos
        $conn = new Conexao();
        $atleta = new Atleta();
        $ev = new atletaService($conn, $atleta);
        $inscritos = $ev->listarCampeonatos($_SESSION["id"]);
        echo "<pre>";
        print_r($inscritos);
        echo "</pre>";
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
</head>
<body>
<?php
        include "menu/add_menu.php";
    ?>
<h3>Campeonato Inscritos</h3>
<div class="principal">
<table border="1">
    <tr>
        <th>Campeonato</th>
        <th>Local</th>
        <th>Data</th>
        <th>Modalidade</th>
        <th>Com Quimono</th>
        <th>Sem Quimono</th>
        <th>Absoluto sem Quimono</th>
        <th>Absoluto com Quimono</th>
        <th>ver</th>
    </tr>
    <?php foreach ($inscritos as $key => $inscrito) { ?>
    <tr>
        <?php
        echo '<td><h5><a href="eventos.php?id=' . (int)$inscrito->idC . '">' . $inscrito->campeonato . '</a></h5></td>';        ?>
        <td><h5><?php echo $inscrito->lugar; ?></h5></td>
        <td><h5><?php echo $inscrito->dia; ?></h5></td>
        <td><h5><?php echo $inscrito->modalidade; ?></h5></td>
        <td><h5><?php echo $inscrito->mcom ? "X": ""; ?></h5></td>
        <td><h5><?php echo $inscrito->msem ? "X": ""; ?></h5></td>
        <td><h5><?php echo $inscrito->macom ? "X": ""; ?></h5></td>
        <td><h5><?php echo $inscrito->masem ? "X": ""; ?></h5</td>
        <td><h5><a href="inscricao.php?inscricao=<?php echo htmlspecialchars($inscrito->idC)?>">editar</a></h5</td>
    </tr>
    <?php } ?>
</table>
</div>
<?php
include "menu/footer.php";
?></body>
</html>