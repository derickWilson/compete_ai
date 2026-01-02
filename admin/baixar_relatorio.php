<?php
/**
 * Gerar um PDF com estatísticas e relatório financeiro do evento usando queries SQL
 */
session_start();
require "../func/is_adm.php";
is_adm();

// Inclusão de dependências
require_once "../classes/eventosServices.php";
require_once __DIR__ . "/../func/calcularIdade.php";
require_once "../classes/tcpdf/tcpdf.php";

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

// Conectar ao banco
$pdo = $conn->conectar();

// ----------------------------------------------------------------------------
// QUERIES SQL PARA ESTATÍSTICAS
// ----------------------------------------------------------------------------

// 1. ESTATÍSTICAS GERAIS
$queryEstatisticas = "
    SELECT 
        -- Total de atletas únicos
        COUNT(DISTINCT a.id) as total_atletas_unicos,
        
        -- Gênero
        SUM(CASE WHEN a.genero = 'Masculino' THEN 1 ELSE 0 END) as masculino,
        SUM(CASE WHEN a.genero = 'Feminino' THEN 1 ELSE 0 END) as feminino,
        
        -- Modalidades (considerando apenas se o evento suporta)
        SUM(CASE WHEN :tipo_com = 1 AND i.mod_com = 1 THEN 1 ELSE 0 END) as com_kimono,
        SUM(CASE WHEN :tipo_sem = 1 AND i.mod_sem = 1 THEN 1 ELSE 0 END) as sem_kimono,
        SUM(CASE WHEN :tipo_com = 1 AND i.mod_ab_com = 1 THEN 1 ELSE 0 END) as absoluto_com,
        SUM(CASE WHEN :tipo_sem = 1 AND i.mod_ab_sem = 1 THEN 1 ELSE 0 END) as absoluto_sem,
        
        -- Status de pagamento
        SUM(CASE WHEN i.status_pagamento IN ('RECEIVED', 'CONFIRMED') THEN 1 ELSE 0 END) as pagantes_confirmados,
        SUM(CASE WHEN i.status_pagamento IN ('PENDING', 'OVERDUE') THEN 1 ELSE 0 END) as pagantes_pendentes,
        SUM(CASE WHEN i.status_pagamento IN ('GRATUITO', 'ISENTO') OR i.valor_pago = 0 OR i.valor_pago IS NULL THEN 1 ELSE 0 END) as isentos
        
    FROM inscricao i
    JOIN atleta a ON i.id_atleta = a.id
    WHERE i.id_evento = :evento_id
";

$stmtEstatisticas = $pdo->prepare($queryEstatisticas);
$stmtEstatisticas->bindValue(':evento_id', $eventoId, PDO::PARAM_INT);
$stmtEstatisticas->bindValue(':tipo_com', $evento->tipo_com, PDO::PARAM_INT);
$stmtEstatisticas->bindValue(':tipo_sem', $evento->tipo_sem, PDO::PARAM_INT);
$stmtEstatisticas->execute();
$estatisticas = $stmtEstatisticas->fetch(PDO::FETCH_ASSOC);

// 2. PAGANTES POR CATEGORIA DE IDADE
$queryPagantesCategoria = "
    SELECT 
        CASE 
            WHEN TIMESTAMPDIFF(YEAR, a.data_nascimento, CURDATE()) <= 15 THEN 'ate_15'
            ELSE 'acima_15'
        END as faixa_idade,
        
        CASE 
            WHEN (i.mod_ab_com = 1 OR i.mod_ab_sem = 1) THEN 'absoluto'
            ELSE 'normal'
        END as tipo_categoria,
        
        COUNT(*) as quantidade,
        SUM(i.valor_pago) as total_pago
        
    FROM inscricao i
    JOIN atleta a ON i.id_atleta = a.id
    WHERE i.id_evento = :evento_id
        AND i.status_pagamento IN ('RECEIVED', 'CONFIRMED')
        AND i.valor_pago > 0
    GROUP BY faixa_idade, tipo_categoria
";

$stmtPagantesCategoria = $pdo->prepare($queryPagantesCategoria);
$stmtPagantesCategoria->bindValue(':evento_id', $eventoId, PDO::PARAM_INT);
$stmtPagantesCategoria->execute();
$pagantesPorCategoria = $stmtPagantesCategoria->fetchAll(PDO::FETCH_ASSOC);

// 3. ISENTOS POR CATEGORIA DE IDADE
$queryIsentosCategoria = "
    SELECT 
        CASE 
            WHEN TIMESTAMPDIFF(YEAR, a.data_nascimento, CURDATE()) <= 15 THEN 'ate_15'
            ELSE 'acima_15'
        END as faixa_idade,
        
        CASE 
            WHEN (i.mod_ab_com = 1 OR i.mod_ab_sem = 1) THEN 'absoluto'
            ELSE 'normal'
        END as tipo_categoria,
        
        COUNT(*) as quantidade
        
    FROM inscricao i
    JOIN atleta a ON i.id_atleta = a.id
    WHERE i.id_evento = :evento_id
        AND (i.status_pagamento IN ('GRATUITO', 'ISENTO') 
             OR i.valor_pago = 0 
             OR i.valor_pago IS NULL)
    GROUP BY faixa_idade, tipo_categoria
";

$stmtIsentosCategoria = $pdo->prepare($queryIsentosCategoria);
$stmtIsentosCategoria->bindValue(':evento_id', $eventoId, PDO::PARAM_INT);
$stmtIsentosCategoria->execute();
$isentosPorCategoria = $stmtIsentosCategoria->fetchAll(PDO::FETCH_ASSOC);

// 4. ARRECADAÇÃO POR LOTE (baseado no valor pago)
$queryArrecadacaoLote = "
    SELECT 
        CASE 
            -- 1º Lote (valores de referência)
            WHEN i.valor_pago BETWEEN 65 AND 75 THEN 'lote_1_ate_15'
            WHEN i.valor_pago BETWEEN 95 AND 105 THEN 'lote_1_acima_15'
            WHEN i.valor_pago BETWEEN 135 AND 145 THEN 'lote_1_absoluto'
            
            -- 2º Lote (valores de referência)
            WHEN i.valor_pago BETWEEN 95 AND 105 THEN 'lote_2_ate_15'
            WHEN i.valor_pago BETWEEN 125 AND 135 THEN 'lote_2_acima_15'
            WHEN i.valor_pago BETWEEN 175 AND 185 THEN 'lote_2_absoluto'
            
            ELSE 'outro'
        END as lote_categoria,
        
        COUNT(*) as quantidade,
        SUM(i.valor_pago) as total_pago
        
    FROM inscricao i
    JOIN atleta a ON i.id_atleta = a.id
    WHERE i.id_evento = :evento_id
        AND i.status_pagamento IN ('RECEIVED', 'CONFIRMED')
        AND i.valor_pago > 0
    GROUP BY lote_categoria
    ORDER BY lote_categoria
";

$stmtArrecadacaoLote = $pdo->prepare($queryArrecadacaoLote);
$stmtArrecadacaoLote->bindValue(':evento_id', $eventoId, PDO::PARAM_INT);
$stmtArrecadacaoLote->execute();
$arrecadacaoPorLote = $stmtArrecadacaoLote->fetchAll(PDO::FETCH_ASSOC);

// 5. TOTAL ARRECADADO
$queryTotalArrecadado = "
    SELECT 
        SUM(i.valor_pago) as total_arrecadado,
        COUNT(*) as total_pagamentos
        
    FROM inscricao i
    WHERE i.id_evento = :evento_id
        AND i.status_pagamento IN ('RECEIVED', 'CONFIRMED')
        AND i.valor_pago > 0
";

$stmtTotalArrecadado = $pdo->prepare($queryTotalArrecadado);
$stmtTotalArrecadado->bindValue(':evento_id', $eventoId, PDO::PARAM_INT);
$stmtTotalArrecadado->execute();
$totalArrecadado = $stmtTotalArrecadado->fetch(PDO::FETCH_ASSOC);

// 6. TOTAL DE CATEGORIAS (aproximado baseado em combinações únicas)
$queryTotalCategorias = "
    SELECT COUNT(DISTINCT 
        CONCAT(
            CASE 
                WHEN TIMESTAMPDIFF(YEAR, a.data_nascimento, CURDATE()) <= 15 THEN 'ate_15'
                ELSE 'acima_15'
            END,
            '|',
            a.genero,
            '|',
            a.faixa,
            '|',
            CASE 
                WHEN (i.mod_ab_com = 1 OR i.mod_ab_sem = 1) THEN 'absoluto'
                ELSE i.modalidade
            END,
            '|',
            CASE 
                WHEN i.mod_com = 1 THEN 'com'
                WHEN i.mod_sem = 1 THEN 'sem'
                ELSE 'outro'
            END
        )
    ) as total_categorias
    
    FROM inscricao i
    JOIN atleta a ON i.id_atleta = a.id
    WHERE i.id_evento = :evento_id
";

$stmtTotalCategorias = $pdo->prepare($queryTotalCategorias);
$stmtTotalCategorias->bindValue(':evento_id', $eventoId, PDO::PARAM_INT);
$stmtTotalCategorias->execute();
$totalCategorias = $stmtTotalCategorias->fetch(PDO::FETCH_ASSOC);

// ----------------------------------------------------------------------------
// PROCESSAMENTO DOS RESULTADOS
// ----------------------------------------------------------------------------

// Inicializar arrays para resultados organizados
$resultados = [
    'estatisticas_gerais' => $estatisticas,
    'total_categorias' => $totalCategorias['total_categorias'] ?? 0,
    'pagantes' => [
        'ate_15' => 0,
        'acima_15' => 0,
        'absoluto' => 0,
        'total' => $estatisticas['pagantes_confirmados'] ?? 0
    ],
    'isentos' => [
        'ate_15' => 0,
        'acima_15' => 0,
        'absoluto' => 0,
        'total' => $estatisticas['isentos'] ?? 0
    ],
    'arrecadacao' => [
        'lote_1' => [
            'ate_15' => ['quantidade' => 0, 'total' => 0],
            'acima_15' => ['quantidade' => 0, 'total' => 0],
            'absoluto' => ['quantidade' => 0, 'total' => 0],
            'total' => 0
        ],
        'lote_2' => [
            'ate_15' => ['quantidade' => 0, 'total' => 0],
            'acima_15' => ['quantidade' => 0, 'total' => 0],
            'absoluto' => ['quantidade' => 0, 'total' => 0],
            'total' => 0
        ],
        'total_geral' => $totalArrecadado['total_arrecadado'] ?? 0
    ]
];

// Processar pagantes por categoria
foreach ($pagantesPorCategoria as $item) {
    if ($item['tipo_categoria'] == 'absoluto') {
        $resultados['pagantes']['absoluto'] += $item['quantidade'];
    } elseif ($item['faixa_idade'] == 'ate_15') {
        $resultados['pagantes']['ate_15'] += $item['quantidade'];
    } else {
        $resultados['pagantes']['acima_15'] += $item['quantidade'];
    }
}

// Processar isentos por categoria
foreach ($isentosPorCategoria as $item) {
    if ($item['tipo_categoria'] == 'absoluto') {
        $resultados['isentos']['absoluto'] += $item['quantidade'];
    } elseif ($item['faixa_idade'] == 'ate_15') {
        $resultados['isentos']['ate_15'] += $item['quantidade'];
    } else {
        $resultados['isentos']['acima_15'] += $item['quantidade'];
    }
}

// Processar arrecadação por lote
foreach ($arrecadacaoPorLote as $item) {
    switch ($item['lote_categoria']) {
        case 'lote_1_ate_15':
            $resultados['arrecadacao']['lote_1']['ate_15']['quantidade'] = $item['quantidade'];
            $resultados['arrecadacao']['lote_1']['ate_15']['total'] = $item['total_pago'];
            $resultados['arrecadacao']['lote_1']['total'] += $item['total_pago'];
            break;
        case 'lote_1_acima_15':
            $resultados['arrecadacao']['lote_1']['acima_15']['quantidade'] = $item['quantidade'];
            $resultados['arrecadacao']['lote_1']['acima_15']['total'] = $item['total_pago'];
            $resultados['arrecadacao']['lote_1']['total'] += $item['total_pago'];
            break;
        case 'lote_1_absoluto':
            $resultados['arrecadacao']['lote_1']['absoluto']['quantidade'] = $item['quantidade'];
            $resultados['arrecadacao']['lote_1']['absoluto']['total'] = $item['total_pago'];
            $resultados['arrecadacao']['lote_1']['total'] += $item['total_pago'];
            break;
        case 'lote_2_ate_15':
            $resultados['arrecadacao']['lote_2']['ate_15']['quantidade'] = $item['quantidade'];
            $resultados['arrecadacao']['lote_2']['ate_15']['total'] = $item['total_pago'];
            $resultados['arrecadacao']['lote_2']['total'] += $item['total_pago'];
            break;
        case 'lote_2_acima_15':
            $resultados['arrecadacao']['lote_2']['acima_15']['quantidade'] = $item['quantidade'];
            $resultados['arrecadacao']['lote_2']['acima_15']['total'] = $item['total_pago'];
            $resultados['arrecadacao']['lote_2']['total'] += $item['total_pago'];
            break;
        case 'lote_2_absoluto':
            $resultados['arrecadacao']['lote_2']['absoluto']['quantidade'] = $item['quantidade'];
            $resultados['arrecadacao']['lote_2']['absoluto']['total'] = $item['total_pago'];
            $resultados['arrecadacao']['lote_2']['total'] += $item['total_pago'];
            break;
    }
}

// Calcular totais
$totalArrecadadoLote1 = $resultados['arrecadacao']['lote_1']['total'];
$totalArrecadadoLote2 = $resultados['arrecadacao']['lote_2']['total'];

// ----------------------------------------------------------------------------
// GERAÇÃO DO PDF
// ----------------------------------------------------------------------------

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Metadados
$pdf->SetCreator('Sistema de Competições da FPJJI');
$pdf->SetAuthor('Federação Paulista de Jiu-Jitsu Internacional');
$pdf->SetTitle('Relatório Estatístico e Financeiro - ' . $evento->nome);
$pdf->SetSubject('Relatório do Evento');

// Configuração
$pdf->SetMargins(15, 15, 15);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(10);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);

// ----------------------------------------------------------------------------
// PÁGINA 1 - CAPA E ESTATÍSTICAS
// ----------------------------------------------------------------------------

$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 20);
$pdf->Cell(0, 15, 'RELATÓRIO ESTATÍSTICO DO EVENTO', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, strtoupper($evento->nome), 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 8, 'Data do Evento: ' . date('d/m/Y', strtotime($evento->data_evento)), 0, 1, 'C');
$pdf->Cell(0, 8, 'Local: ' . $evento->local_camp, 0, 1, 'C');
$pdf->Cell(0, 8, 'Data de Emissão: ' . date('d/m/Y H:i:s'), 0, 1, 'C');

$pdf->Ln(10);

// Título da seção
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'ESTATÍSTICAS GERAIS', 0, 1);

// Primeira coluna - Estatísticas Gerais
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(90, 8, 'ESTATÍSTICAS GERAIS', 0, 0);
$pdf->Cell(90, 8, 'TOTAL ATLETAS PAGANTES', 0, 1);

$pdf->SetFont('helvetica', '', 10);

// Linha 1
$pdf->Cell(90, 8, 'Total de Categorias: ' . $resultados['total_categorias'], 0, 0);
$pdf->Cell(90, 8, 'Categoria até 15 anos: ' . $resultados['pagantes']['ate_15'], 0, 1);

// Linha 2
$pdf->Cell(90, 8, 'Total de Atletas Únicos: ' . $resultados['estatisticas_gerais']['total_atletas_unicos'], 0, 0);
$pdf->Cell(90, 8, 'Categoria acima 15 anos: ' . $resultados['pagantes']['acima_15'], 0, 1);

// Linha 3
$pdf->Cell(90, 8, 'Atletas Masculinos: ' . $resultados['estatisticas_gerais']['masculino'], 0, 0);
$pdf->Cell(90, 8, 'Categoria ABSOLUTO: ' . $resultados['pagantes']['absoluto'], 0, 1);

// Linha 4
$pdf->Cell(90, 8, 'Atletas Femininos: ' . $resultados['estatisticas_gerais']['feminino'], 0, 1);

$pdf->Ln(5);

// Segunda seção - Distribuição por Modalidade
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(90, 8, 'DISTRIBUIÇÃO POR MODALIDADE', 0, 0);
$pdf->Cell(90, 8, 'TOTAL ATLETAS ISENTOS', 0, 1);

$pdf->SetFont('helvetica', '', 10);

// Modalidades COM KIMONO
if ($evento->tipo_com) {
    $pdf->Cell(90, 8, 'Com Kimono: ' . $resultados['estatisticas_gerais']['com_kimono'], 0, 0);
    $pdf->Cell(90, 8, 'Categoria até 15 anos: ' . $resultados['isentos']['ate_15'], 0, 1);
    
    if ($resultados['estatisticas_gerais']['absoluto_com'] > 0) {
        $pdf->Cell(90, 8, 'Absoluto Com Kimono: ' . $resultados['estatisticas_gerais']['absoluto_com'], 0, 0);
        $pdf->Cell(90, 8, 'Categoria acima 15 anos: ' . $resultados['isentos']['acima_15'], 0, 1);
    }
}

// Modalidades SEM KIMONO
if ($evento->tipo_sem) {
    $linhaOffset = $evento->tipo_com ? 2 : 0;
    
    if ($linhaOffset == 0) {
        $pdf->Cell(90, 8, 'Sem Kimono: ' . $resultados['estatisticas_gerais']['sem_kimono'], 0, 0);
        $pdf->Cell(90, 8, 'Categoria até 15 anos: ' . $resultados['isentos']['ate_15'], 0, 1);
        
        if ($resultados['estatisticas_gerais']['absoluto_sem'] > 0) {
            $pdf->Cell(90, 8, 'Absoluto Sem Kimono: ' . $resultados['estatisticas_gerais']['absoluto_sem'], 0, 0);
            $pdf->Cell(90, 8, 'Categoria acima 15 anos: ' . $resultados['isentos']['acima_15'], 0, 1);
        }
    } else {
        if ($resultados['estatisticas_gerais']['absoluto_com'] == 0) {
            $pdf->Cell(90, 8, '', 0, 0);
            $pdf->Cell(90, 8, 'Categoria acima 15 anos: ' . $resultados['isentos']['acima_15'], 0, 1);
        }
        
        $pdf->Cell(90, 8, 'Sem Kimono: ' . $resultados['estatisticas_gerais']['sem_kimono'], 0, 0);
        $pdf->Cell(90, 8, 'Categoria ABSOLUTO: ' . $resultados['isentos']['absoluto'], 0, 1);
    }
}

// Linha final para isentos ABSOLUTO
if (($evento->tipo_com && !$evento->tipo_sem) || 
    ($evento->tipo_com && $evento->tipo_sem && $resultados['estatisticas_gerais']['absoluto_sem'] == 0)) {
    $pdf->Cell(90, 8, '', 0, 0);
    $pdf->Cell(90, 8, 'Categoria ABSOLUTO: ' . $resultados['isentos']['absoluto'], 0, 1);
}

$pdf->Ln(5);

// Modalidades do evento
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 8, 'MODALIDADES DO EVENTO:', 0, 1);
$pdf->SetFont('helvetica', '', 9);

$modalidadesEvento = [];
if ($evento->tipo_com) $modalidadesEvento[] = 'COM KIMONO';
if ($evento->tipo_sem) $modalidadesEvento[] = 'SEM KIMONO';

$pdf->Cell(0, 6, implode(' | ', $modalidadesEvento), 0, 1);

// ----------------------------------------------------------------------------
// PÁGINA 2 - RESUMO FINANCEIRO
// ----------------------------------------------------------------------------

$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'RESUMO FINANCEIRO', 0, 1, 'C');
$pdf->Ln(5);

// Cabeçalho das colunas
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(95, 8, 'TOTAL ATLETAS PAGANTES 1º Lote', 0, 0, 'C');
$pdf->Cell(95, 8, 'TOTAL ATLETAS PAGANTES 2º Lote', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 10);

// Linha 1 - Até 15 anos
$pdf->Cell(95, 8, 'Categoria até 15 anos valor R$ 70,00: ' . 
    $resultados['arrecadacao']['lote_1']['ate_15']['quantidade'], 0, 0, 'C');
$pdf->Cell(95, 8, 'Categoria até 15 anos valor R$ 100,00: ' . 
    $resultados['arrecadacao']['lote_2']['ate_15']['quantidade'], 0, 1, 'C');

// Linha 2 - Acima 15 anos
$pdf->Cell(95, 8, 'Categoria acima 15 anos valor R$ 100,00: ' . 
    $resultados['arrecadacao']['lote_1']['acima_15']['quantidade'], 0, 0, 'C');
$pdf->Cell(95, 8, 'Categoria acima 15 anos valor R$ 130,00: ' . 
    $resultados['arrecadacao']['lote_2']['acima_15']['quantidade'], 0, 1, 'C');

// Linha 3 - Absoluto
$pdf->Cell(95, 8, 'Categoria ABSOLUTO valor R$ 140,00: ' . 
    $resultados['arrecadacao']['lote_1']['absoluto']['quantidade'], 0, 0, 'C');
$pdf->Cell(95, 8, 'Categoria ABSOLUTO valor R$ 180,00: ' . 
    $resultados['arrecadacao']['lote_2']['absoluto']['quantidade'], 0, 1, 'C');

$pdf->Ln(10);

// Cabeçalho arrecadação
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(95, 8, 'TOTAL ARRECADADO POR CATEGORIA 1º Lote', 0, 0, 'C');
$pdf->Cell(95, 8, 'TOTAL ARRECADADO POR CATEGORIA 2º Lote', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 10);

// Linha 1 - Até 15 anos
$pdf->Cell(95, 8, 'Categoria até 15 anos valor: R$ ' . 
    number_format($resultados['arrecadacao']['lote_1']['ate_15']['total'], 2, ',', '.'), 0, 0, 'C');
$pdf->Cell(95, 8, 'Categoria até 15 anos valor: R$ ' . 
    number_format($resultados['arrecadacao']['lote_2']['ate_15']['total'], 2, ',', '.'), 0, 1, 'C');

// Linha 2 - Acima 15 anos
$pdf->Cell(95, 8, 'Categoria acima 15 anos valor: R$ ' . 
    number_format($resultados['arrecadacao']['lote_1']['acima_15']['total'], 2, ',', '.'), 0, 0, 'C');
$pdf->Cell(95, 8, 'Categoria acima 15 anos valor: R$ ' . 
    number_format($resultados['arrecadacao']['lote_2']['acima_15']['total'], 2, ',', '.'), 0, 1, 'C');

// Linha 3 - Absoluto
$pdf->Cell(95, 8, 'Categoria ABSOLUTO valor: R$ ' . 
    number_format($resultados['arrecadacao']['lote_1']['absoluto']['total'], 2, ',', '.'), 0, 0, 'C');
$pdf->Cell(95, 8, 'Categoria ABSOLUTO valor: R$ ' . 
    number_format($resultados['arrecadacao']['lote_2']['absoluto']['total'], 2, ',', '.'), 0, 1, 'C');

// ----------------------------------------------------------------------------
// PÁGINA 3 - TOTAL A RECEBER
// ----------------------------------------------------------------------------

$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 15, 'TOTAL A RECEBER DE INSCRIÇÕES', 0, 1, 'C');
$pdf->Ln(10);

// 1º Lote
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'TOTAL A RECEBER 1º LOTE', 0, 1);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 8, 'R$ ' . number_format($totalArrecadadoLote1, 2, ',', '.'), 0, 1);

$pdf->Ln(5);

// 2º Lote
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'TOTAL A RECEBER 2º LOTE', 0, 1);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 8, 'R$ ' . number_format($totalArrecadadoLote2, 2, ',', '.'), 0, 1);

$pdf->Ln(10);

// Total Bruto
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'TOTAL A RECEBER BRUTO', 0, 1);
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 12, 'R$ ' . number_format($resultados['arrecadacao']['total_geral'], 2, ',', '.'), 0, 1, 'C');

$pdf->Ln(15);

// Resumo adicional
$pdf->SetFont('helvetica', 'I', 10);
$pdf->MultiCell(0, 6, 'Resumo Detalhado:', 0, 'L');
$pdf->SetFont('helvetica', '', 9);

$pdf->Cell(0, 6, 'Total de Inscrições Pagas: ' . $resultados['pagantes']['total'], 0, 1);
$pdf->Cell(0, 6, 'Total de Isentos: ' . $resultados['isentos']['total'], 0, 1);
$pdf->Cell(0, 6, 'Pagamentos Pendentes: ' . $resultados['estatisticas_gerais']['pagantes_pendentes'], 0, 1);

$pdf->Ln(10);

// Rodapé
$pdf->SetFont('helvetica', 'I', 9);
$pdf->MultiCell(0, 6, 'Documento gerado automaticamente pelo Sistema de Competições da FPJJI.', 0, 'C');
$pdf->MultiCell(0, 6, 'Data de geração: ' . date('d/m/Y H:i:s'), 0, 'C');

// ----------------------------------------------------------------------------
// SAÍDA DO DOCUMENTO
// ----------------------------------------------------------------------------

$pdf->Output('relatorio_estatistico_evento_' . $eventoId . '.pdf', 'D');
exit;
?>