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
$inscritosValidos = array_filter($inscritos, function ($inscrito) use ($evento) {
    $eventoGratuito = $evento->normal ? ($evento->normal_preco == 0) :
        ($evento->preco == 0 && $evento->preco_menor == 0 && $evento->preco_abs == 0);

    return $eventoGratuito || !in_array($inscrito->status_pagamento, [
        'PENDING'
    ]);
});

// ----------------------------------------------------------------------------
// FUNÇÕES PARA CORRIGIR INSCRIÇÕES
// ----------------------------------------------------------------------------

/**
 * Se um atleta está inscrito apenas no absoluto, automaticamente o inscreve
 * na categoria normal correspondente (com kimono ou sem kimono)
 */
function corrigirInscricaoAbsoluto($inscrito)
{
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

/**
 * Remove inscrição no absoluto para atletas menores de 15 anos
 * Absoluto é apenas para maiores de 15 anos
 */
function corrigirMenoresAbsoluto($inscrito)
{
    $idade = calcularIdade($inscrito->data_nascimento);
    
    // Se é menor de 15 anos, remove inscrição no absoluto
    if ($idade < 15) {
        $inscrito->mod_ab_com = 0;
        $inscrito->mod_ab_sem = 0;
    }
    
    return $inscrito;
}

/**
 * Função para agrupar faixas conforme regras específicas por idade
 * - Até 15 anos: Branca com Branca, Cinza com Amarela, Laranja com Verde
 * - Acima de 15 anos: Cada faixa compete apenas com a mesma faixa
 */
function agruparFaixa($faixa, $idade)
{
    // Para atletas até 15 anos
    if ($idade <= 15) {
        return match($faixa) {
            'Branca' => 'BRANCA',
            'Cinza', 'Amarela' => 'CINZA/AMARELA',
            'Laranja', 'Verde' => 'LARANJA/VERDE',
            'Azul' => 'AZUL',
            'Roxa' => 'ROXA',
            'Marrom' => 'MARROM',
            'Preta' => 'PRETA',
            default => $faixa
        };
    }
    
    // Para atletas acima de 15 anos, cada faixa compete apenas com a mesma faixa
    return $faixa;
}

// Aplica as correções para todos os inscritos
foreach ($inscritosValidos as $inscrito) {
    $inscrito = corrigirInscricaoAbsoluto($inscrito);
    $inscrito = corrigirMenoresAbsoluto($inscrito);
}

// ----------------------------------------------------------------------------
// CLASSIFICAÇÃO DOS ATLETAS POR CATEGORIA
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
    } else if ($inscrito->mod_ab_sem == 1) {
        $tipo = 'ABSOLUTO SEM KIMONO';
        $eh_absoluto = true;
    } else if ($inscrito->mod_com == 1) {
        $tipo = 'COM KIMONO';
    } else if ($inscrito->mod_sem == 1) {
        $tipo = 'SEM KIMONO';
    }

    // Pula se não tiver tipo definido
    if (empty($tipo))
        continue;

    // Calcula idade para determinar agrupamento de faixas e validar absoluto
    $idade = calcularIdade($inscrito->data_nascimento);

    // Para categorias ABSOLUTAS - apenas para maiores de 15 anos
    if ($eh_absoluto) {
        // Se é menor de 15 anos, pula esta inscrição no absoluto
        if ($idade < 15) {
            continue;
        }
        
        $categoria = 'ABSOLUTO';
        // No absoluto, faixas competem apenas com mesma faixa (acima de 15 anos)
        $grupoFaixa = $inscrito->faixa;
    } else {
        // Para categorias normais, determinar categoria etária
        $categoria = match (true) {
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

        // Aplica agrupamento de faixas considerando a idade
        $grupoFaixa = agruparFaixa($inscrito->faixa, $idade);
    }

    // Pula se não conseguir classificar a faixa
    if (empty($grupoFaixa))
        continue;

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
// ORDENAÇÃO DAS CATEGORIAS - CORRIGIDA
// ----------------------------------------------------------------------------

/**
 * Ordena as categorias na ordem especificada:
 * 1. Sexo (Masculino → Feminino)
 * 2. Tipo de competição (Com Kimono → Absoluto Com Kimono → Sem Kimono → Absoluto Sem Kimono)
 * 3. Faixa (ordem de graduação)
 * 4. Categoria etária (Mais jovens primeiro, com "ABSOLUTO" no final)
 * 5. Modalidade (peso)
 */
uksort($chapeamento, function ($a, $b) {
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

    // 3. Ordena por grupo de faixa (considerando agrupamentos)
    $orderFaixa = [
        'BRANCA' => 0,
        'CINZA/AMARELA' => 1,
        'LARANJA/VERDE' => 2,
        'AZUL' => 3,
        'ROXA' => 4,
        'MARROM' => 5,
        'PRETA' => 6,
        // Para faixas não agrupadas (acima de 15 anos e absoluto)
        'Branca' => 0,
        'Cinza' => 1,
        'Amarela' => 1,
        'Laranja' => 2,
        'Verde' => 2,
        'Azul' => 3,
        'Roxa' => 4,
        'Marrom' => 5,
        'Preta' => 6
    ];

    $ordemFaixaA = $orderFaixa[$faixaA] ?? 999;
    $ordemFaixaB = $orderFaixa[$faixaB] ?? 999;

    if ($ordemFaixaA !== $ordemFaixaB) {
        return $ordemFaixaA - $ordemFaixaB;
    }

    // 4. Ordena por categoria etária (absolutos ficam depois de todas as categorias etárias)
    $orderCategoria = [
        "PRE-MIRIM" => 0,
        "MIRIM 1" => 1,
        "MIRIM 2" => 2,
        "INFANTIL 1" => 3,
        "INFANTIL 2" => 4,
        "INFANTO-JUVENIL" => 5,
        "JUVENIL" => 6,
        "ADULTO" => 7,
        "MASTER" => 8,
        "ABSOLUTO" => 9
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
// FUNÇÃO PARA GERAR CHAVEAMENTO SIMPLES EM TABELA
// ----------------------------------------------------------------------------

/**
 * Função para criar chaveamento simples em formato de tabela vertical
 */
function gerarChaveamentoSimples($atletas, $pdf)
{
    $numAtletas = count($atletas);
    if ($numAtletas == 0)
        return;

    $pdf->SetFont('helvetica', '', 9);

    // Larguras das colunas
    $largura_nome = 60;
    $largura_vencedor = 60;
    $largura_espaco = 10;

    $startY = $pdf->GetY();
    $maxY = $pdf->GetPageHeight() - 30; // Margem inferior

    for ($i = 0; $i < $numAtletas; $i += 2) {
        // Verifica se precisa de nova página
        if ($pdf->GetY() > $maxY - 30) {
            $pdf->AddPage();
            $startY = $pdf->GetY();
        }

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        // Atleta 1 (ou único atleta)
        $pdf->SetXY($x, $y);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell($largura_nome, 6, substr($atletas[$i]->inscrito, 0, 25), 1, 0, 'L', true);

        // Se tem segundo atleta
        if (isset($atletas[$i + 1])) {
            // Atleta 2
            $pdf->SetXY($x, $y + 12);
            $pdf->Cell($largura_nome, 6, substr($atletas[$i + 1]->inscrito, 0, 25), 1, 0, 'L', true);

            // Linha vertical conectando
            $pdf->Line($x + $largura_nome + 2, $y + 3, $x + $largura_nome + 2, $y + 15);

            // Linha horizontal para vencedor
            $pdf->Line($x + $largura_nome + 2, $y + 9, $x + $largura_nome + $largura_espaco - 2, $y + 9);

            // Quadro do vencedor
            $pdf->SetXY($x + $largura_nome + $largura_espaco, $y + 6);
            $pdf->SetFillColor(255, 255, 200);
            $pdf->Cell($largura_vencedor, 6, 'Vencedor ' . (($i / 2) + 1), 1, 0, 'C', true);

            $pdf->SetY($y + 18);
        } else {
            // BYE - apenas um atleta
            $pdf->SetXY($x + $largura_nome + $largura_espaco, $y);
            $pdf->SetFillColor(200, 255, 200);
            $pdf->Cell($largura_vencedor, 6, 'WO', 1, 0, 'C', true);

            $pdf->SetY($y + 8);
        }

        $pdf->Ln(2);
    }

    $pdf->Ln(10);
}

// ----------------------------------------------------------------------------
// GERAÇÃO DO PDF COM TCPDF
// ----------------------------------------------------------------------------

/**
 * Configuração inicial do documento PDF
 */
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Metadados do documento
$pdf->SetCreator('Sistema de Competições da FPJJI');
$pdf->SetAuthor('Federação Paulista de Jiu-Jitsu Internacional');
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
// PÁGINAS POR CATEGORIA - COM CHAVEAMENTO SIMPLES
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
        // Para absolutos, mostrar gênero, tipo E FAIXA
        $pdf->Cell(0, 10, $chapa['genero'] . ' - ' . $chapa['tipo'] . ' - ' . $chapa['faixa'], 0, 1, 'C');
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
    $largura_academia = 60; // Nome da academia  
    $largura_idade = 15;   // Idade
    $largura_peso = 20;    // Peso

    $pdf->Cell($largura_nome, 8, 'NOME DO ATLETA', 1, 0, 'C');
    $pdf->Cell($largura_academia, 8, 'ACADEMIA', 1, 0, 'C');
    $pdf->Cell($largura_idade, 8, 'IDADE', 1, 0, 'C');
    $pdf->Cell($largura_peso, 8, 'PESO', 1, 1, 'C');

    $pdf->SetFont('helvetica', '', 9);

    // Listagem dos atletas com quebra de linha automática
    foreach ($chapa['atletas'] as $atleta) {
        // Nome do atleta (com quebra de linha se necessário)
        $pdf->Cell($largura_nome, 7, $atleta->inscrito, 1, 0, 'L');

        // Nome da academia (com texto reduzido se necessário)
        $academia = strlen($atleta->academia) > 20 ?
            substr($atleta->academia, 0, 22) . '...' :
            $atleta->academia;
        $pdf->Cell($largura_academia, 7, $academia, 1, 0, 'L');

        // Idade
        $pdf->Cell($largura_idade, 7, calcularIdade($atleta->data_nascimento), 1, 0, 'C');

        // Peso
        $pdf->Cell($largura_peso, 7, $atleta->peso . ' kg', 1, 1, 'C');
    }

    $pdf->Ln(10);

    // ------------------------------------------------------------------------
    // CHAVEAMENTO SIMPLES EM TABELA VERTICAL
    // ------------------------------------------------------------------------

    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'CHAVEAMENTO OFICIAL:', 0, 1);

    // Gera o chaveamento simples
    gerarChaveamentoSimples($chapa['atletas'], $pdf);
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