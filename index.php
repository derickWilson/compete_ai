<?php
    session_start();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FPJJI</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php
        include "menu/add_menu.php";
    ?>
<hr>
<div>
    <?php
        if(isset($_GET["erro"]) && $_GET["erro"] == 2){
            // no caso do erro 2 mostra que a conta não foi validada
            echo "<h3 class='alert' >Sua Conta Ainda Não Foi Validada</h3>";
        }
    ?>
</div>
<hr>
    <footer>todos os direitos reservados</footer>
</body>
</html>
