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
$att = new Atleta();
$attServ = new atletaService($con, $att);

// Obtém os dados do atleta logado
$atleta = $attServ->getById($_SESSION["id"]);
$faixa_atual = $atleta->faixa;
$diploma_antigo = $atleta->diploma;
$diplomaNovo = "";

//processas a edição
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //editar a faixa

    // Verifica se o arquivo foi enviado
    // caso não exista um diplmoma retorna erro
    if (isset($_FILES['diploma_novo']) && $_FILES['diploma_novo']['error'] === UPLOAD_ERR_OK) {
        $diploma = $_FILES['diploma_novo'];
        $extensao = pathinfo($diploma['name'], PATHINFO_EXTENSION);
        $novoNome = 'diploma_' . time() . '.' . $extensao;
        $caminhoParaSalvar = 'diplomas/' . $novoNome;
        //excluir antigo diploma
        if (!empty($diploma_antigo) && file_exists("diplomas/" . $diploma_antigo)) {
            unlink("diplomas/" . $diploma_antigo);
        }
        if ($diploma["size"] > 0) {
            if (move_uploaded_file($diploma["tmp_name"], $caminhoParaSalvar)) {
                $diplomaNovo = $novoNome;
            } else {
                echo "Erro ao mover arquivo. Verifique as permissões do diretório.";
                header("Location: edit.php?erro=1");
                exit();
            }
        } else {
            echo "Arquivo vazio ou erro no upload";
        }
    } else {// caso não exista um diplmoma retorna erro 1 "diploma obrigatório"
        header("Location: /editarFaixa?erro=1");
        exit();
    }

    //outros processamentos
    $faixa = cleanWords($_POST["faixa"]);

    if ($faixa == $faixa_atual) {
        header("Location: /editarFaixa?erro=2");
        exit();
    }

    $att->__set("id", $_SESSION["id"]);
    $att->__set("faixa", $faixa);
    $att->__set("diploma", $diplomaNovo);


    if ($attServ->updateFaixa()) {
        // Redireciona para uma página de logout ou aviso
        header("Location: /logout.php?msg=faixa_updated");
        exit();
    } else {
        header("Location: /editarFaixa?erro=1");
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar faixa</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <span class="aviso">Ao Solicitar uma Troca de Faixa<br>
        Sua Conta ficaráinválda<br>
        até o Administrador Averiguar o diploma</span><br>
    <form action="editarFaixa.php" method="POST">
        <label for="faixa">Faixa Atual</label>
        <?php
        if (isset($erro) && $erro == 1) {
            echo "<span class='aviso'>A troca de faixa é obrigatória</span>";
        } ?>
        <select name="faixa" id="faixa">
            <option value="">Selecione a faixa</option>
            <option value="Branca" <?php echo ($faixa_atual == 'Branca') ? 'selected' : ''; ?>>Branca</option>
            <option value="Cinza" <?php echo ($faixa_atual == 'Cinza') ? 'selected' : ''; ?>>Cinza</option>
            <option value="Amarela" <?php echo ($faixa_atual == 'Amarela') ? 'selected' : ''; ?>>Amarela</option>
            <option value="Laranja" <?php echo ($faixa_atual == 'Laranja') ? 'selected' : ''; ?>>Laranja</option>
            <option value="Verde" <?php echo ($faixa_atual == 'Verde') ? 'selected' : ''; ?>>Verde</option>
            <option value="Azul" <?php echo ($faixa_atual == 'Azul') ? 'selected' : ''; ?>>Azul</option>
            <option value="Roxa" <?php echo ($faixa_atual == 'Roxa') ? 'selected' : ''; ?>>Roxa</option>
            <option value="Marrom" <?php echo ($faixa_atual == 'Marrom') ? 'selected' : ''; ?>>Marrom</option>
            <option value="Preta" <?php echo ($faixa_atual == 'Preta') ? 'selected' : ''; ?>>Preta</option>
        </select>
        <label for="diploma_novo">Diploma</label><br>
        <img src="/diplomas/<?php echo $diploma_antigo; ?>" name="diploma_novo" width="100px" height="100px">
        <?php
        if (isset($erro) && $erro == 1) {// erro caso não tenha diploma
            echo "<span class='aviso'>O Diploma é Obrigatório</span>";
        }
        ?>
        <input type="file" name="diploma_novo" id="diploma_novo" accept=".jpg,.jpeg,.png"><br>
        <input type="submit" value="Pedir mudança de faixa">
    </form>
</body>

</html>