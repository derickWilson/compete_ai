<?php
/**
 * Arquivo: editar_inscricao.php
 * Descrição: Processa a edição de inscrição em eventos, verificando se houve mudança no valor
 *            antes de atualizar a cobrança no Asaas.
 */

session_start();

// Verifica login
if (!isset($_SESSION["logado"]) || !$_SESSION["logado"]) {
    header("Location: index.php");
    exit();
}

try {
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

// Inicializa serviços
$conn = new Conexao();
$atserv = new atletaService($conn, new Atleta());
$eventoServ = new eventosService($conn, new Evento());
$assasService = new AssasService($conn);

// Valida dados do formulário
if (!isset($_POST["evento_id"]) || !isset($_POST["modalidade"])) {
    $_SESSION['erro'] = "Dados incompletos";
    header("Location: eventos_cadastrados.php");
    exit();
}

// Sanitiza inputs
$eventoId = (int) cleanWords($_POST["evento_id"]);
$idAtleta = $_SESSION["id"];
$com = isset($_POST["com"]) ? 1 : 0;
$abCom = isset($_POST["abs_com"]) ? 1 : 0;
$sem = isset($_POST["sem"]) ? 1 : 0;
$abSem = isset($_POST["abs_sem"]) ? 1 : 0;
$modalidade = cleanWords($_POST["modalidade"]);
$taxa = 1; // Taxa padrão (pode ser substituída por config_taxa.php)

try {
    // Obtém dados atuais
    $dadosEvento = $eventoServ->getById($eventoId);
    $inscricao = $atserv->getInscricao($eventoId, $idAtleta);
    
    if (!$dadosEvento || !$inscricao) {
        throw new Exception("Registro não encontrado");
    }

    // Verifica se é evento gratuito
    $eventoGratuito = ($dadosEvento->preco == 0 && $dadosEvento->preco_menor == 0 && $dadosEvento->preco_abs == 0);

    // Calcula novo valor
    $novoValor = calcularValorInscricao($dadosEvento, $_SESSION["idade"], $com, $abCom, $sem, $abSem);
    $novoValorComTaxa = $novoValor * $taxa;
    
    // 1. Atualiza modalidades no banco de dados
    $atserv->editarInscricao($eventoId, $idAtleta, $com, $abCom, $sem, $abSem, $modalidade);

    // 2. Para eventos pagos com cobrança existente
    if (!$eventoGratuito && !empty($inscricao->id_cobranca_asaas)) {
        
        // Verifica se o valor mudou
        $valorAtual = (float) $inscricao->valor_pago;
        $valorMudou = abs($novoValorComTaxa - $valorAtual) > 0.01; // Considera diferenças > 1 centavo
        
        if ($valorMudou) {
            // Atualiza cobrança no Asaas
            $resultado = $assasService->editarCobranca($inscricao->id_cobranca_asaas, [
                'value' => number_format($novoValorComTaxa, 2, '.', ''),
                'dueDate' => $dadosEvento->data_limite,
                'description' => 'Inscrição: ' . $dadosEvento->nome . ' (' . $modalidade . ')'
            ]);
            
            if (!$resultado['success']) {
                throw new Exception("Erro ao atualizar cobrança");
            }
            
            // Atualiza valor no banco de dados
            $atserv->atualizarValorInscricao($eventoId, $idAtleta, $novoValorComTaxa);
        }
    }

    $_SESSION['sucesso'] = "Inscrição atualizada" . ($valorMudou ?? false ? " (valor ajustado)" : "");
    header("Location: inscricao.php?inscricao=" . $eventoId);
    exit();

} catch (Exception $e) {
    error_log("Erro editar_inscricao: " . $e->getMessage());
    $_SESSION['erro'] = $e->getMessage();
    header("Location: " . (isset($eventoId) ? "inscricao.php?inscricao=" . $eventoId : "eventos_cadastrados.php"));
    exit();
}

/**
 * Calcula valor da inscrição baseado nas modalidades
 */
function calcularValorInscricao($evento, $idade, $com, $abCom, $sem, $abSem) {
    $valor = 0;
    $eAdulto = $idade > 15;
    $valorBase = $eAdulto ? $evento->preco : $evento->preco_menor;

    if ($com) $valor += $abCom ? $evento->preco_abs : $valorBase;
    if ($sem) $valor += $abSem ? $evento->preco_abs : $valorBase;
    
    return $valor;
}
?>
