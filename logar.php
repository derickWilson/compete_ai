<?php
session_start();
ob_start();

// Prevenir acesso direto
define('LOGIN_ACCESS', true);

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php");
    exit();
}

// Verificar se já está logado
if (isset($_SESSION['logado']) && $_SESSION['logado']) {
    header("Location: pagina_pessoal.php");
    exit();
}

try {
    require_once "classes/atletaService.php";
    include "func/clearWord.php";
    require_once __DIR__ . "/func/security.php";
    
    $spamDetector = new SpamDetector();
    if ($spamDetector->containsSpam($_POST['usuario'] ?? '')) {
        throw new Exception("Credenciais inválidas");
    }
    // Validação dos campos
    $camposObrigatorios = ['usuario', 'senha'];
    foreach ($camposObrigatorios as $campo) {
        if (empty($_POST[$campo])) {
            header('Location: login.php?erro=2'); // Campos obrigatórios
            exit();
        }
    }

    // Sanitização
    $email = trim($_POST["usuario"]); // Remove apenas espaços
    $email = filter_var($email, FILTER_SANITIZE_EMAIL); // Sanitização segura para email    
    $senha = $_POST["senha"]; // Não limpar a senha (pode remover caracteres importantes)

    // Validação de email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: login.php?erro=4'); // Email inválido
        exit();
    }

    // Instanciar e autenticar
    $atleta = new Atleta();
    $conn = new Conexao();

    $atleta->__set("email", $email);
    $atleta->__set("senha", $senha); // A senha será hasheada no service

    $attServ = new atletaService($conn, $atleta);
    $attServ->logar();

} catch (PDOException $e) {
    // Erro de banco de dados
    error_log("PDO Error in login: " . $e->getMessage());
    header('Location: login.php?erro=3'); // Erro de sistema
    exit();

} catch (Exception $e) {
    // Outros erros
    error_log("General Error in login: " . $e->getMessage());
    header('Location: login.php?erro=5'); // Erro genérico
    exit();
}

ob_end_flush();
?>