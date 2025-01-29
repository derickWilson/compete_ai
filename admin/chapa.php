<?php
session_start();
require "../func/is_adm.php";
is_adm();

require_once "../classes/eventosServices.php";

$conn = new Conexao();
$evento = new Evento();
$eventoServ = new eventosService($conn, $evento);

if (isset($_GET["user"])) {
    $chapa = $eventoServ->montarChapa($_GET["id"]);
} else {
    echo "Selecione um usuário";
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Usuário</title>
</head>
<body>
    <header>
        <?php include "../menu/add_menu.php"; ?>
    </header>
    <div>

    </div>
</body>
</html>
