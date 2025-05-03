<?php
session_start();
require "../func/is_adm.php";
is_adm();

try {
    require_once "../classes/eventosServices.php";
    include "../func/clearWord.php";
} catch (\Throwable $th) {
    error_log('Erro ao carregar dependências: '. $th->getMessage());
    $_SESSION['mensagem'] = "Erro ao carregar a página. Por favor, tente novamente.";
    header("Location: /eventos.php");
    exit();
}

// Verificação segura do ID do evento
if (!isset($_GET['id']) ){
    $_SESSION['mensagem'] = "ID do evento não fornecido.";
    header("Location: /eventos.php");
    exit();
}

$eventoId = (int) cleanWords($_GET['id']);

// Obter informações do evento antes de deletar
$conn = new Conexao();
$ev = new Evento();
$evserv = new eventosService($conn, $ev);

try {
    $eventoDetails = $evserv->getById($eventoId);
    
    if (!$eventoDetails) {
        $_SESSION['mensagem'] = "Evento não encontrado.";
        header("Location: /eventos.php");
        exit();
    }
    $evserv->deletarEvento($eventoId);    
} catch (Exception $e) {
    error_log('Erro ao deletar evento: ' . $e->getMessage());
    $_SESSION['mensagem'] = "Erro ao excluir o evento. Por favor, tente novamente.";
}

header("Location: /eventos.php");
exit();
?>