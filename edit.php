<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION["logado"])) {
    header("Location: login.php");
    exit();
}

require_once "classes/atletaService.php";
include "func/clearWord.php";

// Criação do objeto de conexão e atleta
$con = new Conexao();
$atletas = new Atleta();
$attServ = new atletaService($con, $atletas);

// Obtém os dados do atleta logado
$atleta = $attServ->getById($_SESSION["id"]);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Dados do Atleta</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include "menu/add_menu.php"; ?>
    <div>
    <form method="POST" action="editar.php" enctype="multipart/form-data">
        <label for="foto_nova">Foto</label><br>
        <img src="/fotos/<?php echo $_SESSION["foto"]; ?>" name="foto_nova" width="100px" height="100px">
        <input type="file" name="foto_nova" id="foto_nova" accept=".jpg,.jpeg,.png" ><br>
        
        Email: <input name="email" type="email" placeholder="exemplo@email.com" value="<?php echo htmlspecialchars($atleta->email); ?>"><br>
        Telefone: <input maxlength="12" type="tel" name="fone" id="fone" value="<?php echo htmlspecialchars($atleta->fone); ?>" placeholder="0000000000"><br>
        <label for="faixa">Faixa:</label>
        <select id="faixa" name="faixa">
            <option value="">Graduação</option>
            <option value="Branca" <?php if ($atleta->faixa == "Branca") echo "selected"; ?>>Branca</option>
            <option value="Cinza" <?php if ($atleta->faixa == "Cinza") echo "selected"; ?>>cinza</option>
            <option value="Amarela" <?php if ($atleta->faixa == "Amarela") echo "selected"; ?>>Amarela</option>
            <option value="Laranja" <?php if ($atleta->faixa == "Laranja") echo "selected"; ?>>Laranja</option>
            <option value="Verde" <?php if ($atleta->faixa == "Verde") echo "selected"; ?>>Verde</option>
            <option value="Azul" <?php if ($atleta->faixa == "Azul") echo "selected"; ?>>Azul</option>
            <option value="Roxa" <?php if ($atleta->faixa == "Roxa") echo "selected"; ?>>Roxa</option>
            <option value="Marrom" <?php if ($atleta->faixa == "Marrom") echo "selected"; ?>>Marrom</option>
            <option value="Preta" <?php if ($atleta->faixa == "Preta") echo "selected"; ?>>Preta</option>
        </select>
        <br>
        <label for="diploma_novo">Diploma</label><br>
        <img src="/diplomas/<?php echo $_SESSION["diploma"]; ?>" name="diploma_novo" width="100px" height="100px">
        <input type="file" name="diploma_novo" id="diploma_novo" accept=".jpg,.jpeg,.png" ><br>
        
        Peso: <input type="number" name="peso" min="10" step="0.05" value="<?php echo htmlspecialchars($atleta->peso); ?>" required><br>

        <input type="submit" value="Atualizar Dados"><br>
    </form> 
    <a href="pagina_pessoal.php">Voltar</a>
    </div>
    <?php
        include "menu/footer.php";
    ?>
</body>
</html>