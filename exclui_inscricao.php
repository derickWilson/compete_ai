<?php
session_start();
if (!isset($_SESSION["logado"]) || !$_SESSION["logado"]) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET["id"])) {
    header("Location: eventos_cadastrados.php");
    exit();
}

require_once "classes/atletaService.php";
require_once "classes/AssasService.php";
require_once "func/clearWord.php";
require_once "func/database.php";

try {
    $conn = new Conexao();
    $at = new Atleta();
    $atserv = new atletaService($conn, $at);
    $asaas = new AsaasService($conn);
} catch (\Throwable $th) {
    die('Erro ao iniciar serviços: ' . $th->getMessage());
}

// Pega dados do atleta e evento
$evento = cleanWords($_GET["id"]);
$atleta = $_SESSION["id"];

// Busca cobrança vinculada à inscrição
$inscricao = $atserv->getInscricao($evento, $atleta);

if ($inscricao && !empty($inscricao->id_cobranca_asaas)) {
    try {
        $asaas->deletarCobranca($inscricao->id_cobranca_asaas);
    } catch (Exception $e) {
        error_log("Erro ao deletar cobrança no Asaas: " . $e->getMessage());
        // Você pode exibir um alerta ao usuário, se necessário.
    }
}

// Exclui inscrição do banco de dados
$atserv->excluirInscricao($evento, $atleta);

// Redireciona
header("Location: eventos_cadastrados.php");
exit();
?>
