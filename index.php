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
    <title>FPJJI</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="/estilos/icone.jpeg">
</head>
<body>
    <?php
        include "menu/add_menu.php";
    ?>
<div>
    <?php
        if(isset($_GET["message"]) && $_GET["message"] == 1){
            // no caso da mensagem 1 mostra que a conta não foi validada
            echo "<h3 class='alert' >Cadastro relalizado</h3>";
            echo "<h3> Aguarde sua conta ser Validada</h3>";
        }
    ?>
</div>

<center><h3>Galeria de Fotos</h3></center>
<div class="galeria-carousel">
    <button id="prev" class="carousel-btn">&#8592;</button>

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


    <button id="next" class="carousel-btn">&#8594;</button>
</div>


<center><h3>Proximos Eventos</h3></center>
<?php
// Listar todos os eventos
    foreach ($list as $valor) { ?>
    <div class="campeonato-amostra">
    <a href='eventos.php?id=<?php echo $valor->id ?>' class='clear'>
        <h4><?php echo htmlspecialchars($valor->nome); ?></h4>
    </a>
    <br class='clear'>
    </div>
    <?php }?>
<center class="clear"><h3>Patrocinio</h3></center>
    <div class="patrocinio-container">
        <a class="patrocinador" target="blank" href="https://lsisopradores.ind.br/"><img width="340px" height="140px" src="patrocinio/patrocinador_1.jpeg"></a>
        <a class="patrocinador" target="blank" href="https://multivix.edu.br/ead/"><img width="340px" height="140px" src="patrocinio/patrocinador_2.jpeg"></a>
        <a class="patrocinador" target="blank" href="https://www.instagram.com/lotususinagem_pecas"><img width="340px" height="140px" src="patrocinio/patrocinador_3.jpeg"></a>
    </div>
<?php
include "menu/footer.php";
?>
</body>
<script>
    const slides = document.querySelectorAll('.galeria-slide');
    let currentIndex = 0;

    document.getElementById('next').addEventListener('click', () => {
        slides[currentIndex].classList.remove('ativo');
        currentIndex = (currentIndex + 1) % slides.length;
        slides[currentIndex].classList.add('ativo');
    });

    document.getElementById('prev').addEventListener('click', () => {
        slides[currentIndex].classList.remove('ativo');
        currentIndex = (currentIndex - 1 + slides.length) % slides.length;
        slides[currentIndex].classList.add('ativo');
    });
</script>

</html>