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
$idAtleta = $_SESSION["id"];
$atleta = $attServ->getById($idAtleta);

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $antigoDiploma = $atleta->diploma; // Mantém o diploma existente, caso não altere
    
    // Verifica se o arquivo foi enviado
    if (isset($_FILES['diploma']) && $_FILES['diploma']['error'] === UPLOAD_ERR_OK) {
        $diploma = $_FILES['diploma'];
        $extensao = pathinfo($diploma['name'], PATHINFO_EXTENSION);
        $novoNome = 'diploma_' . time() . '.' . $extensao;
        $caminhoParaSalvar = 'diplomas/' . $novoNome;
        unlink('diplomas/'.$antigoDiploma);
        if ($diploma['size'] > 0) {
            if (move_uploaded_file($diploma['tmp_name'], $caminhoParaSalvar)) {
                // Sucesso no upload
            } else {
                echo 'Erro ao mover arquivo. Verifique as permissões do diretório.';
                header("Location: editar_atleta.php");
                exit();
            }
        } else {
            echo 'Arquivo vazio ou erro no upload';
        }
    }
    
    // Sanitiza e define os valores
    $atletas->__set("email", cleanWords($_POST["email"]));
    $atletas->__set("fone", cleanWords($_POST["fone"]));
    $atletas->__set("academia", cleanWords($_POST["academia"]));
    $atletas->__set("faixa", cleanWords($_POST["faixa"]));
    $atletas->__set("peso", cleanWords($_POST["peso"]));
    $atletas->__set("diploma", $caminhoParaSalvar);

    try {
        // Atualiza o atleta
    $attServ->updateAtleta($idAtleta);
        echo 'Dados atualizados com sucesso!';
        header("Location: pagina_pessoal.php"); // Redireciona após sucesso
        exit();
    } catch (Exception $e) {
        echo "Erro ao atualizar os dados: " . $e->getMessage();
    }
}
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
    <form method="post" action="edit.php" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $_SESSION["id"]; ?>">
        Email: <input name="email" type="email" placeholder="exemplo@email.com" value="<?php echo htmlspecialchars($atleta->email); ?>" required><br>
        Telefone: <input maxlength="12" type="tel" name="fone" id="telefone" value="<?php echo htmlspecialchars($atleta->fone); ?>" placeholder="0000000000" required><br>
        Academia/Equipe: <input type="text" name="academia" id="academia" value="<?php echo htmlspecialchars($atleta->academia); ?>" required><br>
        Faixa: 
        <label>Faixa:</label>
        <select id="faixas" name="faixa" required>
            <option value="">Graduação</option>
            <option value="Branca" <?php if ($atleta->faixa == "Branca") echo "selected"; ?>>Branca</option>
            <option value="Cinza" <?php if ($atleta->faixa == "Cinza") echo "selected"; ?>>Branca</option>
            <option value="Amarela" <?php if ($atleta->faixa == "Amarela") echo "selected"; ?>>Amarela</option>
            <option value="Laranja" <?php if ($atleta->faixa == "Laranja") echo "selected"; ?>>Laranja</option>
            <option value="Verde" <?php if ($atleta->faixa == "Verde") echo "selected"; ?>>Verde</option>
            <option value="Azul" <?php if ($atleta->faixa == "Azul") echo "selected"; ?>>Azul</option>
            <option value="Roxa" <?php if ($atleta->faixa == "Roxa") echo "selected"; ?>>Roxa</option>
            <option value="Marrom" <?php if ($atleta->faixa == "Marrom") echo "selected"; ?>>Marrom</option>
            <option value="Preta" <?php if ($atleta->faixa == "Preta") echo "selected"; ?>>Preta</option>
            <option value="Coral" <?php if ($atleta->faixa == "Coral") echo "selected"; ?>>Coral</option>
            <option value="Vermelha e Branca" <?php if ($atleta->faixa == "Vermelha e Branca") echo "selected"; ?>>Vermelha e Branca</option>
            <option value="Vermelha" <?php if ($atleta->faixa == "Vermelha") echo "selected"; ?>>Vermelha</option>
        </select>
<br>

        <input type="file" name="diploma" id="diploma" accept=".jpg,.jpeg,.png" ><br>
        
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