// hamburger.js - Versão Menu Esquerdo
document.addEventListener('DOMContentLoaded', function() {
    console.log('Script do menu hamburger (esquerdo) carregado');
    
    // Função para criar todos os elementos do menu
    function criarElementosMenu() {
        // Verificar e criar overlay se não existir
        if (!document.querySelector('.menu-overlay')) {
            const menuOverlay = document.createElement('div');
            menuOverlay.className = 'menu-overlay';
            document.body.appendChild(menuOverlay);
            console.log('Overlay criado');
        }
        
        // Verificar e criar menu lateral se não existir
        if (!document.querySelector('.hamburger-menu')) {
            const hamburgerMenu = document.createElement('div');
            hamburgerMenu.className = 'hamburger-menu';
            
            // Obter o conteúdo do menu principal
            const header = document.querySelector('header');
            let menuHTML = '';
            
            if (header) {
                const nav = header.querySelector('nav');
                if (nav) {
                    menuHTML = nav.innerHTML;
                }
            }
            
            // Criar conteúdo do menu hamburger
            hamburgerMenu.innerHTML = `
                <button class="menu-close" aria-label="Fechar menu">&times;</button>
                <div class="hamburger-menu-content">
                    <div class="hamburger-logo">
                        <img src="/estilos/banner11.png" alt="FPJJI" onerror="this.src='/estilos/banner1.png'">
                        <h3>Federação Paulista</h3>
                        <h3>Jiu-Jitsu Internacional</h3>
                    </div>
                    <nav>${menuHTML || '<a href="/">Home</a>'}</nav>
                </div>
            `;
            
            document.body.appendChild(hamburgerMenu);
            console.log('Menu lateral (esquerdo) criado');
        }
        
        // Verificar e criar botão hamburger se não existir
        if (!document.querySelector('.menu-toggle')) {
            const header = document.querySelector('header');
            if (header) {
                const menuToggle = document.createElement('div');
                menuToggle.className = 'menu-toggle';
                menuToggle.setAttribute('aria-label', 'Abrir menu de navegação');
                menuToggle.innerHTML = '<span></span><span></span><span></span>';
                header.appendChild(menuToggle);
                console.log('Botão hamburger (esquerdo) criado');
            }
        }
    }
    
    // Criar elementos imediatamente
    criarElementosMenu();
    
    // Configurar event listeners com pequeno delay
    setTimeout(configurarEventListeners, 100);
    
    function configurarEventListeners() {
        const menuToggle = document.querySelector('.menu-toggle');
        const menuOverlay = document.querySelector('.menu-overlay');
        const hamburgerMenu = document.querySelector('.hamburger-menu');
        const menuClose = document.querySelector('.menu-close');
        
        console.log('Configurando eventos para menu esquerdo:', {
            menuToggle: !!menuToggle,
            menuOverlay: !!menuOverlay,
            hamburgerMenu: !!hamburgerMenu,
            menuClose: !!menuClose
        });
        
        if (!menuToggle || !menuOverlay || !hamburgerMenu) {
            console.error('Elementos essenciais do menu não encontrados. Recriando...');
            criarElementosMenu();
            setTimeout(configurarEventListeners, 200);
            return;
        }
        
        // Abrir menu
        menuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            console.log('Abrindo menu hamburger (esquerdo)');
            
            this.classList.add('active');
            menuOverlay.classList.add('active');
            hamburgerMenu.classList.add('active');
            document.body.classList.add('menu-open');
            
            // Adicionar acessibilidade
            this.setAttribute('aria-expanded', 'true');
            menuClose.focus(); // Foco no botão fechar para melhor acessibilidade
        });
        
        // Fechar menu com overlay
        menuOverlay.addEventListener('click', function() {
            console.log('Fechando menu via overlay');
            fecharMenu();
        });
        
        // Fechar menu com botão X
        if (menuClose) {
            menuClose.addEventListener('click', function(e) {
                e.stopPropagation();
                console.log('Fechando menu via botão X');
                fecharMenu();
            });
        }
        
        // Fechar menu com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && hamburgerMenu.classList.contains('active')) {
                console.log('Fechando menu via ESC');
                fecharMenu();
            }
        });
        
        // Fechar menu ao clicar em um link
        const menuLinks = hamburgerMenu.querySelectorAll('nav a');
        menuLinks.forEach(link => {
            link.addEventListener('click', function() {
                console.log('Fechando menu após clique no link');
                setTimeout(fecharMenu, 100);
            });
        });
        
        // Função auxiliar para fechar menu
        function fecharMenu() {
            const activeToggle = document.querySelector('.menu-toggle');
            const activeOverlay = document.querySelector('.menu-overlay');
            const activeMenu = document.querySelector('.hamburger-menu');
            
            if (activeToggle) {
                activeToggle.classList.remove('active');
                activeToggle.setAttribute('aria-expanded', 'false');
            }
            if (activeOverlay) activeOverlay.classList.remove('active');
            if (activeMenu) activeMenu.classList.remove('active');
            document.body.classList.remove('menu-open');
        }
        
        // Melhorar acessibilidade - fechar com Tab
        document.addEventListener('focusin', function(e) {
            if (hamburgerMenu.classList.contains('active') && 
                !hamburgerMenu.contains(e.target) && 
                e.target !== menuToggle) {
                console.log('Foco fora do menu, fechando...');
                fecharMenu();
            }
        });
    }
    
    // Adicionar estilo para responsividade
    if (!document.querySelector('#hamburger-styles')) {
        const style = document.createElement('style');
        style.id = 'hamburger-styles';
        style.textContent = `
            /* Garantir que o menu hamburger apareça em telas pequenas - ESQUERDA */
            @media (max-width: 1024px) {
                header nav {
                    display: none !important;
                }
                
                .menu-toggle {
                    display: flex !important;
                    position: absolute;
                    top: 20px;
                    left: 20px !important; /* Importante para forçar lado esquerdo */
                    right: auto !important;
                    flex-direction: column;
                    cursor: pointer;
                    padding: 10px;
                    z-index: 1001;
                    background: transparent;
                    border: none;
                }
            }
            
            @media (min-width: 1025px) {
                .menu-toggle,
                .menu-overlay,
                .hamburger-menu {
                    display: none !important;
                }
                
                header nav {
                    display: flex !important;
                }
            }
            
            body.menu-open {
                overflow: hidden;
            }
            
            /* Animações suaves */
            .menu-overlay {
                transition: opacity 0.3s ease;
            }
            
            .hamburger-menu {
                transition: left 0.3s ease !important; /* Importante para animação esquerda */
            }
        `;
        document.head.appendChild(style);
    }
});

// Fallback para garantir que elementos foram criados
setTimeout(function() {
    const elements = {
        menuToggle: document.querySelector('.menu-toggle'),
        menuOverlay: document.querySelector('.menu-overlay'),
        hamburgerMenu: document.querySelector('.hamburger-menu')
    };
    
    console.log('Verificação final menu esquerdo:', elements);
}, 1500);