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
    header("Loacatio: ../eventos.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style.css">
    <title>Criar Chapa</title>
</head>
<body>

    <?php include "../menu/add_menu.php"; ?>
    <div>
    <form action="chapa.php">
        <input type="number" name="camp" id="camp" value="<?php echo $camp;?>"><br>
        <label for="idInfantil">Idade maxima infantil</label><input type="number" name="idInfantil">Anos<br>
        <label for="idInfantoJuvenil">Idade maxima infanto junvenil</label><input type="number" name="idInfantoJuvenil">Anos<br>
        <label for="idAdulto">Idade maxima adulto</label><input type="number" name="idAdulto">Anos<br>
        <label for="idMasters">Idade Masters</label><input type="number" name="idMasters">Anos<br>
    </form>
    </div>
    <div>
        <?php
        if($_SERVER["REQUEST_METHOD"] == "POST"){
            $faixas = ["Branca", "Azul","Roxa","Preta", "Coral", "Vermelha", "Preta e Vermelha", "Preta e Branca"];
            foreach($faixas as $cor){
                
            }
        }
        ?>
    </div>
</body>
</html>
