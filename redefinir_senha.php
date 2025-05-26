<?php
session_start();
if (!isset($_SESSION['recuperacao_senha']) || !isset($_SESSION['recuperacao_senha']['codigo_verificado'])) {
    header("Location: recuperar_senha.php");
    exit();
}

require_once "classes/atletaService.php";
require_once "func/clearWord.php";

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $senha = cleanWords($_POST["senha"]);
    $confirmar = cleanWords($_POST["confirmar"]);
    
    if ($senha !== $confirmar) {
        $mensagem = "As senhas não coincidem";
    } else {
        try {
            $conn = new Conexao();
            $atleta = new Atleta();
            $attServ = new atletaService($conn, $atleta);
            
            if ($attServ->redefinirSenha($senha)) {
                $mensagem = "Senha redefinida com sucesso! Redirecionando para login...";
                header("Refresh: 3; url=login.php");
            }
        } catch (Exception $e) {
            $mensagem = "Erro: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Senha</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include "menu/add_menu.php"; ?>
    
    <div class="principal">
        <h1>Definir Nova Senha</h1>
        
        <?php if ($mensagem): ?>
            <div class="mensagem <?php echo strpos($mensagem, 'sucesso') !== false ? 'sucesso' : 'erro'; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php else: ?>
            <form method="post">
                <label for="senha">Nova Senha:</label>
                <input type="password" name="senha" id="senha" required>
                
                <label for="confirmar">Confirmar Senha:</label>
                <input type="password" name="confirmar" id="confirmar" required>
                
                <input type="submit" value="Redefinir Senha">
            </form>
        <?php endif; ?>
    </div>
    
    <?php include "menu/footer.php"; ?>
</body>
</html>