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
    session_start();
    include "menu/add_menu.php";
    ?>
    <h1>Logar</h1>
    <div class="container_login">
        <form action="logar.php" method="post">
            <label for="usuario" >Email</label>
            <input type="text" name="usuario" id="usuario" required>
            <label for="senha">Senha</label>
            <input type="password" name="senha" id="senha" required>
            <input type="submit" value="Logar">
        </form>
        <a href="index.php">voltar</a>
    </div>
</body>
</html>
<?php
}else{
    header('Location: index.php');
}
?>