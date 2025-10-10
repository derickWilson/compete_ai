<?php
require_once __DIR__ . "/../classes/atletaService.php";
require_once __DIR__ . "/../func/database.php";

// Incluir e configurar PHPMailer
require __DIR__ . '/../classes/PHPMailer/src/Exception.php';
require __DIR__ . '/../classes/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../classes/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarEmailNotificacaoSMTP($destinatario, $assunto, $mensagem)
{
    $mail = new PHPMailer(true);

    try {
        // Configurações do servidor SMTP
        $mail->isSMTP();
        $mail->Host = '';
        $mail->SMTPAuth = true;
        $mail->Username = '';
        $mail->Password = '';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Configuração de codificação UTF-8
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        // Remetente e destinatário
        $mail->setFrom('', '');
        $mail->addAddress($destinatario);
        $mail->addReplyTo('', '');

        // Conteúdo do email
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body = $mensagem;

        // Versão alternativa em texto simples
        $mail->AltBody = strip_tags($mensagem);

        // Enviar email
        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('Erro ao enviar e-mail de notificação: ' . $mail->ErrorInfo);
        return false;
    }
}

//notificar de um evento novo
function notificar_geral($id_evento)
{
    // Configurar logging
    $log_file = __DIR__ . '/notificacoes_log_' . date('Y-m-d') . '.txt';
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Iniciando processamento\n", FILE_APPEND);

    try {
        $conn = new Conexao();
        $atleta = new Atleta();
        $at = new atletaService($conn, $atleta);

        // Obter detalhes do evento
        $ev = new Evento();
        $evserv = new eventosService($conn, $ev);
        $evento = $evserv->getById($id_evento);

        if (!$evento) {
            throw new Exception("Evento não encontrado com ID: " . $id_evento);
        }

        // Obter atletas
        $atletas = $at->listAll();
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Encontrados " . count($atletas) . " atletas\n", FILE_APPEND);

        $notificacoes_enviadas = 0;
        foreach ($atletas as $atleta) {
            $msg = '<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FPJJI - Novo Evento Disponível</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f9f9f9;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 20px; border-radius: 5px;">
        <div style="background-color: #2520a0; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;">
            <h1 style="margin: 0; font-size: 24px;">Federação Paulista de Jiu-Jitsu Internacional</h1>
        </div>
        
        <div style="padding: 25px;">
            
            <h2 style="color: #2520a0; font-size: 20px; margin-bottom: 15px; border-bottom: 2px solid #e9b949; padding-bottom: 5px;">🎉 Novo Evento Disponível!</h2>
            
            <p style="margin-bottom: 15px;">Olá <strong style="color: #2520a0;">' . $atleta->nome . '</strong>,</p>
            
            <div style="background-color: #e8f5e8; border-left: 4px solid #4CAF50; padding: 15px; margin: 20px 0;">
                <p style="margin: 0; font-size: 16px; color: #2d5016;">
                    <strong>Um novo evento foi adicionado ao sistema!</strong>
                </p>
                <h3 style="color: #2520a0; margin: 10px 0; font-size: 18px;">' . htmlspecialchars($evento->nome) . '</h3>
                <p style="margin: 5px 0; color: #555;">
                    📅 <strong>Data:</strong> ' . date('d/m/Y', strtotime($evento->data_evento)) . '<br>
                    📍 <strong>Local:</strong> ' . htmlspecialchars($evento->local_camp) . '
                </p>
            </div>
            
            <p style="margin-bottom: 15px;">Não perca esta oportunidade! Confira todos os detalhes e faça sua inscrição:</p>
            
            <div style="text-align: center; margin: 25px 0;">
                <a href="https://fpjji.com/eventos.php?id=' . $id_evento . '" 
                   style="background-color: #4CAF50; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">
                   📋 Ver Detalhes do Evento
                </a>
            </div>
            <p style="margin-bottom: 15px; color: #666; font-size: 14px;">
                <em>Participe</em>
            </p>
        </div>
        
        <div style="background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 5px 5px;">
            <p style="margin: 0 0 10px 0; font-style: italic;">Esta é uma mensagem automática, por favor não responda este e-mail.</p>            
            <p style="margin: 0 0 10px 0;">Caso não queira receber este tipo de notificação, você pode desativar em <a href="https://fpjji.com/edit.php" style="color: #2520a0; text-decoration: none; font-weight: bold;">Editar Dados</a></p>
            <p style="margin: 0 0 10px 0;">© ' . date('Y') . ' FPJJI - Federação Paulista de Jiu-Jitsu Internacional. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>';

            // Enviar email
            $assunto = "Novo Evento: " . htmlspecialchars($evento->nome);
            $enviado = enviarEmailNotificacaoSMTP($atleta->email, $assunto, $msg);

            if ($enviado) {
                $notificacoes_enviadas++;
                file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Notificação enviada para: " . $atleta->email . "\n", FILE_APPEND);
            } else {
                file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] FALHA ao enviar para: " . $atleta->email . "\n", FILE_APPEND);
            }
        }

        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Processamento concluído. {$notificacoes_enviadas} notificações enviadas\n", FILE_APPEND);

    } catch (Exception $e) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}
?>