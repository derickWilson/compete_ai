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
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FPJJI</title>
    <link rel="stylesheet" href="style.css">
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

<center class="clear"><h3>Regras</h3></center>
<center><h3>Patrocinio</h3></center>
<?php
include "menu/footer.php";
?>
</body>
</html>