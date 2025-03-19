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
    <link rel="stylesheet" href="/style.css">
    <title>Criar Chapa</title>
</head>
<body>

    <?php include "../menu/add_menu.php"; ?>
    <div class="principla">
        <?php
            
    $modalidades = [
        "galo", "pluma", "pena", "leve", "medio", 
        "meio-pesado", "pesado", "super-pesado", "pesadissimo", "super-pesadissimo"
    ];
    $categorias_idade = [
        "PRE-MIRIM"       => ["min" => 4,  "max" => 5],   // 4 a 5 anos
        "MIRIM 1"         => ["min" => 6,  "max" => 7],   // 6 a 7 anos
        "MIRIM 2"         => ["min" => 8,  "max" => 9],   // 8 a 9 anos
        "INFANTIL 1"      => ["min" => 10, "max" => 11],  // 10 a 11 anos
        "INFANTIL 2"      => ["min" => 12, "max" => 13],  // 12 a 13 anos
        "INFANTO-JUVENIL" => ["min" => 14, "max" => 15],  // 14 a 15 anos
        "JUVENIL"         => ["min" => 16, "max" => 17],  // 16 a 17 anos
        "ADULTO"          => ["min" => 18, "max" => 29],  // 18 a 29 anos
        "MASTER"          => ["min" => 30, "max" => 100]  // 30 anos ou mais
    ];
    $faixas = [
        "Branca",
        "Cinza",
        "Amarela",
        "Laranja",
        "Verde",
        "Azul",
        "Roxa",
        "Marrom",
        "Preta",
        "Coral",
        "Vermelha e Branca",
        "Vermelha"
    ];

    foreach($modalidades as $mod){
        echo $mod."<br>";
        foreach($categorias_idade as $key => $value){
            echo "<p><br>".$key." maior = ".$value["max"]." menor = ".$value["min"]."<br></<p>";
        }
    }
        ?>
    </div>
    <br><a href="/compete_ai/eventos.php">Voltar</a>

</body>
</html>
<?php
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $eventServ->montarChapa($id, $col, $infantil, $infantoJuvenil, $mastes, $pPesado, $medio);
}
?>