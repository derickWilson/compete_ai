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
    
    // Deletar arquivos associados primeiro
    if (!empty($eventoDetails->imagen)) {
        $imagemPath = "../uploads/" . $eventoDetails->imagen;
        if (file_exists($imagemPath)) {
            unlink($imagemPath);
        }
    }
    
    if (!empty($eventoDetails->doc)) {
        $docPath = "../docs/" . $eventoDetails->doc;
        if (file_exists($docPath)) {
            unlink($docPath);
        }
    }
    
    // Deletar do banco de dados
    $query = "DELETE FROM evento WHERE id = :id";
    $stmt = $conn->conectar()->prepare($query);
    $stmt->bindValue(':id', $eventoId, PDO::PARAM_INT);
    $stmt->execute();
    
    $_SESSION['mensagem'] = "Evento excluído com sucesso!";
    
} catch (Exception $e) {
    error_log('Erro ao deletar evento: ' . $e->getMessage());
    $_SESSION['mensagem'] = "Erro ao excluir o evento. Por favor, tente novamente.";
}

header("Location: /eventos.php");
exit();
?>