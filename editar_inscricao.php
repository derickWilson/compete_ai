<?php
// Incluindo arquivos necessários
session_start();/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/
if (!isset($_SESSION["logado"]) || !$_SESSION["logado"]){
    header("Location: index.php");
}
try {
    require_once "classes/atletaService.php";
    include "func/clearWord.php";
} catch (\Throwable $th) {
    print('['. $th->getMessage() .']');
}
    $conn = new Conexao();
    $at = new Atleta();
    $atserv = new atletaService($conn, $at);
    $evento = cleanWords($_POST["evento_id"]);
    $com = isset($_POST["com"]) ? 1 : 0;
    $abCom = isset($_POST["abs_com"]) ? 1 : 0;
    $sem = isset($_POST["sem"]) ? 1 : 0;
    $abSem = isset($_POST["abs_sem"]) ? 1 : 0;
    $moda = cleanWords($_POST["modalidade"]);
    $idAtleta = $_SESSION["id"];

    try {
        $atserv->editarInscricao($evento, $idAtleta, $com, $abCom, $sem, $abSem, $moda);
    } catch (Exception $e) {
        echo "erro ao editar inscricao [ ".$e->getMessage()." ]";
    }
?>