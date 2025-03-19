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
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    $evento = cleanWords($_POST["evento_id"]);
    $com = isset($_POST["com"]);
    $abCom = isset($_POST["abs_com"]);
    $sem = isset($_POST["sem"]);
    $abSem = isset($_POST["abs_sem"]);
    $moda = cleanWords($_POST["modalidade"]);
?>