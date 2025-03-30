<?php
session_start();
require "../func/is_adm.php";
is_adm();
try {
    require_once "../classes/eventosServices.php";
    include "../func/clearWord.php";
} catch (\Throwable $th) {
    print('['. $th->getMessage() .']');
}
    $conn = new Conexao();
    $ev = new Evento();
    $evserv = new eventosService($conn, $ev);
if (isset($_GET['id'])) {
    // Usado para listar os detalhes de um único evento
    $eventoId = (int) cleanWords($_GET['id']);
    $eventoDetails = $evserv->getById($eventoId);
} 
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/style.css">
    <title>Editar evento</title>
</head>
<body>
<?php include "../menu/add_menu.php"; ?>
    <div class="principal">
        <form action="recadastrar_evento.php" method="POST" id="evento" enctype="multipart/form-data">
            Imagen:<br>
            <img src="/uploads/<?php echo $eventoDetails->imagen; ?>" alt="imagen" width="100%" height="500px">
            Nova Imagen<br>
            <input type="file" name="imagen_nova" id="imagen_nova"><br>
            Nome do evento <input type="text" id="nome_evento" name="nome_evento" 
            value="<?php echo $eventoDetails->nome; ?>"><br>
            <br>
            Data do Campeopnato <input type="date" id="data_camp" name="data_camp"
            value="<?php echo $eventoDetails->data_limite; ?>"><br>
            
            Local <input type="text" name="local_camp"
            value="<?php echo $eventoDetails->local_evento; ?>"><br>

            <p>descrição do evento</p>
            <textarea name="desc_Evento" placeholder="descreva o campeopnato">
                <?php 
                    echo $eventoDetails->descricao;
                ?>
            </textarea><br>

            data limite <input type="date" id="data_limite" name="data_limite" 
            value="<?php echo $eventoDetails->data_limite; ?>"><br>
            Modalidade:
            <br><input type="checkbox" name="tipo_com" id="tipo_com" value="com"
            <?php echo $eventoDetails->tipo_com == 1 ? "checked":""; ?>>Com Kimono

            <br><input type="checkbox" name="tipo_sem" id="tipo_sem" value="sem"
            <?php echo $eventoDetails->tipo_sem == 1 ? "checked":""; ?>>Sem Kimono

            <br>
            Preco geral<input type="number" name="preco" id="preco" placeholder="Preço por Inscrição acima dos 15 anos"
            value="<?php echo $eventoDetails->preco; ?>"><br>

            Preco Absoluto<input type="number" name="preco_abs" id="preco" placeholder="Preço por Inscrição no Absoluto"
            value="<?php echo $eventoDetails->preco_abs; ?>"><br>

            Preco para menores de 15<input type="number" name="preco_menor" id="preco_menor" placeholder="Preço por Inscrição abaixo dos 15 anos"
            value="<?php echo $eventoDetails->preco_menor; ?>"><br>
            <input type="hidden" name="id" value="<?php echo $eventoId; ?>">
            <br><hr><br><input type="submit" value="editar evento">
        </form>
    </div>
<?php
include "/menu/footer.php";
?>
</body>
</html>