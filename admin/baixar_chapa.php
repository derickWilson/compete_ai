<?php
/**
 * Gerar um PDF com as chaves de competição organizadas por categoria,
 * faixa, modalidade e idade para eventos de Jiu-Jitsu
 */
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

function corrigirInscricaoAbsoluto($inscrito)
{
    if ($inscrito->mod_ab_com == 1 && $inscrito->mod_com == 0) {
        $inscrito->mod_com = 1;
    }

    if ($inscrito->mod_ab_sem == 1 && $inscrito->mod_sem == 0) {
        $inscrito->mod_sem = 1;
    }

    return $inscrito;
}

function corrigirMenoresAbsoluto($inscrito)
{
    $idade = calcularIdade($inscrito->data_nascimento);
    
    if ($idade < 15) {
        $inscrito->mod_ab_com = 0;
        $inscrito->mod_ab_sem = 0;
    }
    
    return $inscrito;
}

function agruparFaixa($faixa, $idade)
{
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
    
    return $faixa;
}

function determinarCategoriaEtaria($idade)
{
    return match (true) {
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
}

// Aplica as correções
foreach ($inscritosValidos as $inscrito) {
    $inscrito = corrigirInscricaoAbsoluto($inscrito);
    $inscrito = corrigirMenoresAbsoluto($inscrito);
}

// ----------------------------------------------------------------------------
// CLASSIFICAÇÃO DOS ATLETAS POR CATEGORIA
// ----------------------------------------------------------------------------

$chapeamento = [];

foreach ($inscritosValidos as $inscrito) {
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

    if (empty($tipo))
        continue;

    $idade = calcularIdade($inscrito->data_nascimento);
    $categoria_etaria = determinarCategoriaEtaria($idade);

    if ($eh_absoluto) {
        if ($idade < 15) {
            continue;
        }
        
        $categoria = "ABSOLUTO " . $categoria_etaria;
        $grupoFaixa = $inscrito->faixa;
        $modalidade = 'ABSOLUTO';
    } else {
        $categoria = $categoria_etaria;
        $grupoFaixa = agruparFaixa($inscrito->faixa, $idade);
        $modalidade = $inscrito->modalidade;
    }

    if (empty($grupoFaixa))
        continue;

    // Chave organizada por: categoria_etaria, genero, grupoFaixa, modalidade
    if ($eh_absoluto) {
        $chave = "$categoria_etaria|{$inscrito->genero}|$grupoFaixa|ABSOLUTO";
    } else {
        $chave = "$categoria_etaria|{$inscrito->genero}|$grupoFaixa|$modalidade";
    }

    if (!isset($chapeamento[$chave])) {
        $chapeamento[$chave] = [
            'genero' => $inscrito->genero,
            'tipo' => $tipo,
            'faixa' => $grupoFaixa,
            'categoria' => $categoria,
            'modalidade' => $modalidade,
            'categoria_etaria' => $categoria_etaria,
            'eh_absoluto' => $eh_absoluto,
            'atletas' => []
        ];
    }

    $chapeamento[$chave]['atletas'][] = $inscrito;
}

// ----------------------------------------------------------------------------
// ORDENAÇÃO DAS CATEGORIAS NA SEQUÊNCIA: IDADE, SEXO, FAIXA, CATEGORIA(PESO)
// ----------------------------------------------------------------------------

uksort($chapeamento, function ($a, $b) {
    $partesA = explode('|', $a);
    $partesB = explode('|', $b);

    if (count($partesA) < 4 || count($partesB) < 4) {
        return 0;
    }

    // 1. Ordena por Categoria Etária (Idade)
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
        "OUTROS" => 9
    ];

    $ordemCatA = $orderCategoria[$partesA[0]] ?? 999;
    $ordemCatB = $orderCategoria[$partesB[0]] ?? 999;

    if ($ordemCatA !== $ordemCatB) {
        return $ordemCatA - $ordemCatB;
    }

    // 2. Ordena por Sexo (Masculino primeiro)
    if ($partesA[1] !== $partesB[1]) {
        return $partesA[1] === 'Masculino' ? -1 : 1;
    }

    // 3. Ordena por Faixa
    $orderFaixa = [
        'BRANCA' => 0,
        'CINZA/AMARELA' => 1,
        'LARANJA/VERDE' => 2,
        'AZUL' => 3,
        'ROXA' => 4,
        'MARROM' => 5,
        'PRETA' => 6,
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

    $ordemFaixaA = $orderFaixa[$partesA[2]] ?? 999;
    $ordemFaixaB = $orderFaixa[$partesB[2]] ?? 999;

    if ($ordemFaixaA !== $ordemFaixaB) {
        return $ordemFaixaA - $ordemFaixaB;
    }

    // 4. Ordena por Modalidade (Peso) - Normais primeiro, depois Absolutos
    $eh_absoluto_a = ($partesA[3] === 'ABSOLUTO');
    $eh_absoluto_b = ($partesB[3] === 'ABSOLUTO');

    if ($eh_absoluto_a !== $eh_absoluto_b) {
        return $eh_absoluto_a ? 1 : -1;
    }

    // Se não é absoluto, ordena pelo peso
    if (!$eh_absoluto_a) {
        $numA = (float) preg_replace('/[^0-9.]/', '', $partesA[3]);
        $numB = (float) preg_replace('/[^0-9.]/', '', $partesB[3]);
        
        if ($numA != $numB) {
            return $numA - $numB;
        }
    } else {
        // Para ABSOLUTOS, ordena por faixa novamente (já feito no passo 3)
        // Se necessário, pode-se adicionar ordenação adicional aqui
    }

    return 0;
});

// ----------------------------------------------------------------------------
// PREPARAR DADOS PARA O ÍNDICE
// ----------------------------------------------------------------------------

$indiceCategorias = [];
foreach ($chapeamento as $chave => $chapa) {
    if ($chapa['eh_absoluto']) {
        $descricao = $chapa['categoria_etaria'] . ' - ' . $chapa['genero'] . ' - ' . $chapa['faixa'] . ' - ABSOLUTO';
    } else {
        $descricao = $chapa['categoria_etaria'] . ' - ' . $chapa['genero'] . ' - ' . $chapa['faixa'] . ' - ' . $chapa['modalidade'];
    }
    
    $indiceCategorias[] = [
        'descricao' => $descricao,
        'atletas' => count($chapa['atletas']),
        'dados' => $chapa,
        'chave' => $chave
    ];
}

// ----------------------------------------------------------------------------
// GERAÇÃO DO PDF - COM ÍNDICE PRIMEIRO
// ----------------------------------------------------------------------------

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Metadados
$pdf->SetCreator('Sistema de Competições da FPJJI');
$pdf->SetAuthor('Federação Paulista de Jiu-Jitsu Internacional');
$pdf->SetTitle('Chaves de Competição - ' . $evento->nome);
$pdf->SetSubject('Chaveamento Oficial');

// Configuração
$pdf->SetMargins(15, 15, 15);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(10);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);

// ----------------------------------------------------------------------------
// PÁGINA 1 - CAPA
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

$pdf->Ln(10);
$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(0, 6, 'Este documento contém as chaves oficiais de competição organizadas por categoria, faixa, modalidade e idade.', 0, 'C');
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'I', 10);
$totalCategorias = count($chapeamento);
$totalAtletas = count($inscritosValidos);
$pdf->MultiCell(0, 6, "Total de categorias: $totalCategorias\nTotal de atletas inscritos: $totalAtletas", 0, 'C');

// ----------------------------------------------------------------------------
// PÁGINA 2 - ÍNDICE (criado ANTES das categorias)
// ----------------------------------------------------------------------------

$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 15, 'ÍNDICE DE CATEGORIAS', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Ln(5);

// Cabeçalho do índice
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(120, 8, 'CATEGORIA', 1, 0, 'L', true);
$pdf->Cell(30, 8, 'ATLETAS', 1, 0, 'C', true);
$pdf->Cell(20, 8, 'PÁGINA', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 9);
$pdf->SetFillColor(255, 255, 255);

// Array para armazenar números de página das categorias
$paginasCategorias = [];

// Preenche o índice com números de página estimados
$paginaAtual = 3; // Capa=1, Índice=2, Primeira categoria=3
foreach ($indiceCategorias as $idx => $categoria) {
    // Calcula quantas páginas esta categoria vai precisar
    $numAtletas = $categoria['atletas'];
    $linhasPorPagina = 25; // Aproximadamente
    $paginasNecessarias = ceil($numAtletas / $linhasPorPagina) + 1; // +1 para o chaveamento
    
    // Adiciona ao índice
    $pdf->Cell(120, 8, substr($categoria['descricao'], 0, 60), 1, 0, 'L');
    $pdf->Cell(30, 8, $numAtletas, 1, 0, 'C');
    $pdf->Cell(20, 8, $paginaAtual, 1, 1, 'C');
    
    // Armazena o número da página para referência
    $paginasCategorias[$idx] = $paginaAtual;
    
    // Atualiza para próxima categoria
    $paginaAtual += $paginasNecessarias;
}

// Resumo do índice
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 8, 'RESUMO:', 0, 1);
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 6, 'Total de Categorias: ' . count($chapeamento), 0, 1);
$pdf->Cell(0, 6, 'Total de Atletas: ' . $totalAtletas, 0, 1);

// ----------------------------------------------------------------------------
// PÁGINAS DAS CATEGORIAS
// ----------------------------------------------------------------------------

// Função para gerar chaveamento
function gerarChaveamentoSimples($atletas, $pdf)
{
    $numAtletas = count($atletas);
    if ($numAtletas == 0)
        return;

    $pdf->SetFont('helvetica', '', 9);

    $largura_nome = 60;
    $largura_vencedor = 60;
    $largura_espaco = 10;

    $startY = $pdf->GetY();
    $maxY = $pdf->GetPageHeight() - 30;

    for ($i = 0; $i < $numAtletas; $i += 2) {
        if ($pdf->GetY() > $maxY - 30) {
            $pdf->AddPage();
            $startY = $pdf->GetY();
        }

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        // Atleta 1
        $pdf->SetXY($x, $y);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell($largura_nome, 6, substr($atletas[$i]->inscrito, 0, 25), 1, 0, 'L', true);

        if (isset($atletas[$i + 1])) {
            // Atleta 2
            $pdf->SetXY($x, $y + 12);
            $pdf->Cell($largura_nome, 6, substr($atletas[$i + 1]->inscrito, 0, 25), 1, 0, 'L', true);

            // Linha vertical
            $pdf->Line($x + $largura_nome + 2, $y + 3, $x + $largura_nome + 2, $y + 15);

            // Linha horizontal
            $pdf->Line($x + $largura_nome + 2, $y + 9, $x + $largura_nome + $largura_espaco - 2, $y + 9);

            // Quadro do vencedor
            $pdf->SetXY($x + $largura_nome + $largura_espaco, $y + 6);
            $pdf->SetFillColor(255, 255, 200);
            $pdf->Cell($largura_vencedor, 6, 'Vencedor ' . (($i / 2) + 1), 1, 0, 'C', true);

            $pdf->SetY($y + 18);
        } else {
            // BYE
            $pdf->SetXY($x + $largura_nome + $largura_espaco, $y);
            $pdf->SetFillColor(200, 255, 200);
            $pdf->Cell($largura_vencedor, 6, 'WO', 1, 0, 'C', true);

            $pdf->SetY($y + 8);
        }

        $pdf->Ln(2);
    }

    $pdf->Ln(10);
}

// Agora gera as categorias
foreach ($indiceCategorias as $idx => $categoriaItem) {
    $chapa = $categoriaItem['dados'];
    
    // Embaralha se necessário
    if ($embaralhar) {
        shuffle($chapa['atletas']);
    }

    // Nova página para a categoria
    $pdf->AddPage();
    
    // Cabeçalho da categoria
    $pdf->SetFont('helvetica', 'B', 14);

    if ($chapa['eh_absoluto']) {
        $pdf->Cell(0, 10, $chapa['categoria_etaria'] . ' - ' . $chapa['genero'] . ' - ' . $chapa['faixa'] . ' - ABSOLUTO', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 8, 'Tipo: ' . $chapa['tipo'], 0, 1, 'C');
    } else {
        $pdf->Cell(0, 10, $chapa['categoria_etaria'] . ' - ' . $chapa['genero'] . ' - ' . $chapa['faixa'] . ' - ' . $chapa['modalidade'], 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 8, 'Tipo: ' . $chapa['tipo'], 0, 1, 'C');
    }

    // Informações
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(0, 6, 'Número de atletas: ' . count($chapa['atletas']), 0, 1, 'C');
    $pdf->Cell(0, 6, 'Página: ' . $paginasCategorias[$idx], 0, 1, 'C');
    
    $pdf->Ln(2);
    $pdf->SetFont('helvetica', '', 12);

    // Tabela de atletas
    $pdf->SetFont('helvetica', 'B', 10);
    $largura_nome = 70;
    $largura_academia = 60;
    $largura_idade = 15;
    $largura_peso = 20;

    // Cabeçalho
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell($largura_nome, 8, 'NOME DO ATLETA', 1, 0, 'C', true);
    $pdf->Cell($largura_academia, 8, 'ACADEMIA', 1, 0, 'C', true);
    $pdf->Cell($largura_idade, 8, 'IDADE', 1, 0, 'C', true);
    $pdf->Cell($largura_peso, 8, 'PESO', 1, 1, 'C', true);

    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetFillColor(255, 255, 255);

    // Lista de atletas
    foreach ($chapa['atletas'] as $atleta) {
        $pdf->Cell($largura_nome, 7, $atleta->inscrito, 1, 0, 'L');

        $academia = strlen($atleta->academia) > 20 ?
            substr($atleta->academia, 0, 22) . '...' :
            $atleta->academia;
        $pdf->Cell($largura_academia, 7, $academia, 1, 0, 'L');

        $pdf->Cell($largura_idade, 7, calcularIdade($atleta->data_nascimento), 1, 0, 'C');
        $pdf->Cell($largura_peso, 7, $atleta->peso . ' kg', 1, 1, 'C');
    }

    $pdf->Ln(10);

    // Chaveamento
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'CHAVEAMENTO OFICIAL:', 0, 1);
    gerarChaveamentoSimples($chapa['atletas'], $pdf);
}

// ----------------------------------------------------------------------------
// PÁGINA FINAL - RESUMO
// ----------------------------------------------------------------------------

$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 15, 'RESUMO ESTATÍSTICO DO EVENTO', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Ln(10);

// Estatísticas
$masculino = 0;
$feminino = 0;
foreach ($inscritosValidos as $inscrito) {
    if ($inscrito->genero === 'Masculino') $masculino++;
    else $feminino++;
}

$comKimono = 0;
$semKimono = 0;
$absolutoCom = 0;
$absolutoSem = 0;

foreach ($inscritosValidos as $inscrito) {
    if ($inscrito->mod_com == 1) $comKimono++;
    if ($inscrito->mod_sem == 1) $semKimono++;
    if ($inscrito->mod_ab_com == 1) $absolutoCom++;
    if ($inscrito->mod_ab_sem == 1) $absolutoSem++;
}

// Tabela de estatísticas
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(80, 10, 'ESTATÍSTICAS GERAIS', 0, 1);
$pdf->SetFont('helvetica', '', 10);

$pdf->Cell(80, 8, 'Total de Categorias:', 0, 0);
$pdf->Cell(0, 8, $totalCategorias, 0, 1);

$pdf->Cell(80, 8, 'Total de Atletas:', 0, 0);
$pdf->Cell(0, 8, $totalAtletas, 0, 1);

$pdf->Cell(80, 8, 'Atletas Masculinos:', 0, 0);
$pdf->Cell(0, 8, $masculino, 0, 1);

$pdf->Cell(80, 8, 'Atletas Femininos:', 0, 0);
$pdf->Cell(0, 8, $feminino, 0, 1);

$pdf->Ln(5);

$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(80, 10, 'DISTRIBUIÇÃO POR MODALIDADE', 0, 1);
$pdf->SetFont('helvetica', '', 10);

$pdf->Cell(80, 8, 'Com Kimono:', 0, 0);
$pdf->Cell(0, 8, $comKimono, 0, 1);

$pdf->Cell(80, 8, 'Sem Kimono:', 0, 0);
$pdf->Cell(0, 8, $semKimono, 0, 1);

$pdf->Cell(80, 8, 'Absoluto Com Kimono:', 0, 0);
$pdf->Cell(0, 8, $absolutoCom, 0, 1);

$pdf->Cell(80, 8, 'Absoluto Sem Kimono:', 0, 0);
$pdf->Cell(0, 8, $absolutoSem, 0, 1);

$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 10);
$pdf->MultiCell(0, 6, 'Documento gerado automaticamente pelo Sistema de Competições da FPJJI.', 0, 'C');
$pdf->MultiCell(0, 6, 'Data de geração: ' . date('d/m/Y H:i:s'), 0, 'C');

// ----------------------------------------------------------------------------
// SAÍDA DO DOCUMENTO
// ----------------------------------------------------------------------------

$pdf->Output('chaves_competicao_evento_' . $eventoId . '.pdf', 'D');
exit;
?>