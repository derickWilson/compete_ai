<?php
session_start();

// Ativa logs detalhados para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
file_put_contents('asaas_debug.log', "\n\n--- NOVA TENTATIVA DE INSCRIÇÃO ---\n", FILE_APPEND);

// Verifica autenticação
if (!isset($_SESSION['logado']) {
    $_SESSION['erro'] = "Você precisa estar logado";
    header("Location: login.php");
    exit();
}

// Verifica método POST
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

    // Valida e sanitiza ID do evento
    $evento_id = isset($_POST['evento_id']) ? (int) cleanWords($_POST['evento_id']) : 0;
    $eventoDetails = $evserv->getById($evento_id);
    
    if (!$eventoDetails) {
        throw new Exception("Evento não encontrado");
    }

    // Processa modalidades selecionadas
    $modalidades = [
        'com' => isset($_POST['com']) ? 1 : 0,
        'sem' => isset($_POST['sem']) ? 1 : 0,
        'abs_com' => isset($_POST['abs_com']) ? 1 : 0,
        'abs_sem' => isset($_POST['abs_sem']) ? 1 : 0
    ];

    // Valida pelo menos uma modalidade selecionada
    if (!array_filter($modalidades)) {
        throw new Exception("Selecione pelo menos uma modalidade");
    }

    // Valida modalidade escolhida
    $modalidade_escolhida = isset($_POST['modalidade']) ? cleanWords($_POST['modalidade']) : null;
    if (empty($modalidade_escolhida)) {
        throw new Exception("Modalidade não selecionada");
    }

    // Calcula valor conforme regras de negócio
    $valor = ($_SESSION['idade'] > 15) ? $eventoDetails->preco : $eventoDetails->preco_menor;
    if (($modalidades['abs_com'] || $modalidades['abs_sem']) && $eventoDetails->preco_abs > 0) {
        $valor = $eventoDetails->preco_abs;
    }

    // DEBUG: Log dos dados processados
    file_put_contents('asaas_debug.log', "\nDADOS PROCESSADOS:\n" . print_r([
        'atleta_id' => $_SESSION['id'],
        'evento_id' => $evento_id,
        'modalidades' => $modalidades,
        'modalidade_escolhida' => $modalidade_escolhida,
        'valor' => $valor,
        'idade' => $_SESSION['idade']
    ], true), FILE_APPEND);

    // 1. Faz a inscrição no banco de dados
    $evserv->inscrever(
        $_SESSION['id'],
        $evento_id,
        $modalidades['com'],
        $modalidades['abs_com'],
        $modalidades['sem'],
        $modalidades['abs_sem'],
        $modalidade_escolhida
    );

    // 2. Integração com Asaas
    $asaasService = new AsaasService($conn);
    
    // 2.1. Prepara dados do atleta
    // 2.2. Prepara dados do atleta incluindo a academia
    $dadosAtleta = [
        'id' => $_SESSION['id'],
        'nome' => $_SESSION['nome'],
        'cpf' => $_SESSION['cpf'],
        'email' => $_SESSION['email'],
        'fone' => $_SESSION['fone'],
        'academia' => $_SESSION['academia'] // Adiciona a academia aqui
    ];

    // 2.2. Busca ou cria cliente no Asaas
    $customerId = $asaasService->buscarOuCriarCliente($dadosAtleta);
    file_put_contents('asaas_debug.log', "\nCUSTOMER ID: " . $customerId, FILE_APPEND);

    // 2.3. Cria a cobrança
    $descricao = "Inscrição: " . $eventoDetails->nome . " (" . $modalidade_escolhida . ")";
    $cobranca = $asaasService->criarCobranca([
        'customer' => $customerId,
        'value' => $valor,
        'dueDate' => date('Y-m-d', strtotime('+3 days')),
        'description' => $descricao,
        'billingType' => 'PIX',
        'externalReference' => 'EV_' . $evento_id . '_AT_' . $_SESSION['id']
    ]);

    // DEBUG: Log da resposta da API
    file_put_contents('asaas_debug.log', "\nRESPOSTA ASAAS:\n" . print_r($cobranca, true), FILE_APPEND);

    // 2.4. Atualiza a inscrição com dados do pagamento
    $asaasService->atualizarInscricaoComPagamento(
        $_SESSION['id'],
        $evento_id,
        $cobranca['id'],
        AsaasService::STATUS_PENDENTE,
        $valor
    );

    // 3. Redireciona para comprovante
    header("Location: comprovante.php?id=" . $cobranca['id']);
    exit();

} catch (Exception $e) {
    // Log detalhado do erro
    file_put_contents('asaas_debug.log', 
        "\nERRO:\n" . $e->getMessage() . 
        "\nTRACE:\n" . $e->getTraceAsString(), 
        FILE_APPEND
    );

    // Mensagem amigável para o usuário
    $erroMsg = "Erro no processamento: " . $e->getMessage();
    
    // Se for ambiente de desenvolvimento, mostra detalhes
    if ($_SERVER['SERVER_NAME'] === 'localhost') {
        $erroMsg .= "\n\nDetalhes técnicos:\n" . $e->getTraceAsString();
    }

    $_SESSION['erro_inscricao'] = $erroMsg;
    header("Location: evento_detalhes.php?id=" . $evento_id);
    exit();
}
?>