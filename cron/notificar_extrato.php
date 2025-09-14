<?php
require_once __DIR__ . "/../classes/AssasService.php";
require_once __DIR__ . "/../classes/eventosServices.php";
require_once __DIR__ . "/../func/database.php";
require_once __DIR__ . "/../emails/email.php";

// Configurar logging
$log_file = __DIR__ . '/notificacoes_cobranca_log_' . date('Y-m-d') . '.txt';
file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Iniciando processamento\n", FILE_APPEND);

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

        $inscritos_evento = $ev->getInscritos($evento->id);
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Encontrados " . count($inscritos_evento) . " inscritos\n", FILE_APPEND);

        foreach ($inscritos_evento as $inscrito) {
            // Verificar se tem permissão para e-mail (campo padrão é 1 se não existir)
            $permissao_email = property_exists($inscrito, 'permissao_email') ? $inscrito->permissao_email : 1;

            if ($inscrito->status_pagamento == "PENDING" && $permissao_email == 1) {
                $mensagem = obter_mensagem_base($evento_detalhes->nome, $inscrito->id, "cobranca_lembrete", $dias_restantes);

                if (!empty($mensagem)) {
                    $headers = "MIME-Version: 1.0\r\n";
                    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                    $headers .= "From: FPJJI <fpjjioficial@gmail.com>\r\n";
                    $headers .= "Reply-To: fpjjioficial@gmail.com\r\n";
                    $headers .= "Return-Path: fpjjioficial@gmail.com\r\n";
                    $headers .= "Message-ID: <" . time() . rand(1, 1000) . "@fpjji.com>\r\n";
                    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
                    $headers .= "X-Priority: 3\r\n";
                    $headers .= "X-MSMail-Priority: Normal\r\n";

                    $assunto = "Lembrete: " . $evento_detalhes->nome . "Pagamento Pendente";

                    if (mail($inscrito->email, $assunto, $mensagem, $headers)) {
                        // Aguardar o tempo de 5 segundos para proxima entrega
                        usleep((5 * 1000000));
                        $notificacoes_enviadas++;
                        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] E-mail enviado para: {$inscrito->email}\n", FILE_APPEND);
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