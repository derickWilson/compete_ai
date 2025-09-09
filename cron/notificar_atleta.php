<?php
//8.2.22
require_once __DIR__ . "/../classes/AssasService.php";
require_once __DIR__ . "/../classes/eventosServices.php";
require_once __DIR__ . "/../func/database.php";
require_once __DIR__ . "/../email/email.php";

$conn = new Conexao();
$evento = new Evento();

$ev = new eventosService($conn, $evento);

//obter eventos
$eventos = $ev->listAll();

//percorrer eventos
foreach ($eventos as $evento) {
    //ver se o evento está proximo
    //pegar data atual
    $data_atual = new DateTime();

    //data do evento
    $data_ev = new DateTime($ev->getById($evento->id)->data_evento);

    //diferença de datas
    $tempo = $data_atual->diff($data_ev);

    // para cada diferença de dias
    switch ($tempo->days) {
        case 7://diferença de 1 semana
            //obter todos os inscritos
            $inscritos_evento = $ev->getInscritos($evento->id);

            foreach ($inscritos_evento as $inscrito) {
                if (
                    $inscrito->status_pagamento == "RECEIVED" ||
                    $inscrito->status_pagamento == "ISENTO"
                ) {
                    //caso o atleta esteja comfirmado ou isentado, notifica quantos dias faltam
                    envia_notificacao_para($evento->nome,$inscrito->id, "camp", 7);
                }
                break;

            }
    }
}
?>