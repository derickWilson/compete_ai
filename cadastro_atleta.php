<?php
if (isset($_SESSION["logado"]) || $_SESSION["logado"] = 1){
    header("Location: index.php");
    exit();
}else{
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
    Foto 3x4<br>
    <input type="file" placeholder="FOTO" name="foto" id="foto" accept=".jpg,.jpeg,.png"><br>

    Nome<input name="nome" type="text" placeholder="nome completo" required><br>
        <?php
            if(isset($erro) && $erro == 1){
                echo '<span class = "erro">usuario ja possui conta </span>';
                echo 'email <input name="email" type="email" placeholder="exemplo@email.com" required><br>';
            }else{
                echo 'email <input name="email" type="email" placeholder="exemplo@email.com" required><br>';
            }
        ?>
        senha  <input type="password" name="senha" id="senha" required><br>
        Data de Nascimento  <input type="date" name="data_nascimento" id="data_nasc" required><br>
        Fone  <input maxlength="12" type="tel" name="fone" id="telefone" placeholder="0000000000" required><br>
        Academia/Equipe  <select name="academia" id="academia">
            <option value="">--selecione sua academia--</option>
            <?php
                foreach($academias as $academia){
                    echo "<option value=" .$academia->id . ">".$academia->nome."</option>";
                }
            ?>
        </select><br>
        Faixa 
        <select id="faixas" name="faixa" required>
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
            <option value="Vermelha e Branca">Vermelha e Branca</option>
            <option value="Vermelha">Vermelha</option>
        </select><br>
        Diploma<br>
        <input type="file" placeholder="DIPLOMA" name="diploma" id="diploma" accept=".jpg,.jpeg,.png"><br>
        Peso <input type="number" name="peso" min="10" step="0.05" required><br>
        <input type="submit" value="Cadastrar"><br>
        <input type="hidden" name="tipo" value="AT">
    </form> 
    <a class="link" href="index.php">voltar</a>

    </div>
    <footer>todos os direitos reservados</footer>
</body>
</html>
