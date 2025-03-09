<?php
session_start();
if(!$_SESSION["logado"]){  
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logar</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php
    include "menu/add_menu.php";
    ?>
    <h1>Logar</h1>
    <div class="principal">
        <form action="logar.php" method="post">
            <?php 
            if(isset($_GET["erro"]) && $_GET["erro"] == 1){
                echo "Usuário Inválido<br>";
            }
            ?>
            <label for="usuario" >Email</label>
            <input type="email" name="usuario" id="usuario" required><br>
            <label for="senha">Senha</label>
            <input type="password" name="senha" id="senha" required><br>
            <input type="submit" value="Logar">
        </form><br>
        <a href="index.php">voltar</a>
    </div>
    <?php
    include "menu/footer.php";
    ?></body>
</html>
<?php
}else{
    header('Location: index.php');
}
?>