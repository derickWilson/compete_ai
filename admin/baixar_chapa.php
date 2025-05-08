<?php
session_start();
require "../func/is_adm.php";
is_adm();

require_once "../classes/eventosServices.php";
require_once "../classes/AssasService.php";
include "../func/clearWord.php";
include __DIR__ . "/../func/calcularIdade.php";
require_once __DIR__ . '/../vendor/autoload.php'; // Para o TCPDF

$conn = new Conexao();
$ev = new Evento();
$eventoServ = new eventosService($conn, $ev);

$eventoId = $_GET["id"] ?? null;

if (!$eventoId) {
    die("ID do evento não fornecido.");
}

$evento = $eventoServ->getById($eventoId);
$eventoGratuito = ($evento->preco == 0 && $evento->preco_menor == 0 && $evento->preco_abs == 0);

// Verificar se foi solicitado gerar PDF
$gerarPDF = isset($_GET['pdf']);

if ($gerarPDF) {
    // Configurar PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Sistema de Eventos');
    $pdf->SetTitle('Chapas do Evento - ' . $evento->nome);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
} else {
    // Forçar download do CSV se não for PDF
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="chapas_' . $eventoId . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Tipo', 'Categoria', 'Modalidade', 'Faixa', 'Nome', 'Academia', 'Idade', 'Peso']);
}

// Obter todos os inscritos válidos
$inscritos = $eventoServ->getInscritos($eventoId);
$inscritosValidos = [];

foreach ($inscritos as $inscrito) {
    if ($eventoGratuito || 
        $inscrito->status_pagamento === AssasService::STATUS_PAGO || 
        $inscrito->status_pagamento === AssasService::STATUS_CONFIRMADO ||
        $inscrito->status_pagamento === AssasService::STATUS_GRATUITO) {
        $inscritosValidos[] = $inscrito;
    }
}

// Função para processar e exibir uma chapa
function processarChapa($tipo, $categoria, $faixa, $modalidade, $atletas, $gerarPDF, $pdf, $output) {
    if (empty($atletas)) return;
    
    if ($gerarPDF) {
        $pdf->AddPage();
        $html = '<h2>' . htmlspecialchars("$tipo - $categoria - $faixa - $modalidade") . '</h2>';
        $html .= '<table border="1" cellpadding="4">';
        $html .= '<tr><th>Nome</th><th>Academia</th><th>Idade</th><th>Peso</th></tr>';
        
        shuffle($atletas); // Embaralhar atletas
        
        foreach ($atletas as $atleta) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($atleta->nome) . '</td>';
            $html .= '<td>' . htmlspecialchars($atleta->academia) . '</td>';
            $html .= '<td>' . calcularIdade($atleta->data_nascimento) . '</td>';
            $html .= '<td>' . htmlspecialchars($atleta->peso) . ' kg</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        $pdf->writeHTML($html, true, false, true, false, '');
    } else {
        // CSV
        fputcsv($output, [$tipo, $categoria, $modalidade, $faixa, "", "", "", ""]);
        
        shuffle($atletas); // Embaralhar atletas
        
        foreach ($atletas as $atleta) {
            fputcsv($output, [
                "",
                "",
                "",
                "",
                $atleta->nome,
                $atleta->academia,
                calcularIdade($atleta->data_nascimento),
                $atleta->peso
            ]);
        }
        
        fputcsv($output, ["", "", "", "", "", "", "", ""]); // Linha em branco
    }
}

// Processar eventos com kimono
if ($evento->tipo_com == 1) {
    $categorias_idade = [
        "PRE-MIRIM" => [4,5], "MIRIM 1" => [6,7], "MIRIM 2" => [8,9],
        "INFANTIL 1" => [10,11], "INFANTIL 2" => [12,13], 
        "INFANTO-JUVENIL" => [14,15], "JUVENIL" => [16,17],
        "ADULTO" => [18,29], "MASTER" => [30,100]
    ];
    
    $faixas = ["Branca", "Cinza", "Amarela", "Laranja", "Verde", "Azul", "Roxa", "Marrom", "Preta"];
    
    foreach ($categorias_idade as $categoria => $idades) {
        foreach ($faixas as $faixa) {
            // Filtrar atletas para esta categoria
            $atletas_categoria = array_filter($inscritosValidos, function($atleta) use ($idades, $faixa, $categoria) {
                $idade = calcularIdade($atleta->data_nascimento);
                $faixaMatch = (strtolower(trim($atleta->faixa)) === strtolower(trim($faixa));
                $idadeMatch = ($idade >= $idades[0] && $idade <= $idades[1]);
                $modalidadeMatch = ($atleta->mod_com == 1 || $atleta->mod_ab_com == 1);
                
                return $faixaMatch && $idadeMatch && $modalidadeMatch;
            });
            
            if (!empty($atletas_categoria)) {
                // Separar absoluto e categoria normal
                $atletas_normal = array_filter($atletas_categoria, fn($a) => $a->mod_com == 1);
                $atletas_absoluto = array_filter($atletas_categoria, fn($a) => $a->mod_ab_com == 1);
                
                if (!empty($atletas_normal)) {
                    processarChapa(
                        'COM KIMONO', 
                        $categoria, 
                        $faixa, 
                        $atleta->modalidade, // Assumindo que a modalidade está no objeto
                        $atletas_normal,
                        $gerarPDF,
                        $pdf,
                        $output
                    );
                }
                
                if (!empty($atletas_absoluto)) {
                    processarChapa(
                        'COM KIMONO - ABSOLUTO', 
                        $categoria, 
                        $faixa, 
                        'ABSOLUTO',
                        $atletas_absoluto,
                        $gerarPDF,
                        $pdf,
                        $output
                    );
                }
            }
        }
    }
}

// Processar eventos sem kimono (lógica similar)
if ($evento->tipo_sem == 1) {
    // Implementação similar à do com kimono, ajustando os filtros
}

if ($gerarPDF) {
    // Gerar PDF
    $pdf->Output('chapas_' . $eventoId . '.pdf', 'D');
} else {
    // Fechar CSV
    fclose($output);
}
exit;
>?