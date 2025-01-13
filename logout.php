<?php
// Inicia a sessão
session_start();

// Destruir todas as variáveis de sessão
$_SESSION = array();

// Se houver um cookie de sessão, excluí-lo
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Finalmente, destrói a sessão
session_destroy();

// Redireciona para a página de login ou página inicial
header("Location: index.php"); // Ou "index.php" para a página inicial
exit();
?>
