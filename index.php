<?php
    session_start();
    try {
        require_once "classes/eventosServices.php";
        include "func/clearWord.php";
    } catch (\Throwable $th) {
        print('['. $th->getMessage() .']');
    }
    $conn = new Conexao();
    $ev = new Evento();
    $evserv = new eventosService($conn, $ev);
    $tudo = true;
    //pegar todos    
    $list = $evserv->listAll();
    
    require_once "classes/galeriaClass.php";
    $galeria = new Galeria();
    $galeriaServ = new GaleriaService($conn, $galeria);
    $fotos = $galeriaServ->listGaleria();

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FPJJI - Federação de Jiu-Jitsu</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="/estilos/icone.jpeg">
    <!-- Adicionando ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include "menu/add_menu.php"; ?>
    
    <div class="container">
        <?php if(isset($_GET["message"]) && $_GET["message"] == 1): ?>
            <div class="alert-message success">
                <i class="fas fa-check-circle"></i>
                <h3>Cadastro realizado com sucesso!</h3>
                <p>Aguarde sua conta ser validada pela administração.</p>
            </div>
        <?php endif; ?>

        <h2 class="section-title">Galeria de Fotos</h2>
        
        <div class="galeria-carousel">
            <button id="prev" class="carousel-btn"><i class="fas fa-chevron-left"></i></button>
            
            <div class="galeria-wrapper">
                <?php if (!empty($fotos)): ?>
                    <?php foreach ($fotos as $index => $foto): ?>
                        <div class="galeria-slide <?php echo $index === 0 ? 'ativo' : ''; ?>">
                            <img src="/galeria/<?php echo htmlspecialchars($foto->imagem); ?>" alt="Foto Galeria">
                            <p><?php echo htmlspecialchars($foto->legenda); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="galeria-slide ativo">
                        <p>Nenhuma foto cadastrada na galeria ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <button id="next" class="carousel-btn"><i class="fas fa-chevron-right"></i></button>
        </div>

        <h2 class="section-title">Próximos Eventos</h2>
        
        <div class="eventos-grid">
            <?php foreach ($list as $valor): ?>
            <div class="evento-card <?php echo $valor->normal ? 'evento-normal' : ''; ?>">
                <a href='eventos.php?id=<?php echo $valor->id ?>' class="evento-link">
                    <div class="evento-imagem">
                        <img src="<?php echo !empty($valor->imagen) ? 'uploads/' . htmlspecialchars($valor->imagen) : 'estilos/default-event.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($valor->nome); ?>">
                    </div>
                    <div class="evento-conteudo">
                        <h3 class="evento-titulo"><?php echo htmlspecialchars($valor->nome); ?></h3>
                        <span class="evento-tipo">
                            <?php echo $valor->normal ? 'Evento Normal' : 'Campeonato'; ?>
                        </span>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($list)): ?>
            <div class="nenhum-evento">
                <p>Nenhum evento programado no momento.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <h2 class="section-title">Nossos Patrocinadores</h2>
        
        <div class="patrocinio-container">
            <a class="patrocinador" target="blank" href="https://lsisopradores.ind.br/">
                <img width="340" height="140" src="patrocinio/patrocinador_1.jpeg" alt="Patrocinador 1">
            </a>
            <a class="patrocinador" target="blank" href="https://multivix.edu.br/ead/">
                <img width="340" height="140" src="patrocinio/patrocinador_2.jpeg" alt="Patrocinador 2">
            </a>
            <a class="patrocinador" target="blank" href="https://www.instagram.com/lotususinagem_pecas">
                <img width="340" height="140" src="patrocinio/patrocinador_3.jpeg" alt="Patrocinador 3">
            </a>
        </div>
    </div>
    
    <?php include "menu/footer.php"; ?>
    
    <script>
        // Script melhorado para o carousel
        const slides = document.querySelectorAll('.galeria-slide');
        let currentIndex = 0;
        let autoSlideInterval;

        function showSlide(index) {
            slides.forEach(slide => slide.classList.remove('ativo'));
            slides[index].classList.add('ativo');
        }

        function nextSlide() {
            currentIndex = (currentIndex + 1) % slides.length;
            showSlide(currentIndex);
        }

        function prevSlide() {
            currentIndex = (currentIndex - 1 + slides.length) % slides.length;
            showSlide(currentIndex);
        }

        function startAutoSlide() {
            autoSlideInterval = setInterval(nextSlide, 5000);
        }

        function stopAutoSlide() {
            clearInterval(autoSlideInterval);
        }

        document.getElementById('next').addEventListener('click', () => {
            stopAutoSlide();
            nextSlide();
            startAutoSlide();
        });

        document.getElementById('prev').addEventListener('click', () => {
            stopAutoSlide();
            prevSlide();
            startAutoSlide();
        });

        // Iniciar slideshow automático
        startAutoSlide();

        // Pausar quando o mouse estiver sobre o carousel
        document.querySelector('.galeria-carousel').addEventListener('mouseenter', stopAutoSlide);
        document.querySelector('.galeria-carousel').addEventListener('mouseleave', startAutoSlide);
    </script>
</body>
</html>