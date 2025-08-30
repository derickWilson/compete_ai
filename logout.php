<?php
// Inicia a sessão
session_start();

// Verificar se há uma mensagem para exibir após logout
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';

// Destruir todas as variáveis de sessão
$_SESSION = array();

// Se houver um cookie de sessão, excluí-lo
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Finalmente, destrói a sessão
session_destroy();

// Redireciona para a página de login com mensagem se aplicável
if ($msg === 'faixa_updated') {
    header("Location: login.php?msg=faixa_updated");
} else {
    header("Location: login.php");
}
exit();
?>