<?php
session_start();
require "../func/is_adm.php";
is_adm();

require_once "../classes/eventosServices.php";
require_once "../classes/AssasService.php";
include "../func/clearWord.php";
require_once __DIR__ . "/../func/calcularIdade.php";

$conn = new Conexao();
$ev = new Evento();
$eventoServ = new eventosService($conn, $ev);

$eventoId = $_GET["id"] ?? null;
if (!$eventoId) {
    die("ID do evento não fornecido.");
}

$evento = $eventoServ->getById($eventoId);
if (!$evento) {
    die("Evento não encontrado.");
}

$gerarPDF = isset($_GET['pdf']);
$embaralhar = isset($_GET['embaralhar']);

// Configurar PDF se necessário
if ($gerarPDF) {
    require_once __DIR__ . '/../vendor/autoload.php';
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('Sistema de Eventos');
    $pdf->SetAuthor('Sistema de Eventos');
    $pdf->SetTitle('Chapas - ' . $evento->nome);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    $pdf->SetAutoPageBreak(TRUE, 15);
    $pdf->AddPage();
} else {
    // Configurar CSV
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="chapas_' . $eventoId . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Categoria', 'Faixa', 'Modalidade', 'Nome', 'Academia', 'Idade', 'Peso', 'Status Pagamento']);
}

// Obter inscritos válidos
$inscritos = $eventoServ->getInscritos($eventoId);
$inscritosValidos = array_filter($inscritos, function($inscrito) use ($evento) {
    $eventoGratuito = ($evento->preco == 0 && $evento->preco_menor == 0 && $evento->preco_abs == 0);
    return $eventoGratuito || 
           in_array($inscrito->status_pagamento, [
               AssasService::STATUS_PAGO, 
               AssasService::STATUS_CONFIRMADO,
               AssasService::STATUS_GRATUITO
           ]);
});

// Classificar inscritos por categoria, faixa e modalidade
$chapeamento = [];
foreach ($inscritosValidos as $inscrito) {
    $idade = calcularIdade($inscrito->data_nascimento);
    
    // Determinar categoria por idade
    $categoria = match(true) {
        $idade >= 4 && $idade <= 5 => "PRE-MIRIM",
        $idade >= 6 && $idade <= 7 => "MIRIM 1",
        $idade >= 8 && $idade <= 9 => "MIRIM 2",
        $idade >= 10 && $idade <= 11 => "INFANTIL 1",
        $idade >= 12 && $idade <= 13 => "INFANTIL 2",
        $idade >= 14 && $idade <= 15 => "INFANTO-JUVENIL",
        $idade >= 16 && $idade <= 17 => "JUVENIL",
        $idade >= 18 && $idade <= 29 => "ADULTO",
        $idade >= 30 => "MASTER",
        default => "OUTROS"
    };
    
    // Determinar tipo de competição
    $tipo = '';
    if ($inscrito->mod_com == 1) $tipo = 'COM KIMONO';
    if ($inscrito->mod_sem == 1) $tipo = 'SEM KIMONO';
    if ($inscrito->mod_ab_com == 1) $tipo = 'COM KIMONO - ABSOLUTO';
    if ($inscrito->mod_ab_sem == 1) $tipo = 'SEM KIMONO - ABSOLUTO';
    
    $chave = "$tipo|$categoria|{$inscrito->faixa}|{$inscrito->modalidade}";
    
    if (!isset($chapeamento[$chave])) {
        $chapeamento[$chave] = [
            'tipo' => $tipo,
            'categoria' => $categoria,
            'faixa' => $inscrito->faixa,
            'modalidade' => $inscrito->modalidade,
            'atletas' => []
        ];
    }
    
    $chapeamento[$chave]['atletas'][] = $inscrito;
}

// Ordenar chapas
uksort($chapeamento, function($a, $b) {
    $orderTipo = ['COM KIMONO' => 0, 'SEM KIMONO' => 1, 'COM KIMONO - ABSOLUTO' => 2, 'SEM KIMONO - ABSOLUTO' => 3];
    $orderCategoria = [
        "PRE-MIRIM" => 0, "MIRIM 1" => 1, "MIRIM 2" => 2,
        "INFANTIL 1" => 3, "INFANTIL 2" => 4, "INFANTO-JUVENIL" => 5,
        "JUVENIL" => 6, "ADULTO" => 7, "MASTER" => 8
    ];
    $orderFaixa = [
        "Branca" => 0, "Cinza" => 1, "Amarela" => 2, "Laranja" => 3,
        "Verde" => 4, "Azul" => 5, "Roxa" => 6, "Marrom" => 7,
        "Preta" => 8, "Coral" => 9, "Vermelha e Branca" => 10, "Vermelha" => 11
    ];
    
    list($tipoA, $catA, $faixaA, $modA) = explode('|', $a);
    list($tipoB, $catB, $faixaB, $modB) = explode('|', $b);
    
    if ($orderTipo[$tipoA] !== $orderTipo[$tipoB]) {
        return $orderTipo[$tipoA] - $orderTipo[$tipoB];
    }
    
    if ($orderCategoria[$catA] !== $orderCategoria[$catB]) {
        return $orderCategoria[$catA] - $orderCategoria[$catB];
    }
    
    if ($orderFaixa[$faixaA] !== $orderFaixa[$faixaB]) {
        return $orderFaixa[$faixaA] - $orderFaixa[$faixaB];
    }
    
    return strcmp($modA, $modB);
});

// Gerar saída
foreach ($chapeamento as $chapa) {
    if ($embaralhar) {
        shuffle($chapa['atletas']);
    }
    
    if ($gerarPDF) {
        $html = '<h2>' . htmlspecialchars("{$chapa['tipo']} - {$chapa['categoria']} - {$chapa['faixa']} - " . ucfirst($chapa['modalidade'])) . '</h2>';
        $html .= '<table border="1" cellpadding="4">';
        $html .= '<tr><th>#</th><th>Nome</th><th>Academia</th><th>Idade</th><th>Peso</th></tr>';
        
        foreach ($chapa['atletas'] as $i => $atleta) {
            $html .= '<tr>';
            $html .= '<td>' . ($i + 1) . '</td>';
            $html .= '<td>' . htmlspecialchars($atleta->nome) . '</td>';
            $html .= '<td>' . htmlspecialchars($atleta->academia) . '</td>';
            $html .= '<td>' . calcularIdade($atleta->data_nascimento) . '</td>';
            $html .= '<td>' . htmlspecialchars($atleta->peso) . ' kg</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table><br>';
        $pdf->writeHTML($html, true, false, true, false, '');
    } else {
        // CSV
        fputcsv($output, [
            $chapa['tipo'] . ' - ' . $chapa['categoria'],
            $chapa['faixa'],
            ucfirst($chapa['modalidade']),
            '',
            '',
            '',
            '',
            ''
        ]);
        
        foreach ($chapa['atletas'] as $atleta) {
            fputcsv($output, [
                '',
                '',
                '',
                $atleta->nome,
                $atleta->academia,
                calcularIdade($atleta->data_nascimento),
                $atleta->peso,
                $atleta->status_pagamento
            ]);
        }
        
        fputcsv($output, ['', '', '', '', '', '', '', '']); // Linha em branco
    }
}

if ($gerarPDF) {
    $pdf->Output('chapas_' . $eventoId . '.pdf', 'D');
} else {
    fclose($output);
}
exit;