<?php
session_start();
// Ativar logs detalhados
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
file_put_contents('asaas_debug.log', "\n\n--- NOVA INSCRIÇÃO: " . date('Y-m-d H:i:s') . " ---", FILE_APPEND);

// Verificação de autenticação
if (!isset($_SESSION['logado'])) {
    header("Location: login.php");
    exit();
}

// Verificação de método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['erro'] = "Método inválido";
    header("Location: eventos.php");
    exit();
}

try {
    // Inclui dependências
    require_once "classes/eventosServices.php";
    require_once "classes/AssasService.php";
    require_once "func/clearWord.php";
    require_once "func/database.php";

    // Inicializa serviços
    $conn = new Conexao();
    $ev = new Evento();
    $evserv = new eventosService($conn, $ev);
    $asaasService = new AsaasService($conn);

    // Valida ID do evento
    $evento_id = isset($_POST['evento_id']) ? (int) cleanWords($_POST['evento_id']) : 0;
    $eventoDetails = $evserv->getById($evento_id);
    
    if (!$eventoDetails) {
        throw new Exception("Evento não encontrado");
    }

    // Captura modalidades (com validação)
    $mod_com = isset($_POST['com']) ? 1 : 0;
    $mod_sem = isset($_POST['sem']) ? 1 : 0;
    $mod_ab_com = isset($_POST['abs_com']) ? 1 : 0;
    $mod_ab_sem = isset($_POST['abs_sem']) ? 1 : 0;
    $modalidade_escolhida = isset($_POST['modalidade']) ? cleanWords($_POST['modalidade']) : null;

    // Validação de modalidade
    if (empty($modalidade_escolhida)) {
        throw new Exception("Selecione uma modalidade");
    }

    // Dados do atleta (da sessão)
    $atleta_id = $_SESSION['id'];
    $cpf = $_SESSION['cpf'];
    $nome = $_SESSION['nome'];
    $email = $_SESSION['email'];
    $fone = $_SESSION['fone'];

    // Calcula valor (com fallback)
    $valor = ($_SESSION['idade'] > 15) ? $eventoDetails->preco : $eventoDetails->preco_menor;
    $valor = ($mod_ab_com || $mod_ab_sem) && $eventoDetails->preco_abs > 0 
             ? $eventoDetails->preco_abs 
             : $valor;

    // DEBUG: Log dos dados críticos
    file_put_contents('asaas_debug.log', "\nDADOS INSCRIÇÃO: " . json_encode([
        'atleta_id' => $atleta_id,
        'evento_id' => $evento_id,
        'valor' => $valor,
        'modalidade' => $modalidade_escolhida,
        'cpf' => $cpf
    ]), FILE_APPEND);

    // 1. Inscreve no banco de dados local
    $evserv->inscrever(
        $atleta_id,
        $evento_id,
        $mod_com,
        $mod_ab_com, // Atenção à ordem!
        $mod_sem,
        $mod_ab_sem,
        $modalidade_escolhida
    );

    // 2. Integração com Asaas
    $dadosAtleta = [
        'id' => $atleta_id,
        'nome' => $nome,
        'cpf' => $cpf,
        'email' => $email,
        'fone' => $fone
    ];

    // 3. Busca ou cria cliente
    $customerId = $asaasService->buscarOuCriarCliente($dadosAtleta);
    file_put_contents('asaas_debug.log', "\nCUSTOMER ID: " . $customerId, FILE_APPEND);

    // 4. Cria cobrança
    $descricao = "Inscrição: " . $eventoDetails->nome . " (" . $modalidade_escolhida . ")";
    $cobranca = $asaasService->criarCobranca([
        'customer' => $customerId,
        'value' => $valor,
        'dueDate' => date('Y-m-d', strtotime('+3 days')),
        'description' => $descricao,
        'billingType' => 'PIX',
        'externalReference' => 'EV_' . $evento_id . '_AT_' . $atleta_id
    ]);

    // DEBUG: Log da resposta da API
    file_put_contents('asaas_debug.log', "\nRESPOSTA ASAAS: " . print_r($cobranca, true), FILE_APPEND);

    // 5. Atualiza inscrição com dados do pagamento
    $asaasService->atualizarInscricaoComPagamento(
        $atleta_id,
        $evento_id,
        $cobranca['id'],
        AsaasService::STATUS_PENDENTE,
        $valor
    );

    // Sucesso - redireciona para comprovante
    header("Location: comprovante.php?cobranca_id=" . $cobranca['id']);
    exit();

} catch (Exception $e) {
    // Log detalhado do erro
    file_put_contents('asaas_debug.log', 
        "\nERRO: " . $e->getMessage() . 
        "\nTRACE: " . $e->getTraceAsString(), 
        FILE_APPEND
    );

    // Mensagem amigável
    $erroMsg = "Erro no processamento: " . htmlspecialchars($e->getMessage());
    
    // Se for erro de API, mostra detalhes apenas em desenvolvimento
    if (strpos($e->getMessage(), "ASAAS:") !== false && $_SERVER['SERVER_NAME'] === 'localhost') {
        $erroMsg .= "<pre>" . print_r($cobranca ?? [], true) . "</pre>";
    }

    $_SESSION['erro_inscricao'] = $erroMsg;
    header("Location: evento_detalhes.php?id=" . $evento_id);
    exit();
}
?>