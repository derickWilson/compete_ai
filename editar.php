<?php

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
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

    $atleta = $attServ->getById($_POST["id"]);
    $diploma_antigo = $atleta->diploma;
    $foto_antiga = $atleta->foto;
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