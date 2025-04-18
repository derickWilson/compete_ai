<?php
require "../func/is_adm.php";
is_adm();

include_once "../classes/atletaClass.php";
include_once "../classes/atletaService.php";

$conn = new Conexao();
$atleta = new Atleta();
$attServ = new atletaService($conn, $atleta);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Atualiza os dados do usuário
    $id = $_POST["id"];
    $validado = isset($_POST["validado"]) ? 1 : 0;
    $faixa = $_POST["faixa"];
    $attServ->editAdmin($id, $validado, $faixa);
    header("Location: pessoas.php");
}
?>