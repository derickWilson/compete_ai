<?php
session_start();
try {
    require_once "../func/is_adm.php";
} catch (\Throwable $th) {
    echo $th->getMessage();
}
is_adm();
// Verifica se o usuário é admin; se não for, redireciona
if (isset($_GET["id"])) {
    try {
        require_once __DIR__ . "/../classes/eventosServices.php";
        require_once __DIR__ . "/../func/clearWord.php";
        require_once __DIR__ . "/../func/calcularIdade.php";
    } catch (\Throwable $th) {
        echo $th->getMessage();
    }    $id = (int) cleanWords($_GET["id"]); 
    // Cria instâncias das classes
    $conn = new Conexao();
    $ev = new Evento();
    $evserv = new eventosService($conn, $ev);

    // Obtém os inscritos do evento
    $dados = $evserv->getInscritos($id);

    // Cabeçalho do arquivo CSV
    $header = array(
        "Campeonato", "Atleta", "Idade", "Faixa",
        "Peso", "Academia", "Com Quimono", "Sem Quimono", "Absoluto Com Quimono",
        "Absoluto Sem Quimono"
    );

    // Nome do arquivo CSV
    $filename = "inscritos_evento_{$id}.csv";

    // Define os cabeçalhos HTTP para forçar o download do arquivo
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // Cria o arquivo CSV no fluxo de saída
    $output = fopen('php://output', 'w');
    // Escreve o cabeçalho no arquivo CSV
    fputcsv($output, $header);
    // Escreve os dados no arquivo CSV
    foreach ($dados as $value) {
        fputcsv($output, [
            $value->evento,
            $value->inscrito,
            calcularIdade($value->data_nascimento),
            $value->faixa,
            $value->peso,
            $value->academia,
            $value->mod_com ? "X" : "",
            $value->mod_sem ? "X" : "",
            $value->mod_ab_com ? "X" : "",
            $value->mod_ab_sem ? "X" : ""
        ]);
    }

    // Fecha o fluxo de saída
    fclose($output);
    exit();
} else {
    echo "Erro";
    header("Location /admin/eventos.php");
}
?>