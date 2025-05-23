<?php
// Deve ser a PRIMEIRA linha do arquivo, sem espaços antes!
declare(strict_types=1);
// Iniciar buffer de saída
ob_start();
// Iniciar sessão ANTES de qualquer saída
session_start();
if (isset($_SESSION["logado"])) {
    header("Location: index.php");
    exit();
} else {
    require_once "classes/atletaService.php";
    try {
        // Obtenha os inscritos 
        $conn = new Conexao();
        $atleta = new Atleta();
        $ev = new atletaService($conn, $atleta);
        $academias = $ev->getAcademias();
    } catch (Exception $e) {
        die("Erro ao obter inscritos: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filiar Academia</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="/estilos/icone.jpeg">
</head>

<body>
    <?php
    include "menu/add_menu.php";
    ?>
    <div class="principal">
        <form method="post" action="cadastrar.php" enctype="multipart/form-data">
            <h3>Dados da academia</h3>
            Nome da Academia/Equipe <input type="text" name="academia" id="academia" required><br>
            CEP da academia<input maxlength="20" type="text" name="cep" id="cep" required><br>
            Cidade<input maxlength="50" type="text" name="cidade" id="cidade" required><br>
            Estado
            <select name="estado" id="estado">
                <option value="AC">Acre (AC)</option>
                <option value="AL">Alagoas (AL)</option>
                <option value="AP">Amapá (AP)</option>
                <option value="AM">Amazonas (AM)</option>
                <option value="BA">Bahia (BA)</option>
                <option value="CE">Ceará (CE)</option>
                <option value="DF">Distrito Federal (DF)</option>
                <option value="ES">Espírito Santo (ES)</option>
                <option value="GO">Goiás (GO)</option>
                <option value="MA">Maranhão (MA)</option>
                <option value="MT">Mato Grosso (MT)</option>
                <option value="MS">Mato Grosso do Sul (MS)</option>
                <option value="MG">Minas Gerais (MG)</option>
                <option value="PA">Pará (PA)</option>
                <option value="PB">Paraíba (PB)</option>
                <option value="PR">Paraná (PR)</option>
                <option value="PE">Pernambuco (PE)</option>
                <option value="PI">Piauí (PI)</option>
                <option value="RJ">Rio de Janeiro (RJ)</option>
                <option value="RN">Rio Grande do Norte (RN)</option>
                <option value="RS">Rio Grande do Sul (RS)</option>
                <option value="RO">Rondônia (RO)</option>
                <option value="RR">Roraima (RR)</option>
                <option value="SC">Santa Catarina (SC)</option>
                <option value="SP">São Paulo (SP)</option>
                <option value="SE">Sergipe (SE)</option>
                <option value="TO">Tocantins (TO)</option>
            </select><br>
            <h3>Dados do Responsavel</h3>
            Foto 3x4<br>
            <input type="file" placeholder="FOTO" name="foto" id="foto" accept=".jpg,.jpeg,.png" required><br>

            Nome do Responsavel <input name="nome" type="text" placeholder="nome completo" required><br>
            CPF<input name="cpf" type="text" placeholder="0000000000" maxlength="19" required><br>
            Genero
            <select name="genero">
                <option value="Masculino">Masculino</option>
                <option value="Feminino">Feminino</option>
            </select><br>
            <?php
            if (isset($erro) && $erro == 1) {
                echo '<span class = "erro">usuario ja possui conta </span><br>';
                echo 'email <input name="email" type="email" placeholder="exemplo@email.com" required><br>';
            } else {
                echo 'email <input name="email" type="email" placeholder="exemplo@email.com" required><br>';
            }
            ?>
            senha <input type="password" name="senha" id="senha" required><br>
            Data de Nascimento <input type="date" name="data_nascimento" id="data_nasc" required><br>
            Telefone<br>
            <input type="text" name="ddd" value="+55" style="width: 50px;">
            <input maxlength="15" type="tel" name="fone" id="telefone" placeholder="DDD + número (ex: 11987654321)"
                required>
            <br>
            Faixa do Responsavel <select id="faixas" name="faixa" required>
                <option value="">Graduação</option>
                <option value="Branca">Branca</option>
                <option value="Cinza">Cinza</option>
                <option value="Amarela">Amarela</option>
                <option value="Laranja">Laranja</option>
                <option value="Verde">Verde</option>
                <option value="Azul">Azul</option>
                <option value="Roxa">Roxa</option>
                <option value="Marrom">Marrom</option>
                <option value="Preta">Preta</option>
            </select><br>
            Diploma<br>
            <input type="file" placeholder="DIPLOMA" name="diploma" id="diploma" accept=".jpg,.jpeg,.png" required><br>
            Peso <input type="number" name="peso" min="10" step="0.05" required><br>
            <input type="hidden" name="tipo" value="A">
            <input type="submit" value="Cadastrar"><br>
        </form>
        <a class="link" href="index.php">voltar</a>
    </div>
    <?php
    include "menu/footer.php";
    ob_end_flush();
    ?>
</body>

</html>