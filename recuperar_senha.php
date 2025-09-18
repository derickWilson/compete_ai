<?php
session_start();
if (isset($_SESSION["logado"]) && $_SESSION["logado"]) {
    header("Location: pagina_pessoal.php");
    exit();
}

require_once "classes/atletaService.php";
require_once "func/clearWord.php";

// Incluir e configurar PHPMailer
require 'classes/PHPMailer/src/Exception.php';
require 'classes/PHPMailer/src/PHPMailer.php';
require 'classes/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarEmailRecuperacaoSMTP($destinatario, $codigo) {
    $mail = new PHPMailer(true);
    
    try {
        // Configurações do servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'mail.fpjji.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'wsaazizbjj@fpjji.com';
        $mail->Password = 'SUA_SENHA_AQUI'; // ATENÇÃO: Substitua pela senha real
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        
        // Remetente e destinatário
        $mail->setFrom('wsaazizbjj@fpjji.com', 'FPJJI');
        $mail->addAddress($destinatario);
        $mail->addReplyTo('wsaazizbjj@fpjji.com', 'FPJJI');
        
        // Conteúdo do email
        $mail->isHTML(true);
        $mail->Subject = 'Recuperação de Senha FPJJI';
        $mail->Body = "
            <h2>Recuperação de Senha</h2>
            <p>Você solicitou a recuperação de senha. Use o código abaixo para continuar:</p>
            <div style='font-size: 24px; font-weight: bold; margin: 20px 0;'>$codigo</div>
            <p>Este código é válido por 30 minutos.</p>
            <p>Caso não tenha solicitado esta alteração, ignore este email.</p>
        ";
        
        // Enviar email
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log('Erro ao enviar e-mail: ' . $mail->ErrorInfo);
        return false;
    }
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

        // Envio de email usando PHPMailer
        if (enviarEmailRecuperacaoSMTP($email, $codigo)) {
            $mensagem = "Um código de recuperação foi enviado para seu email:<br>
            Lembre de Conferir a Caixa de Spam<br>";
        } else {
            error_log("FALHA NO ENVIO DE EMAIL - Email: " . $email . ", Código: " . $codigo . ", Data: " . date('Y-m-d H:i:s'));
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