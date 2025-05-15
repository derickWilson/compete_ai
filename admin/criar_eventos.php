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

            Data do Campeonato <input type="date" id="data_camp" name="data_camp" required><br>

            Local <input type="text" id="local_camp" name="local_camp" required><br>

            Imagem do Evento <input type="file" name="img_evento" accept="image/*"><br>
            Ementa <input type="file" name="doc" accept="application/pdf"><br>
            <p>Descrição do evento</p>
            <textarea name="desc_Evento" placeholder="Descreva o campeonato" required></textarea><br>

            Data limite <input type="date" id="data_limite" name="data_limite" required><br>

            Modalidade:
            <br><input type="checkbox" name="tipo_com" id="tipo_com" value="1"> Com Kimono
            <br><input type="checkbox" name="tipo_sem" id="tipo_sem" value="1"> Sem Kimono
            <br><input type="checkbox" name="normal" id="normal" value="1"> Evento Normal
            <br>

            Preço para maiores de 15
            <input type="number" name="preco" id="preco" placeholder="Preço por Inscrição" step="0.01" min="0">

            Preço para Absoluto
            <input type="number" name="preco_abs" placeholder="Preço por Absoluto" step="0.01" min="0">

            Preço para menores de 15
            <input type="number" name="preco_menor" id="preco_menor"
                placeholder="Preço por Inscrição abaixo dos 15 anos" step="0.01" min="0">

            Preço para Evento Normal
            <input type="number" name="normal_preco" id="normal_preco" placeholder="Preço para Evento Normal"
                step="0.01" min="0" disabled>

            <br>
            <hr><br><input type="submit" value="Cadastrar evento">
        </form>
    </div>

    <script>
        // Habilita/desabilita o campo normal_preco conforme o checkbox
        document.getElementById('normal').addEventListener('change', function () {
            const normalPrecoInput = document.getElementById('normal_preco');
            normalPrecoInput.disabled = !this.checked;
            normalPrecoInput.required = this.checked;
        });
    </script>

    <?php include "../menu/footer.php"; ?>
</body>

</html>