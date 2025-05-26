<?php
session_start();
if (!isset($_SESSION['recuperacao_senha'])) {
    header("Location: recuperar_senha.php");
    exit();
}

require_once "classes/atletaService.php";
require_once "func/clearWord.php";

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codigo = cleanWords($_POST["codigo"]);
    
    try {
        $conn = new Conexao();
        $atleta = new Atleta();
        $attServ = new atletaService($conn, $atleta);
        
        if ($attServ->verificarCodigoRecuperacao($codigo)) {
            header("Location: redefinir_senha.php");
            exit();
        }
    } catch (Exception $e) {
        $mensagem = "Erro: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Código</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include "menu/add_menu.php"; ?>
    
    <div class="principal">
        <h1>Verificar Código</h1>
        
        <?php if ($mensagem): ?>
            <div class="mensagem erro"><?php echo $mensagem; ?></div>
        <?php endif; ?>
        
        <p>Digite o código de 6 dígitos que você recebeu por email:</p>
        
        <form method="post">
            <input type="text" name="codigo" pattern="\d{6}" title="6 dígitos numéricos" required>
            <input type="submit" value="Verificar Código">
        </form>
        
        <p><a href="recuperar_senha.php">Solicitar novo código</a></p>
    </div>
    
    <?php include "menu/footer.php"; ?>
</body>
</html>