<?php
    session_start();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compete Ai</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
    <?php
        include "menu/add_menu.php";
    ?>
    <h1>Bem Vindo ao fpjji</h1>
    </header>
<hr>
<div>
    <?php
        if(isset($_GET["erro"]) && $_GET["erro"] == 2){// no caso do erro 2 mostra que a conta não foi validada
    ?>
            <h3>Sua Conta Ainda Não Foi Validada</h3>
    <?php
        }
    ?>
</div>
<hr>
    <footer>todos os direitos reservados</footer>
</body>
</html>
