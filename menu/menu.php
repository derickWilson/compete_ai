<header>
    <div class="center-content">
        <div class="logos-mini-container">
            <img src="/estilos/banner1.png" class="logo-mini">
            <img src="/estilos/banner11.png" class="logo-lateral">
        </div>
        <h2 class='blue'>Federação Paulista Jiu-Jitsu Internacional</h2>
        <img src="/estilos/banner11.png" class="logo" alt="FPJJI - Federação Paulista de Jiu-Jitsu Internacional">
        <h2 class='blue'>Jiu-Jitsu Internacional</h2>
    </div>
    <nav>
        <a href="index.php">Home</a>
        <a href="eventos.php">Eventos</a>
        <a href="cadastro_academia.php">Filiar Academia</a>
        <a href="cadastro_atleta.php">Filiar Atleta</a>
        <a href="login.php">Logar</a>
        <a href="regras.php">Regras</a>
    </nav>
    <!-- Menu Hamburguer -->
    <div class="menu-toggle">
        <span></span>
        <span></span>
        <span></span>
    </div>

    <script>
        // Garantir que o botão hamburger fique à esquerda
        if (window.innerWidth <= 1024) {
            const menuToggle = document.querySelector('.menu-toggle');
            if (menuToggle) {
                menuToggle.style.left = '20px';
                menuToggle.style.right = 'auto';
            }

            // Ajustar conteúdo do header
            const centerContent = document.querySelector('.center-content');
            if (centerContent) {
                centerContent.style.paddingLeft = '60px';
                centerContent.style.paddingRight = '0';
            }
        }
    </script>
</header>