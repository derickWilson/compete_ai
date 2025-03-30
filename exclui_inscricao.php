<?php
session_start();/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/
if (!isset($_SESSION["logado"]) || !$_SESSION["logado"]){
    header("Location: index.php");
}
if(!isset($_GET["id"])){
    header("Location /eventos_cadastrados.php");
}
try {
    require_once "classes/atletaService.php";
    include "func/clearWord.php";
    $conn = new Conexao();
    $at = new Atleta();
    $atserv = new atletaService($conn, $at);
} catch (\Throwable $th) {
    print('['. $th->getMessage() .']');
}

$evento = cleanWords($_GET["id"]);
$atleta = $_SESSION["id"];
$atserv->excluirInscricao($evento, $atleta);
?>