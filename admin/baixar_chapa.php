<?php
/**
 * Gerar um PDF com as chaves de competição organizadas por categoria,
 * faixa, modalidade e idade para eventos de Jiu-Jitsu
 */
// Inicialização da sessão e verificação de permissões
session_start();
require "../func/is_adm.php";
is_adm();

// Inclusão de dependências
require_once "../classes/eventosServices.php";
require_once "../classes/AssasService.php";
include "../func/clearWord.php";
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
 * Filtra inscritos válidos baseado no status de pagamento
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
// FUNÇÃO PARA CORRIGIR INSCRIÇÕES APENAS NO ABSOLUTO
// ----------------------------------------------------------------------------

/**
 * Se um atleta está inscrito apenas no absoluto, automaticamente o inscreve
 * na categoria normal correspondente (com kimono ou sem kimono)
 */
function corrigirInscricaoAbsoluto($inscrito) {
    // Se está inscrito apenas no absoluto com kimono, adiciona com kimono normal
    if ($inscrito->mod_ab_com == 1 && $inscrito->mod_com == 0) {
        $inscrito->mod_com = 1;
    }
    
    // Se está inscrito apenas no absoluto sem kimono, adiciona sem kimono normal
    if ($inscrito->mod_ab_sem == 1 && $inscrito->mod_sem == 0) {
        $inscrito->mod_sem = 1;
    }
    
    return $inscrito;
}

// Aplica a correção para todos os inscritos
foreach ($inscritosValidos as $inscrito) {
    $inscrito = corrigirInscricaoAbsoluto($inscrito);
}

// ----------------------------------------------------------------------------
// CLASSIFICAÇÃO DOS ATLETAS POR CATEGORIA - CORRIGIDA PARA ABSOLUTOS
// ----------------------------------------------------------------------------

/**
 * Estrutura para armazenar atletas agrupados por categoria
 */
$chapeamento = [];

foreach ($inscritosValidos as $inscrito) {
    /**
     * Determina o tipo de competição baseado na modalidade de inscrição
     * COM prioridade: Absolutos > Com Kimono > Sem Kimono
     */
    $tipo = '';
    $eh_absoluto = false;

    if ($inscrito->mod_ab_com == 1) {
        $tipo = 'ABSOLUTO COM KIMONO';
        $eh_absoluto = true;
    } else if ($inscrito->mod_com == 1) {
        $tipo = 'COM KIMONO';
    } else if ($inscrito->mod_ab_sem == 1) {
        $tipo = 'ABSOLUTO SEM KIMONO';
        $eh_absoluto = true;
    } else if ($inscrito->mod_sem == 1) {
        $tipo = 'SEM KIMONO';
    }
    
    // Pula se não tiver tipo definido
    if (empty($tipo)) continue;
    
    // Para categorias ABSOLUTAS, usar categoria "ABSOLUTO" e todas as faixas
    if ($eh_absoluto) {
        $categoria = 'ABSOLUTO';
        $grupoFaixa = 'TODAS AS FAIXAS';
    } else {
        // Para categorias normais, calcular idade e determinar categoria etária
        $idade = calcularIdade($inscrito->data_nascimento);
        
        $categoria = match(true) {
            $idade >= 4 && $idade <= 5 => "PRE-MIRIM",
            $idade >= 6 && $idade <= 7 => "MIRIM 1",
            $idade >= 8 && $idade <= 9 => "MIRIM 2",
            $idade >= 10 && $idade <= 11 => "INFANTIL 1",
            $idade >= 12 && $idade <= 13 => "INFANTIL 2",
            $idade >= 14 && $idade <= 15 => "INFANTO-JUVENIL",
            $idade >= 16 && $idade <= 17 => "JUVENIL",
            $idade >= 18 && $idade <= 29 => "ADULTO",
            $idade >= 30 && $idade <= 35 => "MASTER 1",
            $idade >= 36 && $idade <= 40 => "MASTER 2",
            $idade >= 41 && $idade <= 45 => "MASTER 3",
            $idade >= 46 => "MASTER 4+",
            default => "OUTROS"
        };
        
        // Agrupa faixas conforme especificado para categorias normais
        $grupoFaixa = '';
        if (in_array($inscrito->faixa, ['Branca'])) {
            $grupoFaixa = 'BRANCA';
        } else if (in_array($inscrito->faixa, ['Cinza', 'Amarela'])) {
            $grupoFaixa = 'CINZA/AMARELA';
        } else if (in_array($inscrito->faixa, ['Laranja', 'Verde'])) {
            $grupoFaixa = 'LARANJA/VERDE';
        } else if (in_array($inscrito->faixa, ['Azul'])) {
            $grupoFaixa = 'AZUL';
        } else if (in_array($inscrito->faixa, ['Roxa'])) {
            $grupoFaixa = 'ROXA';
        } else if (in_array($inscrito->faixa, ['Marrom'])) {
            $grupoFaixa = 'MARROM';
        } else if (in_array($inscrito->faixa, ['Preta', 'Coral', 'Vermelha e Branca', 'Vermelha'])) {
            $grupoFaixa = 'PRETA';
        }
    }
    
    // Pula se não conseguir classificar a faixa (apenas para categorias não-absolutas)
    if (empty($grupoFaixa) && !$eh_absoluto) continue;
    
    // Para categorias absolutas, usar "ABSOLUTO" como modalidade
    $modalidade = $eh_absoluto ? 'ABSOLUTO' : $inscrito->modalidade;
    
    // Chave única para agrupamento
    $chave = "{$inscrito->genero}|$tipo|$grupoFaixa|$categoria|$modalidade";
    
    // Inicializa o grupo se não existir
    if (!isset($chapeamento[$chave])) {
        $chapeamento[$chave] = [
            'genero' => $inscrito->genero,
            'tipo' => $tipo,
            'faixa' => $grupoFaixa,
            'categoria' => $categoria,
            'modalidade' => $modalidade,
            'atletas' => []
        ];
    }
    
    // Adiciona atleta ao grupo
    $chapeamento[$chave]['atletas'][] = $inscrito;
}

// ----------------------------------------------------------------------------
// ORDENAÇÃO DAS CATEGORIAS - CORRIGIDA PARA ABSOLUTOS
// ----------------------------------------------------------------------------

/**
 * Ordena as categorias na ordem especificada:
 * 1. Sexo (Masculino → Feminino)
 * 2. Tipo de competição (Com Kimono → Absoluto Com Kimono → Sem Kimono → Absoluto Sem Kimono)
 * 3. Grupo de faixa (ordem especificada, com "TODAS AS FAIXAS" no final para absolutos)
 * 4. Categoria etária (Mais jovens primeiro, com "ABSOLUTO" no final)
 * 5. Modalidade (peso)
 */
uksort($chapeamento, function($a, $b) {
    // Divide as chaves para comparação
    $partesA = explode('|', $a);
    $partesB = explode('|', $b);
    
    // Validação de segurança
    if (count($partesA) < 5 || count($partesB) < 5) {
        return 0;
    }
    
    // Extrai componentes para comparação
    list($generoA, $tipoA, $faixaA, $catA, $modA) = $partesA;
    list($generoB, $tipoB, $faixaB, $catB, $modB) = $partesB;
    
    // 1. Ordena por sexo (Masculino primeiro)
    if ($generoA !== $generoB) {
        return $generoA === 'Masculino' ? -1 : 1;
    }
    
    // 2. Ordena por tipo de competição
    $orderTipo = [
        'COM KIMONO' => 0, 
        'ABSOLUTO COM KIMONO' => 1,
        'SEM KIMONO' => 2, 
        'ABSOLUTO SEM KIMONO' => 3
    ];
    
    $ordemTipoA = $orderTipo[$tipoA] ?? 999;
    $ordemTipoB = $orderTipo[$tipoB] ?? 999;
    
    if ($ordemTipoA !== $ordemTipoB) {
        return $ordemTipoA - $ordemTipoB;
    }
    
    // 3. Ordena por grupo de faixa (absolutos ficam depois das faixas específicas)
    $orderFaixa = [
        'BRANCA' => 0,
        'CINZA/AMARELA' => 1,
        'LARANJA/VERDE' => 2,
        'AZUL' => 3,
        'ROXA' => 4,
        'MARROM' => 5,
        'PRETA' => 6,
        'TODAS AS FAIXAS' => 7  // Absolutos ficam depois de todas as faixas específicas
    ];
    
    $ordemFaixaA = $orderFaixa[$faixaA] ?? 999;
    $ordemFaixaB = $orderFaixa[$faixaB] ?? 999;
    
    if ($ordemFaixaA !== $ordemFaixaB) {
        return $ordemFaixaA - $ordemFaixaB;
    }
    
    // 4. Ordena por categoria etária (absolutos ficam depois de todas as categorias etárias)
    $orderCategoria = [
        "PRE-MIRIM" => 0, "MIRIM 1" => 1, "MIRIM 2" => 2,
        "INFANTIL 1" => 3, "INFANTIL 2" => 4, "INFANTO-JUVENIL" => 5,
        "JUVENIL" => 6, "ADULTO" => 7, "MASTER 1" => 8, 
        "MASTER 2" => 9, "MASTER 3" => 10, "MASTER 4+" => 11,
        "OUTROS" => 12,
        "ABSOLUTO" => 13  // Absolutos ficam depois de todas as categorias etárias
    ];
    
    $ordemCatA = $orderCategoria[$catA] ?? 999;
    $ordemCatB = $orderCategoria[$catB] ?? 999;
    
    if ($ordemCatA !== $ordemCatB) {
        return $ordemCatA - $ordemCatB;
    }
    
    // 5. Ordena por modalidade (peso)
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
// PÁGINAS POR CATEGORIA - CORREÇÃO NO CABEÇALHO PARA ABSOLUTOS
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
    
    // Cabeçalho da categoria - tratamento especial para absolutos
    $pdf->SetFont('helvetica', 'B', 16);
    
    if ($chapa['categoria'] === 'ABSOLUTO') {
        // Para absolutos, mostrar apenas gênero e tipo
        $pdf->Cell(0, 10, $chapa['genero'] . ' - ' . $chapa['tipo'], 0, 1, 'C');
    } else {
        // Para categorias normais, mostrar gênero, tipo e categoria etária
        $pdf->Cell(0, 10, $chapa['genero'] . ' - ' . $chapa['tipo'] . ' - ' . $chapa['categoria'], 0, 1, 'C');
    }
    
    $pdf->SetFont('helvetica', '', 12);
    
    // Mostra informações de faixa e modalidade
    if ($chapa['modalidade'] === 'ABSOLUTO') {
        $pdf->Cell(0, 8, 'Faixa: ' . $chapa['faixa'] . ' - Modalidade: ABSOLUTO', 0, 1, 'C');
    } else {
        $pdf->Cell(0, 8, 'Faixa: ' . $chapa['faixa'] . ' - Peso: ' . $chapa['modalidade'], 0, 1, 'C');
    }
    
    $pdf->Ln(5);
    
    // ------------------------------------------------------------------------
    // TABELA DE ATLETAS
    // ------------------------------------------------------------------------

    $pdf->SetFont('helvetica', 'B', 10);

    // Definição das larguras das colunas (em mm)
    $largura_nome = 70;    // Nome do atleta
    $largura_academia = 50; // Nome da academia  
    $largura_idade = 15;   // Idade
    $largura_peso = 20;    // Peso
    $largura_status = 25;  // Status

    $pdf->Cell($largura_nome, 8, 'NOME DO ATLETA', 1, 0, 'C');
    $pdf->Cell($largura_academia, 8, 'ACADEMIA', 1, 0, 'C');
    $pdf->Cell($largura_idade, 8, 'IDADE', 1, 0, 'C');
    $pdf->Cell($largura_peso, 8, 'PESO', 1, 0, 'C');
    $pdf->Cell($largura_status, 8, 'STATUS', 1, 1, 'C');

    $pdf->SetFont('helvetica', '', 9);

    // Listagem dos atletas com quebra de linha automática
    foreach ($chapa['atletas'] as $atleta) {
        $statusText = match($atleta->status_pagamento) {
            'RECEIVED' => 'PAGO',
            'CONFIRMED' => 'CONFIRMADO',
            'ISENTO' => 'ISENTO',
            'RECEIVED_IN_CASH' => 'PAGO (DINHEIRO)',
            'PENDING' => 'PENDENTE',
            default => $atleta->status_pagamento
        };
        
        // Nome do atleta (com quebra de linha se necessário)
        $pdf->Cell($largura_nome, 7, $atleta->inscrito, 1, 0, 'L');
        
        // Nome da academia (com texto reduzido se necessário)
        $academia = strlen($atleta->academia) > 20 ? 
            substr($atleta->academia, 0, 17) . '...' : 
            $atleta->academia;
        $pdf->Cell($largura_academia, 7, $academia, 1, 0, 'L');
        
        // Idade
        $pdf->Cell($largura_idade, 7, calcularIdade($atleta->data_nascimento), 1, 0, 'C');
        
        // Peso
        $pdf->Cell($largura_peso, 7, $atleta->peso . ' kg', 1, 0, 'C');
        
        // Status
        $pdf->Cell($largura_status, 7, $statusText, 1, 1, 'C');
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