<?php
session_start();
if (!isset($_SESSION["logado"])){
    header("Location: index.php");
    exit();
}else{
    include __DIR__ . "/../classes/atletaService.php";
    
    try {
        // Obtenha os inscritos
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
    <title>Campeonatos cadastrados</title>
</head>
<body>
<h3>Inscritos no Evento <?php echo htmlspecialchars($inscritos[0]->evento); ?></h3>
<table border="1">
    <tr>
        <th>Campeonato</th>
        <th>Com Quimono</th>
        <th>Sem Quimono</th>
        <th>Absoluto sem Quimono</th>
        <th>Absoluto com Quimono</th>
    </tr>
    <?php foreach ($inscritos as $inscrito) { ?>
    <tr>
        <td><?php echo htmlspecialchars($inscrito->camp); ?></td>
        <td><?php echo calcularIdade($inscrito->data_nascimento); ?></td>
        <td><?php echo $inscrito->mod_com ? "X" : ""; ?></td>
        <td><?php echo $inscrito->mod_sem ? "X" : ""; ?></td>
        <td><?php echo $inscrito->mod_ab_com ? "X" : ""; ?></td>
        <td><?php echo $inscrito->mod_ab_sem ? "X" : ""; ?></td>
    </tr>
    <?php } ?>
</table>
?>
</body>
</html>