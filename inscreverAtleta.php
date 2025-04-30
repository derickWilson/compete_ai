<?php
session_start();
// Verifica se o usuário está logado
if (!isset($_SESSION['logado']) || !$_SESSION['logado']) {
    echo "<p>Você deve estar logado para se inscrever.</p>";
    header("location: eventos.php");
    exit();
}
// Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura e valida os dados do formulário
    // Incluindo arquivos necessários
    // Teste de conexão e instâncias
    try {
        require_once "classes/eventosServices.php";
        require_once "classes/AssasService.php";
        include "func/clearWord.php";
        $conn = new Conexao();
        $ev = new Evento();
        $evserv = new eventosService($conn, $ev);
    } catch (Exception $e) {
        die("Erro na conexão ou instância: " . $e->getMessage());
    }
    $evento_id = isset($_POST['evento_id']) ? (int) cleanWords($_POST['evento_id']) : 0;
    // Verifica se o evento existe
    $eventoDetails = $evserv->getById($evento_id);
    if (!$eventoDetails) {
        echo '<p>Evento não encontrado.</p>';
        header("location: eventos.php");
        exit();
    }
    // Captura as opções do formulário
    $mod_com = isset($_POST['com']) ? 1 : 0;
    $mod_sem = isset($_POST['sem']) ? 1 : 0;
    $mod_ab_com = isset($_POST['abs_com']) ? 1 : 0;
    $mod_ab_sem = isset($_POST['abs_sem']) ? 1 : 0;
    $modalidade_escolhida = cleanWords($_POST["modalidade"]);
    
    // Dados do atleta
    $atleta_id = $_SESSION['id'];


// Dados do atleta — pode pegar do banco ou session
$cpf = $_SESSION["cpf"];
$nome = $_SESSION["nome"];
$email = $_SESSION["email"];
$fone = $_SESSION["fone"];

// Determina valor
$valor = $_SESSION["idade"] > 15 ? $eventoDetails->preco : $eventoDetails->preco_menor;
$descricao = "Inscrição no campeonato: " . $eventoDetails->nome;

$valor = $_SESSION["idade"] > 15 ? $eventoDetails->preco : $eventoDetails->preco_menor;
    
// Aplica preço do absoluto se selecionado
if (($mod_ab_com || $mod_ab_sem) && $eventoDetails->preco_abs > 0) {
    $valor = $eventoDetails->preco_abs;
}

 /******************* */
    try {
// Ordem correta (conforme a declaração):
    $evserv->inscrever(
        $atleta_id, 
        $evento_id, 
        $mod_com, 
        $mod_ab_com,  // 4º parâmetro = abs_com
        $mod_sem,     // 5º parâmetro = sem
        $mod_ab_sem, 
        $modalidade_escolhida
    );        
        // 8. Busca ou cria cliente no Asaas
        $asaasService = new AsaasService($conn);
        $dadosAtleta = [
            'id' => $atleta_id,
            'nome' => $_SESSION['nome'],
            'cpf' => $_SESSION['cpf'],
            'email' => $_SESSION['email'],
            'fone' => $_SESSION['fone']
        ];

        $customerId = $asaasService->buscarOuCriarCliente($dadosAtleta);

        // 9. Cria a cobrança no Asaas
        $descricao = "Inscrição no campeonato: " . $eventoDetails->nome;
        $cobranca = $asaasService->criarCobranca([
            'customer' => $customerId,
            'value' => $valor,
            'dueDate' => date('Y-m-d', strtotime('+3 days')),
            'description' => $descricao,
            'billingType' => 'PIX'
        ]);

        // 10. Atualiza a inscrição com dados do pagamento
        $asaasService->atualizarInscricaoComPagamento(
            $atleta_id,
            $evento_id, 
            $cobranca['id'],
            'PENDING',
            $valor
        );
    } catch (Exception $e) {
        error_log("ERRO ASAAS: " . $e->getMessage() . "\nDados enviados: " . json_encode([
            'customer' => $customerId,
            'value' => $valor,
            'evento' => $evento_id
        ]));
        echo "<p class='error'>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p>Método de requisição inválido.</p>";
}
?>