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
    //********** API*/
    require_once "func/api_asaas.php";

// Dados do atleta — pode pegar do banco ou session
$cpf = $_SESSION["cpf"];
$nome = $_SESSION["nome"];
$email = $_SESSION["email"];
$fone = $_SESSION["fone"];
$grupo = "Nome da Academia";

// Verifica/Cria cliente
$cliente_id = buscarClienteAsaas($cpf);
if (!$cliente_id) {
    $cliente_id = criarClienteAsaas($nome, $cpf, $email, $fone, $referencia, $grupo);
}
if (!$cliente_id) die("Erro ao registrar cliente no Asaas.");

// Determina valor
$valor = $_SESSION["idade"] > 15 ? $eventoDetails->preco : $eventoDetails->preco_menor;
$descricao = "Inscrição no campeonato: " . $eventoDetails->nome;
$referencia_pag = "evento_{$evento_id}_user_{$_SESSION['id']}";

// Cria cobrança
$cobranca = criarCobrancaAsaas($cliente_id, $valor, $descricao, $referencia_pag);

if ($cobranca && isset($cobranca["id"])) {
    $id_cobranca = $cobranca["id"];
    $query = "UPDATE inscricao SET cobranca = '$id_cobranca' WHERE id_atleta = $atleta_id AND id_evento = $evento_id";
    $conn->query($query);

    echo "<p>Inscrição feita com sucesso! Escaneie o QR Code para pagar:</p>";
    echo "<img src='{$cobranca['pixQrCodeImage']}' alt='PIX QR Code'>";
    echo "<br><a href='{$cobranca['invoiceUrl']}' target='_blank'>Pagar Agora</a>";
} else {
    echo "Erro ao gerar cobrança.";
}
    /******************* */
    try {

        $evserv->inscrever($atleta_id, $evento_id, $mod_com, $mod_sem, $mod_ab_com, $mod_ab_sem, $modalidade_escolhida);
    } catch (Exception $e) {
        echo '<p>Erro ao realizar a inscrição: ' . $e->getMessage() . '</p>';
    }
} else {
    echo "<p>Método de requisição inválido.</p>";
}
?>