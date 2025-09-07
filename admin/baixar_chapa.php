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

// Configurar CSV
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="chapas_' . $eventoId . '.csv"');
$output = fopen('php://output', 'w');
fputcsv($output, ['Categoria', 'Faixa', 'Modalidade', 'Nome', 'Academia', 'Idade', 'Peso', 'Status Pagamento']);

// Obter inscritos válidos (MESMA lógica do lista_inscritos.php)
$inscritos = $eventoServ->getInscritos($eventoId);
$inscritosValidos = array_filter($inscritos, function($inscrito) use ($evento) {
    // Verifica se é evento gratuito
    $eventoGratuito = $evento->normal ? ($evento->normal_preco == 0) : 
        ($evento->preco == 0 && $evento->preco_menor == 0 && $evento->preco_abs == 0);
    
    // Se evento gratuito: todos são válidos
    // Se evento pago: apenas pagos/confirmados/isentos
    return $eventoGratuito || in_array($inscrito->status_pagamento, [
        'RECEIVED', 'CONFIRMED', 'ISENTO', 'RECEIVED_IN_CASH'
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
        "JUVENIL" => 6, "ADULTO" => 7, "MASTER" => 8, "OUTROS" => 9
    ];
    $orderFaixa = [
        "Branca" => 0, "Cinza" => 1, "Amarela" => 2, "Laranja" => 3,
        "Verde" => 4, "Azul" => 5, "Roxa" => 6, "Marrom" => 7,
        "Preta" => 8, "Coral" => 9, "Vermelha e Branca" => 10, "Vermelha" => 11
    ];
    
    $partesA = explode('|', $a);
    $partesB = explode('|', $b);
    
    // Verifica se o explode retornou todas as partes necessárias
    if (count($partesA) < 4 || count($partesB) < 4) {
        return 0; // Não ordena se não tiver todas as partes
    }
    
    list($tipoA, $catA, $faixaA, $modA) = $partesA;
    list($tipoB, $catB, $faixaB, $modB) = $partesB;
    
    // Usa valores padrão se não existir no array
    $ordemTipoA = $orderTipo[$tipoA] ?? 999;
    $ordemTipoB = $orderTipo[$tipoB] ?? 999;
    $ordemCatA = $orderCategoria[$catA] ?? 999;
    $ordemCatB = $orderCategoria[$catB] ?? 999;
    $ordemFaixaA = $orderFaixa[$faixaA] ?? 999;
    $ordemFaixaB = $orderFaixa[$faixaB] ?? 999;
    
    if ($ordemTipoA !== $ordemTipoB) {
        return $ordemTipoA - $ordemTipoB;
    }
    
    if ($ordemCatA !== $ordemCatB) {
        return $ordemCatA - $ordemCatB;
    }
    
    if ($ordemFaixaA !== $ordemFaixaB) {
        return $ordemFaixaA - $ordemFaixaB;
    }
    
    return strcmp($modA, $modB);
});

// Gerar saída CSV
foreach ($chapeamento as $chapa) {
    if ($embaralhar) {
        shuffle($chapa['atletas']);
    }
    
    // CSV - Cabeçalho da chapa
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
    
    // CSV - Atletas
    foreach ($chapa['atletas'] as $atleta) {
        // Mapear status para texto amigável
        $statusText = match($atleta->status_pagamento) {
            'RECEIVED' => 'PAGO',
            'CONFIRMED' => 'CONFIRMADO',
            'ISENTO' => 'ISENTO',
            'RECEIVED_IN_CASH' => 'PAGO (DINHEIRO)',
            'PENDING' => 'PENDENTE',
            default => $atleta->status_pagamento
        };
        
        fputcsv($output, [
            '',
            '',
            '',
            $atleta->inscrito,
            $atleta->academia,
            calcularIdade($atleta->data_nascimento),
            $atleta->peso,
            $statusText
        ]);
    }
    
    fputcsv($output, ['', '', '', '', '', '', '', '']); // Linha em branco
}

fclose($output);
exit;
?>