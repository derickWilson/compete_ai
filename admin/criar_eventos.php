<?php
session_start();
require "../func/is_adm.php";
is_adm();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar evento</title>
</head>
<body>
<?php include "../menu/add_menu.php"; ?>
    <div>
        <form action="cadastrar_evento.php" method="POST" id="evento" enctype="multipart/form-data">
            Nome do evento <input type="text" id="nome_evento" name="nome_evento"><br>
            <br>

            Data do Campeopnato <input type="date" id="data_camp" name="data_camp"><br>
            
            Local <input type="text" id="local_camp"><br>

            <input type="file" name="img_evento" accept="image/*">
            <p>descrição do evento</p>
            <textarea name="desc_Evento" placeholder="descreva o campeopnato">
            </textarea><br>

            data limite <input type="date" id="data_limite" name="data_limite"><br>
            Modalidade:
            <br><input type="checkbox" name="tipo_com" id="tipo_com" value="com">Com Kimono
            <br><input type="checkbox" name="tipo_sem" id="tipo_sem" value="sem">Sem Kimono

            <br><input type="number" name="preco" id="preco" placeholder="Preço por Inscrição">
            <br><hr><br><input type="submit" value="Cadastrar evento">
        </form>
    </div>

    <script>
    </script>
</body>
</html>