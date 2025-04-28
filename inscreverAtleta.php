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
        $evserv->inscrever($atleta_id, $evento_id, $mod_com, $mod_sem, $mod_ab_com, $mod_ab_sem, $modalidade_escolhida);
        
    
        // 9. Atualiza a inscrição com dados do pagamento
    $asaasService->atualizarInscricaoComPagamento(
        $atleta_id,
        $evento_id,
        $cobranca['id'],
        'PENDING',
        $valor
    );
    } catch (Exception $e) {
        echo '<p>Erro ao realizar a inscrição: ' . $e->getMessage() . '</p>';
    }
} else {
    echo "<p>Método de requisição inválido.</p>";
}
?>