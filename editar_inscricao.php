<?php
/**
 * Arquivo: editar_inscricao.php
 * Descrição: Processa a edição de inscrição em eventos, atualizando tanto no banco de dados
 *            quanto na API de pagamentos Asaas quando necessário.
 * Melhorias:
 * - Verificação de eventos gratuitos
 * - Tratamento mais robusto de erros
 * - Cálculo de valores mais organizado
 * - Mensagens de feedback mais claras
 */

// Inicia a sessão para armazenar mensagens de feedback
session_start();

// Configurações de debug (descomentar para desenvolvimento)
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

// Verifica se o usuário está logado
if (!isset($_SESSION["logado"]) || !$_SESSION["logado"]) {
    header("Location: index.php");
    exit();
}

try {
    // Inclui os arquivos necessários
    require_once "classes/atletaService.php";
    require_once "classes/eventosServices.php";
    require_once "classes/AssasService.php";
    include "func/clearWord.php";
    require_once "config_taxa.php";
} catch (\Throwable $th) {
    $_SESSION['erro'] = "Erro ao carregar recursos do sistema";
    header("Location: eventos_cadastrados.php");
    exit();
}

// Inicializa as classes necessárias
$conn = new Conexao();
$at = new Atleta();
$atserv = new atletaService($conn, $at);
$eventoServ = new eventosServices($conn, new Evento());
$assasService = new AssasService($conn);

// Verifica se todos os campos necessários foram enviados
if (!isset($_POST["evento_id"]) || !isset($_POST["modalidade"])) {
    $_SESSION['erro'] = "Dados do formulário incompletos";
    header("Location: eventos_cadastrados.php");
    exit();
}

// Limpa e armazena os dados do formulário
$eventoId = (int) cleanWords($_POST["evento_id"]);
$com = isset($_POST["com"]) ? 1 : 0;
$abCom = isset($_POST["abs_com"]) ? 1 : 0;
$sem = isset($_POST["sem"]) ? 1 : 0;
$abSem = isset($_POST["abs_sem"]) ? 1 : 0;
$modalidade = cleanWords($_POST["modalidade"]);
$idAtleta = $_SESSION["id"];
$taxa = 1; // Taxa padrão (pode ser substituída por config_taxa.php)

try {
    // 1. Obtém os dados completos do evento e da inscrição
    $dadosEvento = $eventoServ->getById($eventoId);
    if (!$dadosEvento) {
        throw new Exception("Evento não encontrado");
    }

    $inscricao = $atserv->getInscricao($eventoId, $idAtleta);
    if (!$inscricao) {
        throw new Exception("Inscrição não encontrada");
    }

    // 2. Verifica se o evento é gratuito
    $eventoGratuito = ($dadosEvento->preco == 0 && $dadosEvento->preco_menor == 0 && $dadosEvento->preco_abs == 0);

    // 3. Atualiza as modalidades da inscrição no banco de dados
    $atserv->editarInscricao($eventoId, $idAtleta, $com, $abCom, $sem, $abSem, $modalidade);

    // 4. Processa atualização de cobrança apenas para eventos pagos com cobrança existente
    if (!$eventoGratuito && !empty($inscricao->id_cobranca_asaas)) {
        // Calcula o novo valor
        $valor = $this->calcularValorInscricao(
            $dadosEvento, 
            $_SESSION["idade"], 
            $com, 
            $abCom, 
            $sem, 
            $abSem
        );
        
        // Aplica a taxa
        $valorComTaxa = $valor * $taxa;
        $valorFormatado = number_format($valorComTaxa, 2, '.', '');

        // Prepara os dados para atualização no Asaas
        $dadosAtualizacao = [
            'value' => $valorFormatado,
            'dueDate' => $dadosEvento->data_limite,
            'description' => 'Inscrição: ' . $dadosEvento->nome . ' (' . $modalidade . ')'
        ];

        // Atualiza a cobrança no Asaas
        $resultado = $assasService->editarCobranca($inscricao->id_cobranca_asaas, $dadosAtualizacao);
        
        if (!$resultado['success']) {
            error_log("Erro ao atualizar cobrança: " . $resultado['message']);
            throw new Exception("Ocorreu um erro ao atualizar o pagamento");
        }

        // Atualiza o valor pago no banco de dados
        $atserv->atualizarValorInscricao($eventoId, $idAtleta, $valorFormatado);
    }

    // 5. Redireciona com mensagem de sucesso
    $_SESSION['sucesso'] = "Inscrição atualizada com sucesso!";
    header("Location: inscricao.php?inscricao=" . $eventoId);
    exit();

} catch (Exception $e) {
    // Log do erro e redireciona com mensagem de erro
    error_log("Erro ao editar inscrição: " . $e->getMessage());
    $_SESSION['erro'] = "Erro ao editar inscrição: " . $e->getMessage();
    header("Location: " . (isset($eventoId) ? "inscricao.php?inscricao=" . $eventoId : "eventos_cadastrados.php"));
    exit();
}

/**
 * Calcula o valor da inscrição baseado nas modalidades selecionadas
 */
function calcularValorInscricao($evento, $idadeAtleta, $com, $abCom, $sem, $abSem) {
    $valor = 0;
    $eAdulto = $idadeAtleta > 15;

    // Lógica para modalidade COM quimono
    if ($com) {
        $valorBase = $eAdulto ? $evento->preco : $evento->preco_menor;
        $valor += $abCom ? $evento->preco_abs : $valorBase;
    }

    // Lógica para modalidade SEM quimono
    if ($sem) {
        $valorBase = $eAdulto ? $evento->preco : $evento->preco_menor;
        $valor += $abSem ? $evento->preco_abs : $valorBase;
    }

    return $valor;
}
?>
