<?php
session_start();

// Verifica se o usuário é admin; se não for, redireciona
if (!isset($_SESSION["admin"]) || !$_SESSION["admin"]) {
    header("Location: index.php");
    exit();
}

if (isset($_GET["id"]) && is_numeric($_GET["id"])) {
    include "../classes/eventosServices.php";
    include_once "../func/clearWord.php";
    
    $id = (int) cleanWords($_GET["id"]); 
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
            $value->idade,
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
    echo "ID do evento inválido.";
}
?>
