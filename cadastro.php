<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php
    session_start();
    include "menu/add_menu.php";
    ?>
    <div class="principal">
    <form method="post" action="cadastrar.php" enctype="multipart/form-data">
        nome <input name="nome" type="text" placeholder="nome completo" required><br>
        <?php
            if(isset($erro) && $erro == 1){
                echo '<span class = "erro">usuario ja possui conta </span>';
                echo 'email <input name="email" type="email" placeholder="exemplo@email.com" required><br>';
            }else{
                echo 'email <input name="email" type="email" placeholder="exemplo@email.com" required><br>';
            }
        ?>
        senha <input type="password" name="senha" id="senha" required><br>
        Data de Nascimento <input type="date" name="data_nascimento" id="data_nasc" required><br>
        Fone <input maxlength="12" type="tel" name="fone" id="telefone" placeholder="0000000000" required><br>
        Academia/Equipe <input type="text" name="academia" id="academia" required><br>
        Faixa <select id="faixas" name="faixa" required>
            <option value="">Graduação</option>
            <option value="Branca">Branca</option>
            <option value="Crinza">Crinza</option>
            <option value="Amarela">Amarela</option>
            <option value="Laranja">Laranja</option>
            <option value="Verde">Verde</option>
            <option value="Azul">Azul</option>
            <option value="Roxa">Roxa</option>
            <option value="Marrom">Marrom</option>
            <option value="Preta">Preta</option>
            <option value="Coral">Coral</option>
            <option value="Vermelha">Vermelha</option>
            <option value="Preta e Vermelha">Preta e Vermelha</option>
            <option value="Preta e Branca">Preta e Branca</option>
        </select><br>
        <input type="file" name="diploma" id="diploma" accept=".jpg,.jpeg,.png"  style="display: none;"><br>
        Peso <input type="number" name="peso" min="10" step="0.05" required><br>
        <input type="submit" value="Cadastrar"><br>
    </form> 
    <a href="index.php">voltar</a>

    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        let faixa = document.getElementById('faixas');
        let diplomaInput = document.getElementById('diploma');

        faixa.addEventListener("change", function() {
            let graduacoes = ["Preta", "Coral", "Vermelha", "Preta e Vermelha", "Preta e Branca"];
            let selecionado = faixa.value;
            if (graduacoes.includes(selecionado)) {
                diplomaInput.style.display = "block";
            } else {
                diplomaInput.style.display = "none";
            }
        });
    });
    </script>
</body>
</html>
