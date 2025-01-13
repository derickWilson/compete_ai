<?php
// Incluindo arquivos necessários
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once "classes/atletaService.php";
    require_once "classes/eventosServices.php";
    include "func/clearWord.php";
} catch (\Throwable $th) {
    print('['. $th->getMessage() .']');
}

    $conn = new Conexao();
    $ev = new Evento();
    $evserv = new eventosService($conn, $ev);
    $tudo = true;    

if (isset($_GET['id'])) {
    // Usado para listar os detalhes de um único evento
    $eventoId = (int) cleanWords($_GET['id']);
    $eventoDetails = $evserv->getById($eventoId);
    $tudo = false;
} else {//se não esta em um evento especifico,  lista todos
    $list = $evserv->listAll();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventos</title>
</head>
<body>

<?php
    session_start();
    include_once "menu/add_menu.php";
    ?>
    <div class="principal">
        <?php
        // Listar todos os eventos
        if ($tudo) {
            foreach ($list as $valor) { ?>
                <a href='eventos.php?id=<?php echo $valor->id ?>'><h2><?php echo htmlspecialchars($valor->nome); ?></h2></a>
                <?php if (isset($_SESSION['admin']) && $_SESSION['admin']) { ?>
                    | <a href='admin/lista_inscritos.php?id=<?php echo $valor->id ?>'>Ver Inscritos</a>
                <?php } ?>
                <br>
            <?php } ?>
            <br><a href="index.php">Voltar</a>
        <?php
        } else {
            if (isset($eventoDetails)) {// Listar apenas um campeonato
        ?>
                <h1><?php echo htmlspecialchars($eventoDetails->nome); ?></h1>
                <img src="uploads/<?php echo $eventoDetails->imagen; ?>" alt="Imagem do Evento">
                <p>Descrição: <?php echo htmlspecialchars($eventoDetails->descricao); ?></p>
                <p>Data Limite: <?php echo htmlspecialchars($eventoDetails->data_limite); ?></p>
                <p>Preço: <?php echo $eventoDetails->preco; ?></p>

                <?php
                if (isset($_SESSION['logado']) && $_SESSION['logado']) {
                    ?>
                    <form action="inscreverAtleta.php" method="POST">
                        <input type="hidden" name="evento_id" value="<?php echo htmlspecialchars($eventoDetails->id); ?>">
                        <input type="hidden" name="valor" value="<?php echo htmlspecialchars($eventoDetails->preco); ?>">
                        <?php
                        // Caso o tipo de campeonato seja com quimono
                        if ($eventoDetails->tipo_com == 1) {
                            echo '<input type="checkbox" name="com"> Com Quimono ';
                            echo '<input type="checkbox" name="abs_com"> Absoluto Com Quimono ';
                        }

                        // Caso o tipo de campeonato seja sem quimono
                        if ($eventoDetails->tipo_sem == 1) {
                            echo '<input type="checkbox" name="sem"> Sem Quimono ';
                            echo '<input type="checkbox" name="abs_sem"> Absoluto Sem Quimono ';
                        }
                        ?>
                        <input type="submit" value="Inscrever-se">
                    </form>
                <?php
                } else {
                    ?>
                    <p>Você deve estar logado para se inscrever.</p>
                <?php
                }
            } else {
                ?>
                <p>Evento não encontrado.</p>
                <a href="eventos.php">Voltar</a>
            <?php
            }
            ?>
            <br><a href="eventos.php">Voltar</a>
        <?php
        } // Fim da condição de um único evento
        ?>
    </div>
</body>
</html>
