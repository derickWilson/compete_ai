<?php
/**
 **gerar um PDF com as chaves de competição organizadas por categoria,
 * faixa, modalidade e idade para eventos de Jiu-Jitsu
 * 
 * */
// Inicialização da sessão e verificação de permissões
session_start();
require "../func/is_adm.php";
is_adm();

// Inclusão de dependências
require_once "../classes/eventosServices.php";
require_once "../classes/AssasService.php";
include "../func/clearWord.php";
require_once __DIR__ . "/../func/calcularIdade.php";
require_once __DIR__ . "/../classes/tc-lib-pdf-main/src/Tcpdf.php";

/**
 * Instanciação dos serviços necessários
 */
$conn = new Conexao();
$ev = new Evento();
$eventoServ = new eventosService($conn, $ev);

// Validação do ID do evento
$eventoId = $_GET["id"] ?? null;
if (!$eventoId) {
    die("ERRO: ID do evento não fornecido.");
}

// Obtenção dos dados do evento
$evento = $eventoServ->getById($eventoId);
if (!$evento) {
    die("ERRO: Evento não encontrado.");
}

// Configuração de parâmetros opcionais
$embaralhar = 1;

// ----------------------------------------------------------------------------
// PROCESSAMENTO DOS INSCRITOS VÁLIDOS
// ----------------------------------------------------------------------------

/**
 * Obtém todos os inscritos no evento
 */
$inscritos = $eventoServ->getInscritos($eventoId);

/**
 * filtra inscritos válidos baseado no status de pagamento
 * eventos gratuitos: todos os inscritos são válidos
 * eventos pagos: apenas inscrições com pagamento confirmado ou se foi isento
 */
$inscritosValidos = array_filter($inscritos, function($inscrito) use ($evento) {
    $eventoGratuito = $evento->normal ? ($evento->normal_preco == 0) : 
        ($evento->preco == 0 && $evento->preco_menor == 0 && $evento->preco_abs == 0);
    
    return $eventoGratuito || in_array($inscrito->status_pagamento, [
        'RECEIVED', 'CONFIRMED', 'ISENTO', 'RECEIVED_IN_CASH'
    ]);
});

// ----------------------------------------------------------------------------
// CLASSIFICAÇÃO DOS ATLETAS POR CATEGORIA
// ----------------------------------------------------------------------------

/**
 * Estrutura para armazenar atletas agrupados por categoria
 */
$chapeamento = [];

foreach ($inscritosValidos as $inscrito) {
    // Calcula idade do atleta para determinar categoria
    $idade = calcularIdade($inscrito->data_nascimento);
    
    /**
     * Determina a categoria etária baseado na idade
     * Sistema padrão de categorias do Jiu-Jitsu
     */
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
    
    /**
     * Determina o tipo de competição baseado na modalidade de inscrição
     */
    $tipo = '';
    if ($inscrito->mod_com == 1) $tipo = 'COM KIMONO';
    if ($inscrito->mod_sem == 1) $tipo = 'SEM KIMONO';
    if ($inscrito->mod_ab_com == 1) $tipo = 'COM KIMONO - ABSOLUTO';
    if ($inscrito->mod_ab_sem == 1) $tipo = 'SEM KIMONO - ABSOLUTO';
    
    // Chave única para agrupamento
    $chave = "$tipo|$categoria|{$inscrito->faixa}|{$inscrito->modalidade}";
    
    // Inicializa o grupo se não existir
    if (!isset($chapeamento[$chave])) {
        $chapeamento[$chave] = [
            'tipo' => $tipo,
            'categoria' => $categoria,
            'faixa' => $inscrito->faixa,
            'modalidade' => $inscrito->modalidade,
            'atletas' => []
        ];
    }
    
    // Adiciona atleta ao grupo
    $chapeamento[$chave]['atletas'][] = $inscrito;
}

// ----------------------------------------------------------------------------
// ORDENAÇÃO DAS CATEGORIAS
// ----------------------------------------------------------------------------

/**
 * Ordena as categorias por:
 * 1. Tipo de competição (Com Kimono → Sem Kimono → Absolutos)
 * 2. Categoria etária (Mais jovens primeiro)
 * 3. Faixa (Branca → Preta)
 * 4. Modalidade (Peso específico → Absoluto)
 */
uksort($chapeamento, function($a, $b) {
    // Tabelas de ordenação
    $orderTipo = [
        'COM KIMONO' => 0, 
        'SEM KIMONO' => 1, 
        'COM KIMONO - ABSOLUTO' => 2, 
        'SEM KIMONO - ABSOLUTO' => 3
    ];
    
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
    
    // Divide as chaves para comparação
    $partesA = explode('|', $a);
    $partesB = explode('|', $b);
    
    // Validação de segurança
    if (count($partesA) < 4 || count($partesB) < 4) {
        return 0;
    }
    
    // Extrai componentes para comparação
    list($tipoA, $catA, $faixaA, $modA) = $partesA;
    list($tipoB, $catB, $faixaB, $modB) = $partesB;
    
    // Obtém ordens com valores padrão para casos inválidos
    $ordemTipoA = $orderTipo[$tipoA] ?? 999;
    $ordemTipoB = $orderTipo[$tipoB] ?? 999;
    $ordemCatA = $orderCategoria[$catA] ?? 999;
    $ordemCatB = $orderCategoria[$catB] ?? 999;
    $ordemFaixaA = $orderFaixa[$faixaA] ?? 999;
    $ordemFaixaB = $orderFaixa[$faixaB] ?? 999;
    
    // Comparação em cascata
    if ($ordemTipoA !== $ordemTipoB) return $ordemTipoA - $ordemTipoB;
    if ($ordemCatA !== $ordemCatB) return $ordemCatA - $ordemCatB;
    if ($ordemFaixaA !== $ordemFaixaB) return $ordemFaixaA - $ordemFaixaB;
    
    return strcmp($modA, $modB);
});

// ----------------------------------------------------------------------------
// GERAÇÃO DO PDF COM TCPDF
// ----------------------------------------------------------------------------

/**
 * Configuração inicial do documento PDF
 */
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Metadados do documento
$pdf->SetCreator('Sistema de Competições de Jiu-Jitsu');
$pdf->SetAuthor('Federação de Jiu-Jitsu');
$pdf->SetTitle('Chaves de Competição - ' . $evento->nome);
$pdf->SetSubject('Chaveamento Oficial');

// Configuração de margens
$pdf->SetMargins(15, 15, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);

// ----------------------------------------------------------------------------
// PÁGINA INICIAL - CAPA DO DOCUMENTO
// ----------------------------------------------------------------------------

$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 20);
$pdf->Cell(0, 15, 'CHAVES OFICIAIS DE COMPETIÇÃO', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, strtoupper($evento->nome), 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 8, 'Data do Evento: ' . date('d/m/Y', strtotime($evento->data_evento)), 0, 1, 'C');
$pdf->Cell(0, 8, 'Local: ' . $evento->local_camp, 0, 1, 'C');
$pdf->Cell(0, 8, 'Data de Emissão: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
$pdf->Ln(15);

// ----------------------------------------------------------------------------
// PÁGINAS POR CATEGORIA
// ----------------------------------------------------------------------------

foreach ($chapeamento as $chapa) {
    /**
     * Embaralha os atletas se solicitado (para sorteio)
     */
    if ($embaralhar) {
        shuffle($chapa['atletas']);
    }
    
    // Nova página para cada categoria
    $pdf->AddPage();
    
    // Cabeçalho da categoria
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, $chapa['tipo'] . ' - ' . $chapa['categoria'], 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 8, 'Faixa: ' . $chapa['faixa'] . ' - Modalidade: ' . $chapa['modalidade'], 0, 1, 'C');
    $pdf->Ln(5);
    
    // ------------------------------------------------------------------------
    // TABELA DE ATLETAS
    // ------------------------------------------------------------------------
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(80, 8, 'NOME DO ATLETA', 1, 0, 'C');
    $pdf->Cell(40, 8, 'ACADEMIA', 1, 0, 'C');
    $pdf->Cell(20, 8, 'IDADE', 1, 0, 'C');
    $pdf->Cell(20, 8, 'PESO', 1, 0, 'C');
    $pdf->Cell(30, 8, 'STATUS', 1, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 9);
    
    // Listagem dos atletas
    foreach ($chapa['atletas'] as $atleta) {
        $statusText = match($atleta->status_pagamento) {
            'RECEIVED' => 'PAGO',
            'CONFIRMED' => 'CONFIRMADO',
            'ISENTO' => 'ISENTO',
            'RECEIVED_IN_CASH' => 'PAGO (DINHEIRO)',
            'PENDING' => 'PENDENTE',
            default => $atleta->status_pagamento
        };
        
        $pdf->Cell(80, 7, $atleta->inscrito, 1);
        $pdf->Cell(40, 7, $atleta->academia, 1);
        $pdf->Cell(20, 7, calcularIdade($atleta->data_nascimento), 1, 0, 'C');
        $pdf->Cell(20, 7, $atleta->peso . ' kg', 1, 0, 'C');
        $pdf->Cell(30, 7, $statusText, 1, 1, 'C');
    }
    
    $pdf->Ln(10);
    
    // ------------------------------------------------------------------------
    // CHAVEAMENTO SIMPLES
    // ------------------------------------------------------------------------
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'CHAVEAMENTO PRELIMINAR:', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    
    $numAtletas = count($chapa['atletas']);
    for ($i = 0; $i < $numAtletas; $i += 2) {
        if (isset($chapa['atletas'][$i + 1])) {
            $pdf->Cell(0, 7, 
                ($i + 1) . '. ' . $chapa['atletas'][$i]->inscrito . 
                '  vs  ' . 
                ($i + 2) . '. ' . $chapa['atletas'][$i + 1]->inscrito, 
                0, 1
            );
        } else {
            $pdf->Cell(0, 7, 
                ($i + 1) . '. ' . $chapa['atletas'][$i]->inscrito . ' - BYE', 
                0, 1
            );
        }
    }
    
    $pdf->Ln(15);
}

// ----------------------------------------------------------------------------
// SAÍDA DO DOCUMENTO
// ----------------------------------------------------------------------------

/**
 * Gera o PDF para download
 * Nome do arquivo: chaves_competicao_evento_[ID].pdf
 */
$pdf->Output('chaves_competicao_evento_' . $eventoId . '.pdf', 'D');
exit;

// ----------------------------------------------------------------------------
// FIM DO SCRIPT
// ----------------------------------------------------------------------------
?>