<?php
// Incluir CSS e JS do menu hamburger
echo '<link rel="stylesheet" href="/menu_hamburger.css">';
echo '<script src="/hamburger.js" defer></script>';

// CSS inline para garantir que funcione imediatamente (LADO ESQUERDO)
echo '
<style>
    /* CSS temporário para garantir que o botão apareça NO LADO ESQUERDO */
    @media (max-width: 1024px) {
        .menu-toggle {
            display: flex !important;
            position: absolute;
            top: 20px;
            left: 20px !important; /* Forçar lado esquerdo */
            right: auto !important;
            flex-direction: column;
            cursor: pointer;
            padding: 10px;
            z-index: 1001;
            background: transparent;
            border: none;
        }
        
        .menu-toggle span {
            height: 3px;
            width: 25px;
            background: #0066cc;
            margin-bottom: 5px;
            display: block;
            border-radius: 2px;
        }
        
        header nav {
            display: none !important;
        }
        
        /* Ajustar o header para não ter conteúdo à esquerda */
        .center-content {
            padding-left: 60px; /* Espaço para o botão hamburger */
        }
    }
    
    @media (min-width: 1025px) {
        .menu-toggle {
            display: none !important;
        }
        
        .center-content {
            padding-left: 0; /* Remover padding em telas grandes */
        }
    }
</style>
';

// Informações do usuário (opcional)
if (isset($_SESSION['logado']) && $_SESSION['logado']) {
    echo '<div id="user-info" style="display: none;">';
    echo '<div class="user-icon">👤</div>';
    if (isset($_SESSION['nome'])) {
        echo '<p><strong>' . htmlspecialchars($_SESSION['nome']) . '</strong></p>';
    }
    if (isset($_SESSION['email'])) {
        echo '<p>' . htmlspecialchars($_SESSION['email']) . '</p>';
    }
    echo '</div>';
}
?>