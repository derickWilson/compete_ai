<?php
require_once __DIR__ . "/../classes/AssasService.php";
require_once __DIR__ . "/../classes/eventosServices.php";
require_once __DIR__ . "/../func/database.php";
require_once __DIR__ . "/../emails/email.php";

// Configurar logging
$log_file = __DIR__ . '/notificacoes_log_' . date('Y-m-d') . '.txt';
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

        $data_ev = new DateTime($evento_detalhes->data_evento);

        // Ignorar eventos passados
        if ($data_ev < $data_atual) {
            continue;
        }

        $diferenca = $data_atual->diff($data_ev);
        $dias_restantes = $diferenca->days;

        // Notificar em 14, 7, 3 e 1 dias antes
        if (in_array($dias_restantes, [14, 7, 3, 1])) {
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Evento {$evento->id} em {$dias_restantes} dias, processando...\n", FILE_APPEND);

            $inscritos_evento = $ev->getInscritos($evento->id);
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Encontrados " . count($inscritos_evento) . " inscritos\n", FILE_APPEND);

            foreach ($inscritos_evento as $inscrito) {
                // Verificar se tem permissão para e-mail (campo padrão é 1 se não existir)
                $permissao_email = property_exists($inscrito, 'permissao_email') ? $inscrito->permissao_email : 1;

                if (($inscrito->status_pagamento == "RECEIVED" || $inscrito->status_pagamento == "ISENTO") && $permissao_email == 1) {
                    $mensagem = obter_mensagem_base($evento_detalhes->nome, $inscrito->id, "campeonato_lembrete", $dias_restantes);

                    if (!empty($mensagem)) {
                        $headers = "MIME-Version: 1.0\r\n";
                        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                        $headers .= "From: FPJJI <fpjjioficial@gmail.com>\r\n";
                        $headers .= "Reply-To: fpjjioficial@gmail.com\r\n";
                        $headers .= "X-Mailer: PHP/" . phpversion();

                        $assunto = "Lembrete: " . $evento_detalhes->nome . " em " . $dias_restantes . " dia" . ($dias_restantes > 1 ? "s" : "");

                        if (mail($inscrito->email, $assunto, $mensagem, $headers)) {
                            // Aguardar o tempo de 10 segundos para proxima entrega
                            usleep((10 * 1000000));
                            $notificacoes_enviadas++;
                            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] E-mail enviado para: {$inscrito->email}\n", FILE_APPEND);
                        } else {
                            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Falha ao enviar para: {$inscrito->email}\n", FILE_APPEND);
                        }
                    }
                }
            }
        }
    }

    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Processamento concluído. {$notificacoes_enviadas} notificações enviadas\n", FILE_APPEND);

} catch (Exception $e) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
}
?>