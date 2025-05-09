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
    <link rel="stylesheet" href="/style.css">
    <title>Criar evento</title>
</head>
<body>
<?php include "../menu/add_menu.php"; ?>
    <div class="principal">
        <form action="cadastrar_evento.php" method="POST" id="evento" enctype="multipart/form-data">
            Nome do evento <input type="text" id="nome_evento" name="nome_evento" required><br>
            <br>

            Data do Campeopnato <input type="date" id="data_camp" name="data_camp" required><br>
            
            Local <input type="text" id="local_camp" name="local_camp" required><br>

            Imagen do Evento  <input type="file" name="img_evento" accept="image/*"><br>
            Ementa<input type="file" name="doc" accept="application/pdf"><br>
            <p>descrição do evento</p>
            <textarea name="desc_Evento" placeholder="descreva o campeopnato" required>
            </textarea><br>

            data limite <input type="date" id="data_limite" name="data_limite" required><br>
            Modalidade:
            <br><input type="checkbox" name="tipo_com" id="tipo_com" value="com">Com Kimono
            <br><input type="checkbox" name="tipo_sem" id="tipo_sem" value="sem">Sem Kimono
            <br>
            Preco para maiores de 15
            <input type="number" name="preco" id="preco" placeholder="Preço por Inscrição">
            Preco para Abosoluto
            <input type="number" name="preco_abs" placeholder="Preço por Absoluto">
            <br>Preco para menores de 15
            <input type="number" name="preco_menor" id="preco_menor" placeholder="Preço por Inscrição abaixo dos 15 anos"><br>
            <br><hr><br><input type="submit" value="Cadastrar evento">
        </form>
    </div>
<?php
include "/menu/footer.php";
?>
</body>
</html>