<?php
session_start();
require "../func/is_adm.php";
is_adm();

require_once "../classes/galeriaClass.php";

$conn = new Conexao();
$galeria = new Galeria();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/style.css">
    <title>Nova Imagem - Galeria</title>
</head>
<body>
<?php include "../menu/add_menu.php"; ?>
<div class="principal">
    <h2>Adicionar Nova Imagem à Galeria</h2>
    <form action="salvar_galeria.php" method="POST" enctype="multipart/form-data">
        <label for="imagem">Imagem:</label><br>
        <input type="file" name="imagem" id="imagem" required><br><br>

        <label for="legenda">Legenda da Imagem:</label><br>
        <input type="text" name="legenda" id="legenda" placeholder="Digite uma legenda para a imagem" required><br><br>

        <input class="botao-acao" type="submit" value="Salvar Imagem">
    </form>
</div>
<?php include "../menu/footer.php"; ?>
</body>
</html>