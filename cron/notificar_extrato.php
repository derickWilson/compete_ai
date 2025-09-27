<?php
require_once __DIR__ . "/../classes/AssasService.php";
require_once __DIR__ . "/../classes/eventosServices.php";
require_once __DIR__ . "/../func/database.php";
require_once __DIR__ . "/../emails/email.php";

// Incluir e configurar PHPMailer
require __DIR__ . '/../classes/PHPMailer/src/Exception.php';
require __DIR__ . '/../classes/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../classes/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Configurar logging
$log_file = __DIR__ . '/notificacoes_cobranca_log_' . date('Y-m-d') . '.txt';
file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Iniciando processamento\n", FILE_APPEND);

function enviarEmailCobrancaSMTP($destinatario, $assunto, $mensagem) {
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
        $mail->setFrom('wsaazizbjj@fpjji.com', 'FPJJI');
        $mail->addAddress($destinatario);
        $mail->addReplyTo('wsaazizbjj@fpjji.com', 'FPJJI');
        
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
        error_log('Erro ao enviar e-mail de cobrança: ' . $mail->ErrorInfo);
        return false;
    }
}

try {
    $conn = new Conexao();
    $evento = new Evento();
    $ev = new eventosService($conn, $evento);

    // Obter eventos
    $eventos = $ev->listAll();
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Encontrados " . count($eventos) . " eventos\n", FILE_APPEND);

    $data_atual = new DateTime();
    $notificacoes_enviadas = 0;

    foreach ($eventos as $evento) {
        $evento_detalhes = $ev->getById($evento->id);

        if (empty($evento_detalhes->data_evento)) {
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Evento {$evento->id} sem data, ignorando\n", FILE_APPEND);
            continue;
        }

        //Ignorar eventos que já passaram
        $data_evento = new DateTime($evento_detalhes->data_evento);
        if ($data_evento < $data_atual) {
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Evento {$evento->id} já passou ({$evento_detalhes->data_evento}), ignorando\n", FILE_APPEND);
            continue;
        }

        $inscritos_evento = $ev->getInscritos($evento->id);
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Evento {$evento->id} - Encontrados " . count($inscritos_evento) . " inscritos\n", FILE_APPEND);

        foreach ($inscritos_evento as $inscrito) {
            // Verificar se tem permissão para e-mail (campo padrão é 1 se não existir)
            $permissao_email = property_exists($inscrito, 'permissao_email') ? $inscrito->permissao_email : 1;

            if ($inscrito->status_pagamento == "PENDING" && $permissao_email == 1) {
                $mensagem = obter_mensagem_base($evento_detalhes->nome, $inscrito->id, "cobranca_lembrete", 0);

                if (!empty($mensagem)) {
                    $assunto = "Lembrete: " . $evento_detalhes->nome . " - Pagamento Pendente";

                    if (enviarEmailCobrancaSMTP($inscrito->email, $assunto, $mensagem)) {
                        // Aguardar o tempo de 5 segundos para proxima entrega
                        usleep((5 * 1000000));
                        $notificacoes_enviadas++;
                        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] E-mail enviado para: {$inscrito->email} (Evento: {$evento->id})\n", FILE_APPEND);
                    } else {
                        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Falha ao enviar para: {$inscrito->email}\n", FILE_APPEND);
                    }
                }
            }
        }
    }

    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Processamento concluído. {$notificacoes_enviadas} notificações enviadas\n", FILE_APPEND);

} catch (Exception $e) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] ERRO SE NOTIFICACAO DE COBRANÇA: " . $e->getMessage() . "\n", FILE_APPEND);
}
?>