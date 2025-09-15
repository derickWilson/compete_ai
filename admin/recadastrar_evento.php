<?php
session_start();
require __DIR__ . "/../func/is_adm.php";
is_adm();

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    $_SESSION['mensagem'] = "Método de requisição inválido";
    header("Location: /eventos.php");
    exit();
}

require_once __DIR__ . "/../classes/eventosServices.php";
require_once __DIR__ . "/../classes/AssasService.php";
require_once __DIR__ . "/../func/clearWord.php";
require_once __DIR__ . "/../func/calcularIdade.php";
require_once __DIR__ . "/../config_taxa.php";


// Verificação do ID do evento
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    $_SESSION['mensagem'] = "ID do evento inválido";
    header("Location: /eventos.php");
    exit();
}

$conn = new Conexao();
$evento = new Evento();
$eventoService = new eventosService($conn, $evento);
$assasService = new AssasService($conn);

$id = (int) cleanWords($_POST['id']);
$eventoAntigo = $eventoService->getById($id);

if (!$eventoAntigo || !isset($eventoAntigo->id)) {
    $_SESSION['mensagem'] = "Evento não encontrado";
    header("Location: /eventos.php");
    exit();
}

// Inicializa array com os dados atuais do evento
$dadosEvento = [
    'id' => $id,
    'nome' => $eventoAntigo->nome ?? '',
    'local_camp' => $eventoAntigo->local_camp ?? '',
    'data_evento' => $eventoAntigo->data_evento ?? '',
    'descricao' => $eventoAntigo->descricao ?? '',
    'data_limite' => $eventoAntigo->data_limite,
    'tipoCom' => $eventoAntigo->tipo_com ?? 0,
    'tipoSem' => $eventoAntigo->tipo_sem ?? 0,
    'preco' => $eventoAntigo->preco ?? 0,
    'preco_menor' => $eventoAntigo->preco_menor ?? 0,
    'preco_abs' => $eventoAntigo->preco_abs ?? 0,
    'preco_sem' => $eventoAntigo->preco_sem ?? 0, // Adicionado
    'preco_sem_menor' => $eventoAntigo->preco_sem_menor ?? 0, // Adicionado
    'preco_sem_abs' => $eventoAntigo->preco_sem_abs ?? 0, // Adicionado
    'img' => $eventoAntigo->imagen ?? null,
    'doc' => $eventoAntigo->doc ?? null,
    'normal' => $eventoAntigo->normal ?? 0,
    'normal_preco' => $eventoAntigo->normal_preco ?? 0
];

// Processar upload da nova imagem
if (isset($_FILES['imagen_nova']) && $_FILES['imagen_nova']['error'] === UPLOAD_ERR_OK) {
    $imagen = $_FILES['imagen_nova'];
    $extensao = strtolower(pathinfo($imagen['name'], PATHINFO_EXTENSION));

    if (in_array($extensao, ['jpg', 'jpeg', 'png'])) {
        $novoNome = 'img_' . time() . '.' . $extensao;
        $caminhoDestino = __DIR__ . "/../uploads/" . $novoNome;

        if (!empty($dadosEvento['img']) && file_exists(__DIR__ . "/../uploads/" . $dadosEvento['img'])) {
            unlink(__DIR__ . "/../uploads/" . $dadosEvento['img']);
        }

        if (move_uploaded_file($imagen['tmp_name'], $caminhoDestino)) {
            $dadosEvento['img'] = $novoNome;
        } else {
            $_SESSION['mensagem'] = "Erro ao salvar a nova imagem.";
            header("Location: /admin/editar_evento.php?id=" . $id);
            exit();
        }
    } else {
        $_SESSION['mensagem'] = "Formato de imagem inválido. Use JPG, JPEG ou PNG.";
        header("Location: /admin/editar_evento.php?id=" . $id);
        exit();
    }
}

// Processar upload do novo documento (EDITAL)
if (isset($_FILES['nDoc']) && $_FILES['nDoc']['error'] === UPLOAD_ERR_OK) {
    $doc = $_FILES['nDoc'];
    $extensao = strtolower(pathinfo($doc['name'], PATHINFO_EXTENSION));

    if ($extensao === 'pdf') {
        $novoNome = 'doc_' . time() . '.pdf';
        $caminhoDestino = __DIR__ . "/../docs/" . $novoNome;

        if (!file_exists(__DIR__ . "/../docs")) {
            mkdir(__DIR__ . "/../docs", 0755, true);
        }

        if (!empty($dadosEvento['doc']) && file_exists(__DIR__ . "/../docs/" . $dadosEvento['doc'])) {
            unlink(__DIR__ . "/../docs/" . $dadosEvento['doc']);
        }

        if (move_uploaded_file($doc['tmp_name'], $caminhoDestino)) {
            $dadosEvento['doc'] = $novoNome;
        } else {
            $_SESSION['mensagem'] = "Erro ao salvar o novo documento.";
            header("Location: /admin/editar_evento.php?id=" . $id);
            exit();
        }
    } else {
        $_SESSION['mensagem'] = "O edital deve ser um arquivo PDF.";
        header("Location: /admin/editar_evento.php?id=" . $id);
        exit();
    }
}

// Atualizar campos do formulário
if (isset($_POST['nome_evento']) && !empty($_POST['nome_evento'])) {
    $dadosEvento['nome'] = cleanWords($_POST['nome_evento']);
}

if (isset($_POST['local_camp']) && !empty($_POST['local_camp'])) {
    $dadosEvento['local_camp'] = cleanWords($_POST['local_camp']);
}

if (isset($_POST['data_evento']) && !empty($_POST['data_evento'])) {
    $dadosEvento['data_evento'] = cleanWords($_POST['data_evento']);
}

if (isset($_POST['desc_Evento']) && !empty($_POST['desc_Evento'])) {
    $dadosEvento['descricao'] = cleanWords($_POST['desc_Evento']);
}

if (isset($_POST['data_limite']) && !empty($_POST['data_limite'])) {
    $dadosEvento['data_limite'] = cleanWords($_POST['data_limite']);
}

$dadosEvento['tipoCom'] = isset($_POST['tipo_com']) ? 1 : 0;
$dadosEvento['tipoSem'] = isset($_POST['tipo_sem']) ? 1 : 0;
$dadosEvento['normal'] = isset($_POST['normal']) ? 1 : 0;

if (isset($_POST['preco']) && is_numeric($_POST['preco'])) {
    $dadosEvento['preco'] = (float) str_replace(',', '.', cleanWords($_POST['preco']));
}

if (isset($_POST['preco_menor']) && is_numeric($_POST['preco_menor'])) {
    $dadosEvento['preco_menor'] = (float) str_replace(',', '.', cleanWords($_POST['preco_menor']));
}

if (isset($_POST['preco_abs']) && is_numeric($_POST['preco_abs'])) {
    $dadosEvento['preco_abs'] = (float) str_replace(',', '.', cleanWords($_POST['preco_abs']));
}

if (isset($_POST['normal_preco']) && is_numeric($_POST['normal_preco'])) {
    $dadosEvento['normal_preco'] = (float) str_replace(',', '.', cleanWords($_POST['normal_preco']));
}

// Captura dos preços SEM kimono
if (isset($_POST['preco_sem']) && is_numeric($_POST['preco_sem'])) {
    $dadosEvento['preco_sem'] = (float) str_replace(',', '.', cleanWords($_POST['preco_sem']));
}

if (isset($_POST['preco_sem_menor']) && is_numeric($_POST['preco_sem_menor'])) {
    $dadosEvento['preco_sem_menor'] = (float) str_replace(',', '.', cleanWords($_POST['preco_sem_menor']));
}

if (isset($_POST['preco_sem_abs']) && is_numeric($_POST['preco_sem_abs'])) {
    $dadosEvento['preco_sem_abs'] = (float) str_replace(',', '.', cleanWords($_POST['preco_sem_abs']));
}


// Verifica se a data limite foi alterada
$dataLimiteAlterada = ($dadosEvento['data_limite'] != $eventoAntigo->data_limite);

// Verifica se algum preço foi alterado
// Verifica se algum preço foi alterado
$precoAlterado = (
    $dadosEvento['preco'] != $eventoAntigo->preco ||
    $dadosEvento['preco_menor'] != $eventoAntigo->preco_menor ||
    $dadosEvento['preco_abs'] != $eventoAntigo->preco_abs ||
    $dadosEvento['preco_sem'] != $eventoAntigo->preco_sem || // Adicionado
    $dadosEvento['preco_sem_menor'] != $eventoAntigo->preco_sem_menor || // Adicionado
    $dadosEvento['preco_sem_abs'] != $eventoAntigo->preco_sem_abs || // Adicionado
    $dadosEvento['normal_preco'] != $eventoAntigo->normal_preco
);

// Atualizar evento
try {
    foreach ($dadosEvento as $key => $value) {
        $evento->__set($key, $value);
    }
    
    $resultado = $eventoService->editEvento();
    
    if ($resultado && ($precoAlterado || $dataLimiteAlterada)) {
        // Busca todas as inscrições com cobranças pendentes
        $inscricoes = $eventoService->getInscritos($id);
        
        foreach ($inscricoes as $inscricao) {
            if ($inscricao->status_pagamento === 'PENDING' && !empty($inscricao->id_cobranca_asaas)) {
                try {
                    $dadosAtualizacao = [];
                    
                    // Se o preço foi alterado, calcular novo valor
                    if ($precoAlterado) {
                        $novoValor = calcularNovoValor($inscricao, $dadosEvento);
                        $dadosAtualizacao['value'] = $novoValor;
                        
                        // Atualiza o valor no banco de dados
                        $eventoService->atualizarValorInscricao(
                            $inscricao->id, 
                            $inscricao->ide, 
                            $novoValor
                        );
                    }
                    
                    // Se a data limite foi alterada, atualizar data de vencimento
                    if ($dataLimiteAlterada) {
                        // CALCULAR NOVA DATA DE VENCIMENTO (1 DIA ANTES DA NOVA DATA LIMITE)
                        $novaDataLimite = new DateTime($dadosEvento['data_limite']);
                        $novaDataVencimento = $novaDataLimite->modify('-1 day')->format('Y-m-d');
                        
                        $dadosAtualizacao['dueDate'] = $novaDataVencimento;
                    }
                    
                    // ATUALIZAR COBRANÇA NO ASAAS (mesmo que só a data tenha mudado)
                    $assasService->editarCobranca($inscricao->id_cobranca_asaas, $dadosAtualizacao);
                    
                } catch (Exception $e) {
                    error_log("Erro ao atualizar cobrança {$inscricao->id_cobranca_asaas}: " . $e->getMessage());
                    continue;
                }
            }
        }
    }
    
    $_SESSION['mensagem'] = "Evento atualizado com sucesso!";
    header("Location: /eventos.php?id=" . $id);
    exit();
    
} catch (Exception $e) {
    error_log("Erro ao atualizar evento: " . $e->getMessage());
    $_SESSION['mensagem'] = "Erro ao atualizar evento. Por favor, tente novamente.";
    header("Location: /admin/editar_evento.php?id=" . $id);
    exit();
}
/**
 * Calcula o novo valor da inscrição com base na modalidade
 * Se optar por ambas modalidades (com e sem kimono): (preço_com + preço_sem) * 0.6 (40% de desconto)
 */
function calcularNovoValor($inscricao, $dadosEvento)
{
    // Se for evento normal
    if ($dadosEvento['normal']) {
        return $dadosEvento['normal_preco'] * TAXA;
    }

    $idade = calcularIdade($inscricao->data_nascimento);
    $menorIdade = ($idade <= 15);

    $valorTotal = 0;
    $modalidadesSelecionadas = 0;

    // Modalidade COM kimono
    if ($inscricao->mod_com) {
        $precoCom = $menorIdade ? $dadosEvento['preco_menor'] : $dadosEvento['preco'];
        $valorTotal += $precoCom;
        $modalidadesSelecionadas++;
    }

    // Modalidade SEM kimono
    if ($inscricao->mod_sem) {
        $precoSem = $menorIdade ? $dadosEvento['preco_sem_menor'] : $dadosEvento['preco_sem'];
        $valorTotal += $precoSem;
        $modalidadesSelecionadas++;
    }

    // Absoluto COM kimono
    if ($inscricao->mod_ab_com) {
        $valorTotal += $dadosEvento['preco_abs'];
        $modalidadesSelecionadas++;
    }

    // Absoluto SEM kimono
    if ($inscricao->mod_ab_sem) {
        $valorTotal += $dadosEvento['preco_sem_abs'];
        $modalidadesSelecionadas++;
    }

    // Aplica desconto de 40% se fez mais de uma modalidade
    if ($modalidadesSelecionadas > 1) {
        $valorTotal *= 0.6; // 40% de desconto
    }

    // Aplica a taxa
    $valorTotal *= TAXA;

    // Validação de segurança
    if ($valorTotal <= 0) {
        error_log("Valor calculado inválido para inscrição {$inscricao->id}: $valorTotal");
        return $inscricao->valor_pago; // Mantém o valor original em caso de erro
    }

    return $valorTotal;
}
?>