<?php
session_start();
require "../func/is_adm.php";
is_adm();

require_once "../classes/eventosServices.php";
require_once "../classes/AssasService.php";
include "../func/clearWord.php";
include __DIR__ . "/../func/calcularIdade.php"; // Inclui a função de cálculo de idade

$conn = new Conexao();
$ev = new Evento();
$eventoServ = new eventosService($conn, $ev);

if (isset($_GET["id"])) {
    $camp = cleanWords($_GET["id"]);
} else {
    echo "Selecione um campeonato";
    header("Location: ../eventos.php");
    exit();
}

$evento = $eventoServ->getById($camp);
$eventoGratuito = ($evento->preco == 0 && $evento->preco_menor == 0 && $evento->preco_abs == 0);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/style.css">
    <title>Criar Chapa</title>
    <style>
        .chapa-container {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .chapa-title {
            font-weight: bold;
            background-color: #f0f0f0;
            padding: 5px;
            margin-bottom: 5px;
        }
        .atleta-item {
            padding: 3px;
            border-bottom: 1px solid #eee;
        }
        .atleta-item:nth-child(even) {
            background-color: #f9f9f9;
        }
        @media print {
            a { display: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <?php include "../menu/add_menu.php"; ?>
    <div class="principal">
        <h2>Chapas do Campeonato: <?php echo htmlspecialchars($evento->nome); ?></h2>
        
        <?php
        $inscritos = $eventoServ->getInscritos($camp);
        $inscritosValidos = [];
        
        foreach ($inscritos as $inscrito) {
            if ($eventoGratuito || 
                $inscrito->status_pagamento === AssasService::STATUS_PAGO || 
                $inscrito->status_pagamento === AssasService::STATUS_CONFIRMADO ||
                $inscrito->status_pagamento === AssasService::STATUS_GRATUITO) {
                $inscritosValidos[] = $inscrito;
            }
        }

        // Agrupar por categoria
        $chapeamento = [];
        foreach ($inscritosValidos as $inscrito) {
            $idade = calcularIdade($inscrito->data_nascimento); // Usando a função existente
            
            $categoriaIdade = "";
            foreach ([
                "PRE-MIRIM" => [4,5],
                "MIRIM 1" => [6,7],
                "MIRIM 2" => [8,9],
                "INFANTIL 1" => [10,11],
                "INFANTIL 2" => [12,13],
                "INFANTO-JUVENIL" => [14,15],
                "JUVENIL" => [16,17],
                "ADULTO" => [18,29],
                "MASTER" => [30,100]
            ] as $cat => $range) {
                if ($idade >= $range[0] && $idade <= $range[1]) {
                    $categoriaIdade = $cat;
                    break;
                }
            }
            
            if (empty($categoriaIdade)) continue;
            
            $chave = $categoriaIdade . '|' . $inscrito->faixa . '|' . $inscrito->modalidade;
            
            if (!isset($chapeamento[$chave])) {
                $chapeamento[$chave] = [
                    'categoria' => $categoriaIdade,
                    'faixa' => $inscrito->faixa,
                    'modalidade' => $inscrito->modalidade,
                    'atletas' => []
                ];
            }
            
            $chapeamento[$chave]['atletas'][] = $inscrito;
        }

        // Ordenar chapas
        uasort($chapeamento, function($a, $b) {
            $orderIdade = [
                "PRE-MIRIM" => 0, "MIRIM 1" => 1, "MIRIM 2" => 2,
                "INFANTIL 1" => 3, "INFANTIL 2" => 4, "INFANTO-JUVENIL" => 5,
                "JUVENIL" => 6, "ADULTO" => 7, "MASTER" => 8
            ];
            
            $orderFaixa = [
                "Branca" => 0, "Cinza" => 1, "Amarela" => 2, "Laranja" => 3,
                "Verde" => 4, "Azul" => 5, "Roxa" => 6, "Marrom" => 7,
                "Preta" => 8, "Coral" => 9, "Vermelha e Branca" => 10, "Vermelha" => 11
            ];
            
            if ($a['categoria'] !== $b['categoria']) {
                return $orderIdade[$a['categoria']] - $orderIdade[$b['categoria']];
            }
            
            if ($a['faixa'] !== $b['faixa']) {
                return $orderFaixa[$a['faixa']] - $orderFaixa[$b['faixa']];
            }
            
            return strcmp($a['modalidade'], $b['modalidade']);
        });

        // Exibir chapas com atletas embaralhados
        foreach ($chapeamento as $chapa) {
            echo '<div class="chapa-container">';
            echo '<div class="chapa-title">';
            echo htmlspecialchars(
                $chapa['categoria'] . ' - ' . 
                $chapa['faixa'] . ' - ' . 
                ucfirst($chapa['modalidade']) . 
                ' (' . count($chapa['atletas']) . ' atletas)'
            );
            echo '</div>';
            
            // Embaralhar os atletas desta chapa
            $atletasEmbaralhados = $chapa['atletas'];
            shuffle($atletasEmbaralhados);
            
            foreach ($atletasEmbaralhados as $atleta) {
                echo '<div class="atleta-item">';
                echo htmlspecialchars(
                    "{$atleta->nome} | {$atleta->academia} | " .
                    "Peso: {$atleta->peso}kg | " .
                    "Idade: " . calcularIdade($atleta->data_nascimento) . " anos"
                );
                echo '</div>';
            }
            
            echo '</div>';
        }
        ?>
    </div>
    <br>
    <div class="no-print">
        <a href="/compete_ai/eventos.php">Voltar</a> | 
        <a href="#" onclick="window.print()">Imprimir Chapas</a> |
        <a href="?id=<?php echo $camp; ?>&embaralhar=1">Embaralhar Novamente</a>
    </div>
</body>
</html>