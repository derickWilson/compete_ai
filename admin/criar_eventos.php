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
    <link rel="icon" href="/estilos/icone.jpeg">
    <!-- Adicionando ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Criar evento</title>
</head>

<body>
    <?php include "../menu/add_menu.php"; ?>
    <?php include "../include_hamburger.php"; ?>
    <div class="principal">
        <form action="cadastrar_evento.php" method="POST" id="evento" enctype="multipart/form-data">
            Nome do evento <input type="text" id="nome_evento" name="nome_evento" required><br>
            <br>

            Data do Campeonato <input type="date" id="data_camp" name="data_camp" required><br>

            Local <input type="text" id="local_camp" name="local_camp" required><br>

            Imagem do Evento <input type="file" name="img_evento" accept="image/*"><br>
            Ementa <input type="file" name="doc" accept="application/pdf"><br>
            <p>Descrição do evento</p>
            <textarea name="desc_Evento" placeholder="Descreva o campeonato" required></textarea><br>

            Data limite <input type="date" id="data_limite" name="data_limite" required><br>

            Modalidade:<br>
            <input type="checkbox" name="normal" id="normal">
            <label for="normal">Evento Normal (Preço único)</label><br>

            <input type="checkbox" name="tipo_com" id="tipo_com">
            <label for="tipo_com">Com Kimono</label><br>

            <input type="checkbox" name="tipo_sem" id="tipo_sem">
            <label for="tipo_sem">Sem Kimono</label><br>
            <br>

            <h4>Preço para Evento Com Quimono</h4>
            Preço para maiores de 15
            <input type="number" name="preco" id="preco" placeholder="Preço por Inscrição" step="0.01" min="0"><br>

            Preço para Absoluto
            <input type="number" name="preco_abs" placeholder="Preço por Absoluto" step="0.01" min="0"><br>

            Preço para menores de 15
            <input type="number" name="preco_menor" id="preco_menor"
                placeholder="Preço por Inscrição abaixo dos 15 anos" step="0.01" min="0"><br>

            <h4>Preços para eventos Sem Kimono</h4>
            Preço para maiores de 15 anos
            <input type="number" name="preco_sem" id="preco_sem" placeholder="Preço por Inscrição SEM Kimono"
                step="0.01" min="0"><br>

            Preço para menores de 15 anos
            <input type="number" name="preco_sem_menor" id="preco_sem_menor"
                placeholder="Preço por Inscrição SEM Kimono abaixo dos 15 anos" step="0.01" min="0"><br>

            Preço para Absoluto SEM Kimono
            <input type="number" name="preco_sem_abs" placeholder="Preço por Absoluto SEM Kimono" step="0.01" min="0">

            <h4>Preço para Evento Normal</h4>
            <input type="number" name="normal_preco" id="normal_preco" placeholder="Preço para Evento Normal"
                step="0.01" min="0">
            <br>
            <hr><br><input type="submit" value="Cadastrar evento">
        </form>
    </div>
    <?php include "../menu/footer.php"; ?>
</body>

</html>