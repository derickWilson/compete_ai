<?php
session_start();
require "../func/is_adm.php";
is_adm();

require_once "../classes/eventosServices.php";
include "../func/clearWord.php";

$conn = new Conexao();
$evento = new Evento();
$eventoServ = new eventosService($conn, $evento);

if (isset($_GET["id"])) {
    $camp = cleanWords($_GET["id"]);
} else {
    echo "Selecione um campeonato";
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

    <?php include "../menu/add_menu.php"; ?>
    <div>
    <form action="chapa.php">
        <input type="number" name="camp" id="camp" value="<?php echo $camp;?>">

    </form>
    </div>
</body>
</html>
