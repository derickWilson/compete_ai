<?php
session_start();

// Configuração de logs
ini_set('display_errors', 0); // Desative em produção
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

// Inclui dependências
require_once "classes/eventosServices.php";
require_once "classes/AssasService.php";
require_once "func/clearWord.php";
require_once "func/database.php";

try {
    // Inicializa serviços
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

    // Validação de modalidade selecionada
    $modalidade_escolhida = cleanWords($_POST['modalidade']);

    // Cálculo do valor
    // Cálculo do valor com a taxa de 1,98%
    $taxa = 1.0198;
    $valor = ($_SESSION['idade'] > 15) ? $eventoDetails->preco * $taxa : $eventoDetails->preco_menor * $taxa;
    
    if (($modalidades['abs_com'] || $modalidades['abs_sem']) && $eventoDetails->preco_abs > 0) {
        $valor = $eventoDetails->preco_abs * $taxa;
    }

    // 1. Valide TUDO primeiro (mesmo que "teoricamente" já esteja válido)
    $requiredSession = ['id', 'nome', 'cpf', 'email', 'fone'];
    foreach ($requiredSession as $field) {
        if (empty($_SESSION[$field])) {
            throw new Exception("Dados incompletos na sessão");
        }
    }

    // Em inscreverAtleta.php, antes de criar $dadosAtleta
    $requiredSession = ['id', 'nome', 'cpf', 'email', 'fone'];
    foreach ($requiredSession as $field) {
        if (empty($_SESSION[$field])) {
            error_log("Campo $field faltando na sessão");
            throw new Exception("Dados incompletos na sessão");
        }
    }
    // 2. Integração com Asaas
    $dadosAtleta = [
        'id' => $_SESSION['id'],
        'nome' => $_SESSION['nome'],
        'cpf' => $_SESSION['cpf'],
        'email' => $_SESSION['email'],
        'fone' => $_SESSION['fone'],
        'academia' => $_SESSION['academia'] // Adiciona a academia
    ];

    // 2.1 Busca ou cria cliente
    $customerId = $asaasService->buscarOuCriarCliente($dadosAtleta);
    file_put_contents('asaas_debug.log', "\nCustomer ID: $customerId", FILE_APPEND);
    
    // 1. Inscreve no banco de dados local
    $evserv->inscrever(
        $_SESSION['id'],
        $evento_id,
        $modalidades['com'],
        $modalidades['abs_com'],
        $modalidades['sem'],
        $modalidades['abs_sem'],
        $modalidade_escolhida
    );
    // 2.2 Cria cobrança
    $descricao = "Inscrição: " . $eventoDetails->nome . " (" . $modalidade_escolhida . ")";
    $cobranca = $asaasService->criarCobranca([
        'customer' => $customerId,
        'value' => $valor,
        'dueDate' => $eventoDetails->data_limite, // Usa a data limite do evento
        'description' => $descricao,
        'externalReference' => 'EV_' . $evento_id . '_AT_' . $_SESSION['id']
    ]);

    // 2.3 Atualiza inscrição com dados do pagamento
    $asaasService->atualizarInscricaoComPagamento(
        $_SESSION['id'],
        $evento_id,
        $cobranca['payment']['id'],
        AssasService::STATUS_PENDENTE,
        $valor
    );

    // Redireciona para os eventos
    header("Location: eventos_cadastrados.php");
    exit();

} catch (Exception $e) {
    // Log detalhado do erro
    file_put_contents('asaas_debug.log', 
        "\nERRO: " . $e->getMessage() . 
        "\nTRACE: " . $e->getTraceAsString(), 
        FILE_APPEND
    );

    // Mensagem amigável
    $_SESSION['erro_inscricao'] = "Erro na inscrição: " . $e->getMessage();
    header("Location: evento_detalhes.php?id=" . $evento_id);
    exit();
}
?>