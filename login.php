<?php
session_start();
if (!isset($_SESSION["logado"]) || !$_SESSION["logado"]) {
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logar</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="/estilos/icone.jpeg">
</head>
<body>
    <?php include "menu/add_menu.php"; ?>
    <h1>Logar</h1>
    <div class="principal">
        <form action="logar.php" method="post">
            <?php if(isset($_GET["erro"]) && $_GET["erro"] == 3): ?>
                <div class="erro">Senha inválida</div>
            <?php endif; ?>
            
            <label for="usuario">Email</label>
            <input type="email" name="usuario" id="usuario" required><br>
            
            <label for="senha">Senha</label>
            <input type="password" name="senha" id="senha" required>
            
            <input type="submit" value="Logar">
            
            <p class="link-recuperacao">
                <a href="recuperar_senha.php">Esqueci minha senha</a>
            </p>
        </form>
    </div>
    <?php include "menu/footer.php"; ?>
</body>
</html>
<?php
} else {
    header('Location: index.php');
}
?>