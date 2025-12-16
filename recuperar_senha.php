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


/**
 * Registra logs de envio de email em arquivo de texto
 * 
 * @param string $destinatario Email do destinatário
 * @param string $codigo Código de recuperação enviado
 * @param bool $sucesso Status do envio (true para sucesso, false para falha)
 * @param string $mensagem_erro Mensagem de erro em caso de falha (opcional)
 * @return bool Retorna true se o log foi registrado com sucesso
 */
function registrarLogEmail($destinatario, $codigo, $sucesso, $mensagem_erro = '')
{
    // Configurações do log
    $diretorio_logs = __DIR__ . '/logs/';
    $arquivo_log = $diretorio_logs . 'email_logs.txt';

    // Criar diretório de logs se não existir
    if (!file_exists($diretorio_logs)) {
        mkdir($diretorio_logs, 0755, true);
    }

    // Formatar a mensagem de log
    $data_hora = date('Y-m-d H:i:s');
    $status = $sucesso ? 'SUCESSO' : 'FALHA';
    $mensagem = $sucesso ?
        "Código enviado: $codigo" :
        "Erro: " . (!empty($mensagem_erro) ? $mensagem_erro : 'Desconhecido');

    // Linha do log
    $linha_log = "[$data_hora] $status - Para: $destinatario - $mensagem" . PHP_EOL;

    // Registrar no arquivo de log
    $resultado = file_put_contents($arquivo_log, $linha_log, FILE_APPEND | LOCK_EX);

    return $resultado !== false;
}

function enviarEmailRecuperacaoSMTP($destinatario, $codigo)
{
    $mail = new PHPMailer(true);

    try {
        // Configurações do servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'mail.fpjji.com';
        $mail->SMTPAuth = true;
        $mail->Username = '';
        $mail->Password = '';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        // Remetente e destinatário
        $mail->setFrom('.', 'FPJJI');
        $mail->addAddress($destinatario);
        $mail->addReplyTo('', 'FPJJI');

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
        registrarLogEmail($destinatario, $codigo, true);
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
    <?php include "include_hamburger.php"; ?>

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