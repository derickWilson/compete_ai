<?php
session_start();
if (isset($_SESSION["logado"]) && $_SESSION["logado"]) {
    header("Location: pagina_pessoal.php");
    exit();
}

require_once "classes/atletaService.php";
require_once "func/clearWord.php";
function enviarEmailRecuperacao($destinatario, $codigo)
{
    $assunto = "Recuperação de Senha - Seu Site";
    $mensagem = "
    <html>
    <head>
        <title>Recuperação de Senha</title>
    </head>
    <body>
        <h2>Recuperação de Senha</h2>
        <p>Você solicitou a recuperação de senha. Use o código abaixo para continuar:</p>
        <div style='font-size: 24px; font-weight: bold; margin: 20px 0;'>$codigo</div>
        <p>Este código é válido por 30 minutos.</p>
        <p>Caso não tenha solicitado esta alteração, ignore este email.</p>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: fpjji.com\r\n";
    $headers .= "Reply-To: fpjjioficial@gmail.com\r\n";

    return mail($destinatario, $assunto, $mensagem, $headers);
}
$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = cleanWords($_POST["email"]);

    try {
        $conn = new Conexao();
        $atleta = new Atleta();
        $atleta->__set("email", $email);
        $attServ = new atletaService($conn, $atleta);

        $codigo = $attServ->gerarCodigoRecuperacao($email);

        //envio de email
        if (enviarEmailRecuperacao($email, $codigo)) {
            $mensagem = "Um código de recuperação foi enviado para seu email:<br>";
        } else {
            throw new Exception("Falha ao enviar email. Tente novamente mais tarde.");
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
    <title>Recuperar Senha</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php include "menu/add_menu.php"; ?>

    <div class="principal">
        <h1>Recuperar Senha</h1>

        <?php if ($mensagem): ?>
            <div class="mensagem"><?php echo $mensagem; ?></div>
            <?php if (strpos($mensagem, "enviado") !== false): ?>
                <p><a href="verificar_codigo.php">Ir para verificação do código</a></p>
            <?php endif; ?>
        <?php else: ?>
            <form method="post">
                <label for="email">Digite seu email cadastrado:</label>
                <input type="email" name="email" id="email" required>

                <input type="submit" value="Enviar Código">
            </form>
        <?php endif; ?>

        <p><a href="login.php">Voltar para o login</a></p>
    </div>

    <?php include "menu/footer.php"; ?>
</body>

</html>