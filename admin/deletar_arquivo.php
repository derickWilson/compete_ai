<?php
session_start();
require __DIR__ . "/../func/is_adm.php";
is_adm();

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    $_SESSION['mensagem'] = "Método de requisição inválido";
    header("Location: /eventos.php");
    exit();
}

try {
    require_once __DIR__ . "/../classes/eventosServices.php";
    require_once __DIR__ . "/../func/clearWord.php";
    // Validar entrada
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        throw new Exception("ID do evento inválido");
    }

    if (!isset($_POST['tipo']) || !in_array($_POST['tipo'], ['imagen', 'doc', 'chaveamento'])) {
        throw new Exception("Tipo de arquivo inválido");
    }

    if (!isset($_POST['arquivo']) || empty(trim($_POST['arquivo']))) {
        throw new Exception("Nome do arquivo inválido");
    }

    $idEvento = (int) cleanWords($_POST['id']);
    $tipo = cleanWords($_POST['tipo']);
    $arquivo = cleanWords($_POST['arquivo']);

    $conn = new Conexao();
    $evento = new Evento();
    $eventoService = new eventosService($conn, $evento);

    // Deletar arquivo
    $resultado = $eventoService->deletarArquivo($idEvento, $tipo, $arquivo);

    if ($resultado) {
        $_SESSION['mensagem'] = "Arquivo excluído com sucesso!";
    } else {
        throw new Exception("Falha ao excluir o arquivo");
    }


    header("Location: /admin/editar_evento.php?id=" . $idEvento);
    exit();

} catch (Exception $e) {
    // Capturar exceções e mostrar mensagem amigável
    error_log("Erro em delete_event_file.php: " . $e->getMessage());
    $_SESSION['mensagem'] = "Erro: " . $e->getMessage();
}
?>