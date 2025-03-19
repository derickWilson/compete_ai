<?php
session_start();
require "../func/is_adm.php";
is_adm();

require_once "../classes/eventosServices.php";
include "../func/clearWord.php";

$conn = new Conexao();
$ev = new Evento();
$eventoServ = new eventosService($conn,$ev);

if (isset($_GET["id"])) {
    $camp = cleanWords($_GET["id"]);
} else {
    echo "Selecione um campeonato";
    header("Location: ../eventos.php");
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
    <div class="principal">
        <h2>Chapas do Campeonato</h2>

        <?php
        $modalidades = [
            "galo", "pluma", "pena", "leve", "medio", 
            "meio-pesado", "pesado", "super-pesado", "pesadissimo", "super-pesadissimo"
        ];
        $categorias_idade = [
            "PRE-MIRIM"       => ["min" => 4,  "max" => 5],
            "MIRIM 1"         => ["min" => 6,  "max" => 7],
            "MIRIM 2"         => ["min" => 8,  "max" => 9],
            "INFANTIL 1"      => ["min" => 10, "max" => 11],
            "INFANTIL 2"      => ["min" => 12, "max" => 13],
            "INFANTO-JUVENIL" => ["min" => 14, "max" => 15],
            "JUVENIL"         => ["min" => 16, "max" => 17],
            "ADULTO"          => ["min" => 18, "max" => 29],
            "MASTER"          => ["min" => 30, "max" => 100]
        ];
        $faixas = [
            "Branca", "Cinza", "Amarela", "Laranja", "Verde",
            "Azul", "Roxa", "Marrom", "Preta", "Coral",
            "Vermelha e Branca", "Vermelha"
        ];

        $evento = $eventoServ->getById($camp);

        if ($evento->tipo_com == 1) {
            foreach ($categorias_idade as $categoria => $idades) {
                foreach ($modalidades as $mods) {
                    foreach ($faixas as $cor) {
                        $chapa = $eventoServ->montarChapas($camp, $mods, $cor, $idades["min"], $idades["max"]);
                        $linha = 0;
                        foreach ($chapa as $atleta) {
                            if($linha%2 == 0){
                                echo "<br>";
                            }
                            echo "{$atleta->nome} | {$atleta->academia} | {$atleta->faixa}";
                            $linha += 1;
                        }
                    }
                }
            }
        }
        ?>
    </div>
    <br><a href="/compete_ai/eventos.php">Voltar</a>
</body>
</html>
