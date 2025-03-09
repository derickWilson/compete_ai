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
    <title>Criar evento</title>
</head>
<body>
<?php include "../menu/add_menu.php"; ?>
    <div class="principal">
        <form action="recadastrar_evento.php" method="POST" id="evento" enctype="multipart/form-data">
            Nome do evento <input type="text" id="nome_evento" name="nome_evento" 
            value="<?php echo $eventoDetails->nome; ?>"><br>
            <br>

            Data do Campeopnato <input type="date" id="data_camp" name="data_camp"
            value="<?php echo $eventoDetails->data_limite; ?>"><br>
            
            Local <input type="text" id="local_camp"
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
            <?php $eventoDetails->tipo_com == "com" ? "checked": "";?>>Com Kimono

            <br><input type="checkbox" name="tipo_sem" id="tipo_sem" value="sem"
            <?php $eventoDetails->tipo_sem == "com" ? "checked": "";?>>Sem Kimono

            <br>
            Preco geral<input type="number" name="preco" id="preco" placeholder="Preço por Inscrição acima dos 15 anos"
            value="<?php echo $eventoDetails->preco; ?>"><br>

            Preco para menores de 15<input type="number" name="preco_menor" id="preco_menor" placeholder="Preço por Inscrição abaixo dos 15 anos"
            value="<?php echo $eventoDetails->preco; ?>">
            <br><hr><br><input type="submit" value="editar evento">
        </form>
    </div>
<?php
include "/menu/footer.php";
?>
</body>
</html>