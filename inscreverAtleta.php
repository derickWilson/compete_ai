<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['logado']) || !$_SESSION['logado']) {
    header("location: eventos.php");
    exit();
}

// Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<p>Método de requisição inválido.</p>";
    exit();
}

require_once "classes/eventosServices.php";
require_once "func/api_asaas.php";
require_once "classes/conexao.php"; // Certifique-se de que esta classe existe
include "func/clearWord.php";

// Cria instâncias
$conn = new Conexao();
$db = $conn->conectar(); // <--- IMPORTANTE: conecta usando o método correto
$evento = new Evento();
$evserv = new eventosService($conn, $evento);
$asaasService = new AsaasService();

// Captura dados do formulário
$evento_id = isset($_POST['evento_id']) ? (int) cleanWords($_POST['evento_id']) : 0;
$mod_com = isset($_POST['com']) ? 1 : 0;
$mod_sem = isset($_POST['sem']) ? 1 : 0;
$mod_ab_com = isset($_POST['abs_com']) ? 1 : 0;
$mod_ab_sem = isset($_POST['abs_sem']) ? 1 : 0;
$modalidade_escolhida = cleanWords($_POST["modalidade"]);
$atleta_id = $_SESSION['id'];

// Verifica se o evento existe
$eventoDetails = $evserv->getById($evento_id);
if (!$eventoDetails) {
    echo '<p>Evento não encontrado.</p>';
    header("location: eventos.php");
    exit();
}

// Dados do atleta
$cpf = $_SESSION["cpf"];
$nome = $_SESSION["nome"];
$email = $_SESSION["email"];
$fone = $_SESSION["fone"];
$grupo = $_SESSION["academia"];

// Verifica/cria cliente
$cliente = $asaasService->listarClientes(['cpfCnpj' => $cpf]);
$cliente_id = isset($cliente['data'][0]['id']) ? $cliente['data'][0]['id'] : null;

if (!$cliente_id) {
    $clienteCriado = $asaasService->criarCliente([
        'name' => $nome,
        'cpfCnpj' => $cpf,
        'email' => $email,
        'phone' => $fone,
        'groupName' => $grupo,
        'externalReference' => "user_{$atleta_id}"
    ]);
    $cliente_id = $clienteCriado['id'] ?? null;
}

if (!$cliente_id) die("Erro ao registrar cliente no Asaas.");

// Define valor da cobrança
$valor = $_SESSION["idade"] > 15 ? $eventoDetails->preco : $eventoDetails->preco_menor;
$descricao = "Inscrição no campeonato: " . $eventoDetails->nome;

// Cria cobrança Pix
$cobranca = $asaasService->criarCobranca([
    'billingType' => 'PIX',
    'customer' => $cliente_id,
    'value' => $valor,
    'dueDate' => date('Y-m-d', strtotime('+7 days')),
    'description' => $descricao
]);

if (!$cobranca || !isset($cobranca["id"])) {
    die("Erro ao gerar cobrança.");
}

$id_cobranca = $cobranca["id"];

// Atualiza a tabela de inscrição com o ID da cobrança
$stmt = $db->prepare("UPDATE inscricao SET cobranca = :cobranca WHERE id_atleta = :id_atleta AND id_evento = :id_evento");
$stmt->execute([
    ':cobranca' => $id_cobranca,
    ':id_atleta' => $atleta_id,
    ':id_evento' => $evento_id
]);

// Realiza a inscrição no banco
try {
    $evserv->inscrever($atleta_id, $evento_id, $mod_com, $mod_sem, $mod_ab_com, $mod_ab_sem, $modalidade_escolhida);
} catch (Exception $e) {
    echo '<p>Erro ao realizar a inscrição: ' . $e->getMessage() . '</p>';
}

// Recupera o payload Pix
$info = $asaasService->enviarRequisicao("payments/{$id_cobranca}/billingInfo");
$payload = $info['pix']['payload'] ?? null;

echo "<p>Inscrição feita com sucesso!</p>";
if ($payload) {
    echo "<p>Copie o código Pix abaixo para pagar:</p>";
    echo "<textarea readonly rows='4' cols='60'>{$payload}</textarea><br>";
} elseif (isset($cobranca['pixQrCodeImage'])) {
    echo "<img src='{$cobranca['pixQrCodeImage']}' alt='PIX QR Code'><br>";
}
echo "<a href='{$cobranca['invoiceUrl']}' target='_blank'>Pagar Agora</a>";
