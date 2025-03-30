<?php
session_start();
require "../func/is_adm.php";
include "../func/clearWord.php";
is_adm();

try {
    include_once "../classes/atletaService.php";
    require_once __DIR__ . "/../func/clearWord.php";
    $con = new Conexao();
    $at = new Atleta();
    $attServ = new atletaService($con, $at);    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
    exit();
}

$id = cleanWords($_GET["id"]);

$attServ->excluirAtleta($id);
?>