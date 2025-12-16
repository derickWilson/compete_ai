<?php
session_start();
if (isset($_GET['msg']) && $_GET['msg'] === 'faixa_updated') {
    echo '<div class="sucesso">Solicitação de troca de faixa enviada com sucesso! Faça login novamente quando sua faixa for validada pelo administrador.</div>';
}
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
    <?php include "include_hamburger.php"; ?>

    <h1>Logar</h1>
    <div class="principal">
        <form action="logar.php" method="post">
            <?php if(isset($_GET["erro"])): ?>
                <div class="erro">
                    <?php 
                    switch($_GET["erro"]) {
                        case 1:
                            echo "Email não encontrado em nosso sistema.";
                            break;
                        case 2:
                            echo "Por favor, preencha todos os campos obrigatórios.";
                            break;
                        case 3:
                            echo "Senha inválida.";
                            break;
                        case 4:
                            echo "Formato de email inválido.";
                            break;
                        case 5:
                            echo "Erro no sistema. Por favor, tente novamente mais tarde.";
                            break;
                        case 6:
                            echo "Sua conta ainda não foi validada. Aguarde a aprovação do administrador.";
                            break;
                        default:
                            echo "Erro ao realizar login. Tente novamente.";
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET["sucesso"])): ?>
                <div class="sucesso">
                    <?php 
                    switch($_GET["sucesso"]) {
                        case 1:
                            echo "Senha redefinida com sucesso! Faça login com sua nova senha.";
                            break;
                        case 2:
                            echo "Cadastro realizado com sucesso! Aguarde a validação da sua conta.";
                            break;
                        default:
                            echo "Operação realizada com sucesso!";
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <label for="usuario">Email</label>
            <input type="email" name="usuario" id="usuario" required value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>"><br>
            
            <label for="senha">Senha</label>
            <input type="password" name="senha" id="senha" required>
            
            <br><input type="submit" value="Logar">
            
            <p class="link-recuperacao">
                <a href="recuperar_senha.php">Esqueci minha senha</a>
            </p>
            
            <p class="link-cadastro">
                Não tem uma conta? <a href="cadastro_atleta.php">Cadastre-se aqui</a>
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