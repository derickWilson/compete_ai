<?php
session_start();

// Verificações iniciais (usuário logado, método POST, etc.)
if (!isset($_SESSION["logado"])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "Método inválido";
    exit();
}

require_once "classes/atletaService.php";
require_once "classes/AsaasService.php";
require_once "classes/conexao.php";
require_once "classes/eventosServices.php";
require_once "func/clearWord.php";

// Obter dados do formulário
$evento_id = (int) cleanWords($_POST['evento_id']);
$mod_com = isset($_POST['com']) ? 1 : 0;
$mod_sem = isset($_POST['sem']) ? 1 : 0;
$mod_ab_com = isset($_POST['abs_com']) ? 1 : 0;
$mod_ab_sem = isset($_POST['abs_sem']) ? 1 : 0;
$modalidade_escolhida = cleanWords($_POST["modalidade"]);

// Verificar se selecionou alguma modalidade absoluta
$absoluto_selecionado = ($mod_ab_com == 1 || $mod_ab_sem == 1);

// Instanciar serviços
$conn = new Conexao();
$evento = new Evento();
$evserv = new eventosService($conn, $evento);
$atletaService = new atletaService($conn, new Atleta());
$asaasService = new AsaasService(ASAAS_API_URL, ASAAS_TOKEN);
$atletaService->setAsaasService($asaasService);

// Obter detalhes do evento
$eventoDetails = $evserv->getById($evento_id);
if (!$eventoDetails) {
    die("Evento não encontrado");
}

// CALCULAR VALOR COM BASE NAS MODALIDADES SELECIONADAS
$valor = $_SESSION["idade"] > 15 ? $eventoDetails->preco : $eventoDetails->preco_menor;

// Se selecionou absoluto E o evento tem preço especial para absoluto
if ($absoluto_selecionado && $eventoDetails->preco_abs > 0) {
    $valor = $eventoDetails->preco_abs;
}

// Fazer a inscrição no banco de dados
// ... (código existente)

try {
    // Instanciar serviços
    $conn = new Conexao();
    $evento = new Evento();
    $evserv = new eventosService($conn, $evento);
    $atletaService = new atletaService($conn, new Atleta());
    
    // Configurar AsaasService com suas credenciais
    $asaasService = new AsaasService(ASAAS_API_KEY, ASAAS_API_URL);
    $atletaService->setAsaasService($asaasService);
    
    // Criar cobrança
    $cobranca = $atletaService->criarCobrancaInscricao(
        $evento_id,
        $_SESSION['id'],
        $valor,
        $descricao
    );
    
    if (!isset($cobranca['id'])) {
        throw new Exception("Não foi possível gerar a cobrança");
    }
    
    // Redirecionar para página de pagamento
    header("Location: pagamento.php?cobranca=" . $cobranca['id']);
    exit();
    
} catch (Exception $e) {
    $_SESSION['erro_inscricao'] = $e->getMessage();
    header("Location: evento_detalhes.php?id=" . ($evento_id ?? ''));
    exit();
}catch (Exception $e) {
    die("Erro ao processar inscrição: " . $e->getMessage());
}
?>