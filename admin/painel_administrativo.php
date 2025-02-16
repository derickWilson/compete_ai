<?php
//session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "../func/is_adm.php";
is_adm();

include_once "../classes/atletaClass.php";
include_once "../classes/atletaService.php";

try {
    $con = new Conexao();
    $at = new Atleta();
    $attServ = new atletaService($con, $at);
    
    $lista = $attServ->listInvalido();
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
    exit();
}

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style.css">
    <title>Painel Administrativo</title>
</head>
<body>
    <?php include "../menu/add_menu.php"; ?>
    <div class="principal">
        <?php

        ?>
        <table>
            <tr>
                <th>Nome</th>
                <th>Faixa</th>
                <th>Academia</th>
                <th>Validado</th>
            </tr>
            <?php 
                foreach ($lista as $key => $value) {
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($value->nome); ?></td>
                        <td><?php echo htmlspecialchars($value->faixa); ?></td>
                        <td><?php echo htmlspecialchars($value->academia); ?></td>
                        <td><a href="controle.php?user=<?php echo htmlspecialchars($value->id); ?>">ver</a></td>
                    </tr>
                <?php } ?>
        </table>
    </div>
</body>
</html>
