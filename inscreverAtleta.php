<?php
session_start();

// Configuração de logs
ini_set('display_errors', 0);
file_put_contents('asaas_debug.log', "\n\n" . date('Y-m-d H:i:s') . " - Início da inscrição", FILE_APPEND);

// Verificações iniciais
if (!isset($_SESSION['logado'])) {
    $_SESSION['erro'] = "Acesso não autorizado";
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['erro'] = "Método inválido";
    header("Location: eventos.php");
    exit();
}

require_once "classes/eventosServices.php";
require_once "classes/AssasService.php";
require_once "func/clearWord.php";
require_once "config_taxa.php";
require_once "func/database.php";

try {
    $conn = new Conexao();
    $ev = new Evento();
    $evserv = new eventosService($conn, $ev);
    $asaasService = new AssasService($conn);

    // Validação do evento
    $evento_id = (int) cleanWords($_POST['evento_id']);
    $eventoDetails = $evserv->getById($evento_id);
    
    if (!$eventoDetails) {
        throw new Exception("Evento não encontrado");
    }

    // Processa modalidades
    $modalidades = [
        'com' => isset($_POST['com']) ? 1 : 0,
        'sem' => isset($_POST['sem']) ? 1 : 0,
        'abs_com' => isset($_POST['abs_com']) ? 1 : 0,
        'abs_sem' => isset($_POST['abs_sem']) ? 1 : 0
    ];

    $modalidade_escolhida = cleanWords($_POST['modalidade']);

    // Cálculo do valor com taxa
    $valor = ($_SESSION['idade'] > 15) ? $eventoDetails->preco * $taxa : $eventoDetails->preco_menor * $taxa;
    
    if (($modalidades['abs_com'] || $modalidades['abs_sem']) && $eventoDetails->preco_abs > 0) {
        $valor = $eventoDetails->preco_abs * $taxa;
    }

    // Validação dos dados da sessão
    $requiredSession = ['id', 'nome', 'cpf', 'email', 'fone'];
    foreach ($requiredSession as $field) {
        if (empty($_SESSION[$field])) {
            throw new Exception("Dados incompletos na sessão - Campo $field faltando");
        }
    }

    // 1. Inscreve no banco de dados local
    // Após processar as modalidades, adicione:
    $aceite_regulamento = isset($_POST['aceite_regulamento']) ? 1 : 0;
    $aceite_responsabilidade = isset($_POST['aceite_responsabilidade']) ? 1 : 0;
    
    // Verifique se ambos os termos foram aceitos
    if (!$aceite_regulamento || !$aceite_responsabilidade) {
        throw new Exception("Você deve aceitar todos os termos para se inscrever");
    }
    
    // Modifique a chamada de inscrever
    $inscricaoSucesso = $evserv->inscrever(
        $_SESSION['id'],
        $evento_id,
        $modalidades['com'],
        $modalidades['abs_com'],
        $modalidades['sem'],
        $modalidades['abs_sem'],
        $modalidade_escolhida,
        $aceite_regulamento,
        $aceite_responsabilidade
    );
    
    if ($inscricaoSucesso === false) {
        throw new Exception("Falha ao registrar inscrição no banco local");
    }

    // 2. Integração com Asaas
    $dadosAtleta = [
        'id' => $_SESSION['id'],
        'nome' => $_SESSION['nome'],
        'cpf' => $_SESSION['cpf'],
        'email' => $_SESSION['email'],
        'fone' => $_SESSION['fone'],
        'academia' => $_SESSION['academia'] ?? null
    ];

    $customerId = $asaasService->buscarOuCriarCliente($dadosAtleta);
    file_put_contents('asaas_debug.log', "\nCustomer ID: $customerId", FILE_APPEND);

    $descricao = "Inscrição: " . $eventoDetails->nome . " (" . $modalidade_escolhida . ")";
        $cobranca = $asaasService->criarCobranca([
            'customer' => $customerId,
            'value' => $valor,
            'dueDate' => $eventoDetails->data_limite,
            'description' => $descricao,
            'externalReference' => 'EV_' . $evento_id . '_AT_' . $_SESSION['id'],
            'billingType' => 'PIX'
        ]);
    // 3. Atualiza inscrição com dados do pagamento
    $valorNumerico = (float) number_format($valor, 2, '.', '');
    $atualizacao = $asaasService->atualizarInscricaoComPagamento(
        $_SESSION['id'],
        $evento_id,
        $cobranca['payment']['id'],
        AssasService::STATUS_PENDENTE,
        $valorNumerico
    );

    if (!$atualizacao) {
        throw new Exception("Falha ao atualizar dados de pagamento");
    }

    header("Location: eventos_cadastrados.php");
    exit();

} catch (Exception $e) {
    file_put_contents('asaas_error.log', 
        "\nERRO: " . date('Y-m-d H:i:s') .
        "\nMensagem: " . $e->getMessage() .
        "\nArquivo: " . $e->getFile() .
        "\nLinha: " . $e->getLine() .
        "\nTrace: " . $e->getTraceAsString() . "\n",
        FILE_APPEND
    );

    $_SESSION['erro_inscricao'] = "Erro na inscrição: " . $e->getMessage();
    header("Location: evento_detalhes.php?id=" . $evento_id);
    exit();
}
?>