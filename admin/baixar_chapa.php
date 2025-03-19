<?php
session_start();
require "../func/is_adm.php";
is_adm();

require_once "../classes/eventosServices.php";
include "../func/clearWord.php";

$conn = new Conexao();
$ev = new Evento();
$eventoServ = new eventosService($conn, $ev);

$eventoId = $_GET["id"] ?? null;

if (!$eventoId) {
    die("ID do evento não fornecido.");
}

// Nome do arquivo CSV
$nomeArquivo = "chapas.csv";

// Definir cabeçalhos para forçar o download do CSV
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');

// Abrir saída do PHP para escrita do CSV
$output = fopen('php://output', 'w');

// Escrever cabeçalho da planilha
fputcsv($output, ['Categoria', 'Modalidade', 'Faixa', 'Nome', 'Academia']);

$modalidades = ["Galo", "Pluma", "Pena", "Leve", "Médio", "Meio-Pesado", "Pesado", "Super-Pesado", "Pesadíssimo", "Super-Pesadíssimo"];
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
$faixas = ["Branca", "Cinza", "Amarela", "Laranja", "Verde", "Azul", "Roxa", "Marrom", "Preta", "Coral", "Vermelha e Branca", "Vermelha"];

$evento = $eventoServ->getById($eventoId);

if ($evento->tipo_com == 1) {
    foreach ($categorias_idade as $categoria => $idades) {
        foreach ($modalidades as $modalidade) {
            foreach ($faixas as $faixa) {
                $chapa = $eventoServ->montarChapas($eventoId, $modalidade, $faixa, $idades["min"], $idades["max"]);
                
                if (!empty($chapa)) {
                    // Escrever uma linha indicando a categoria, modalidade e faixa
                    fputcsv($output, ["$categoria", "$modalidade", "$faixa", "", ""]);

                    foreach ($chapa as $atleta) {
                        fputcsv($output, ["", "", "", $atleta->nome, $atleta->academia]);
                    }

                    // Linha em branco para melhor separação
                    fputcsv($output, ["", "", "", "", ""]);
                }
            }
        }
    }
}

// Fechar a saída do CSV
fclose($output);
exit;
?>
