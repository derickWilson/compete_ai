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
require_once __DIR__ . "/../classes/AssasService.php"; // Adicionado
include __DIR__ . "/../func/clearWord.php";

// Verificação do ID do evento
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    $_SESSION['mensagem'] = "ID do evento inválido";
    header("Location: /eventos.php");
    exit();
}

$conn = new Conexao();
$evento = new Evento();
$eventoService = new eventosService($conn, $evento);
$assasService = new AssasService($conn); // Instância do serviço Asaas

$id = (int) cleanWords($_POST['id']);
$eventoAntigo = $eventoService->getById($id);

if (!$eventoAntigo || !isset($eventoAntigo->id)) {
    $_SESSION['mensagem'] = "Evento não encontrado";
    header("Location: /eventos.php");
    exit();
}

// [Todo o código de processamento de dados do formulário permanece igual...]

// Verifica se algum preço foi alterado
$precoAlterado = (
    $dadosEvento['preco'] != $eventoAntigo->preco ||
    $dadosEvento['preco_menor'] != $eventoAntigo->preco_menor ||
    $dadosEvento['preco_abs'] != $eventoAntigo->preco_abs ||
    $dadosEvento['normal_preco'] != $eventoAntigo->normal_preco
);

// Atualizar evento
try {
    foreach ($dadosEvento as $key => $value) {
        $evento->__set($key, $value);
    }
    
    $resultado = $eventoService->editEvento();
    
    if ($resultado && $precoAlterado) {
        // Busca todas as inscrições com cobranças pendentes
        $inscricoes = $eventoService->getInscritos($id);
        
        foreach ($inscricoes as $inscricao) {
            if ($inscricao->status_pagamento === 'PENDING' && !empty($inscricao->id_cobranca_asaas)) {
                try {
                    // Determinar o novo valor conforme a modalidade
                    $novoValor = determinarNovoValor($inscricao, $dadosEvento);
                    
                    // Atualiza a cobrança no Asaas
                    $assasService->editarCobranca($inscricao->id_cobranca_asaas, [
                        'billingType' => 'PIX',
                        'value' => $novoValor,
                        'dueDate' => $dadosEvento['data_limite'],
                        'description' => "Inscrição: " . $dadosEvento['nome'] . " (" . $inscricao->modalidade . ")"
                    ]);
                    
                    // Atualiza o valor no banco de dados
                    $eventoService->atualizarValorInscricao(
                        $inscricao->id_atleta, 
                        $inscricao->id_evento, 
                        $novoValor
                    );
                    
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
 * Determina o novo valor com base na modalidade da inscrição
 */
function determinarNovoValor($inscricao, $dadosEvento) {
    if ($inscricao->modalidade === 'NORMAL') {
        return $dadosEvento['normal_preco'];
    }
    
    $idade = calcularIdade($inscricao->data_nascimento);
    $menorIdade = ($idade < 18);
    
    if ($inscricao->mod_com || $inscricao->mod_sem) {
        return $menorIdade ? $dadosEvento['preco_menor'] : $dadosEvento['preco'];
    }
    
    if ($inscricao->mod_ab_com || $inscricao->mod_ab_sem) {
        return $dadosEvento['preco_abs'];
    }
    
    return $inscricao->valor_pago; // Mantém o valor original se não identificar a modalidade
}
?>