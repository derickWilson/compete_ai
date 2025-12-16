<?php
// admin/novo_patrocinador.php
session_start();
require "../func/is_adm.php";
is_adm();

require_once "../classes/patrocinadorClass.php";

$conn = new Conexao();
$patrocinador = new Patrocinador();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/style.css">
    <title>Novo Patrocinador</title>
</head>
<body>
<?php include "../menu/add_menu.php"; ?>
<?php include "../include_hamburger.php"; ?>
<div class="principal">
    <h2>Adicionar Novo Patrocinador</h2>
    <form action="salvar_patrocinador.php" method="POST" enctype="multipart/form-data">
        <label for="nome">Nome do Patrocinador:</label><br>
        <input type="text" name="nome" id="nome" placeholder="Digite o nome do patrocinador" required><br><br>

        <label for="imagem">Logo/Imagem:</label><br>
        <input type="file" name="imagem" id="imagem" required><br><br>

        <label for="link">Link do Patrocinador:</label><br>
        <input type="url" name="link" id="link" placeholder="https://exemplo.com" required><br><br>

        <input class="botao-acao" type="submit" value="Salvar Patrocinador">
    </form>
</div>
<?php include "../menu/footer.php"; ?>
</body>
</html>