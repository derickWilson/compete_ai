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
    $at = new Atleta();
    $attServ = new atletaService($con, $at);

    $atleta = $attServ->getById($_POST["id"]);
    $diploma_antigo = $atleta->diploma;
    $foto_antiga = $atleta->foto;
    // Verifica se o arquivo foi enviado
    if (isset($_FILES['diploma']) && $_FILES['diploma']['error'] === UPLOAD_ERR_OK) {
        $diploma = $_FILES['diploma'];
        $extensao = pathinfo($diploma['name'], PATHINFO_EXTENSION);
        $novoNome = 'diploma_' . time() . '.' . $extensao;
        $caminhoParaSalvar = '/diplomas/' . $novoNome;
        //excluir antigo diploma
        if(!empty("/diplomas/".$diploma_antigo) && file_exists("/diplomas/".$diploma_antigo)){
            unlink('diplomas/'.$diploma_antigo);
        }
        if ($diploma['size'] > 0) {
            if (move_uploaded_file($diploma['tmp_name'], $caminhoParaSalvar)) {
                $diplomaNovo = $novoNome;
            } else {
                echo 'Erro ao mover arquivo. Verifique as permissões do diretório.';
                header("Location: editar_atleta.php");
                exit();
            }
        } else {
            echo 'Arquivo vazio ou erro no upload';
        }
    }else{
        $diplomaNovo = $diploma_antigo;
    }
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $foto = $_FILES['foto'];
        $extensaoFoto = pathinfo($foto['name'], PATHINFO_EXTENSION);
        $novoNomeFoto = 'foto_' . time() . '.' . $extensaoFoto;
        $caminhoParaSalvarFoto = 'fotos/' . $novoNomeFoto;
        //excluir foto antiga
        if(!empty("/fotos/".$diploma_foto_antigaantigo) && file_exists("/diplomas/".$foto_antiga)){
            unlink('/fotos/'.$foto_antiga);
        }
        if ($foto['size'] > 0) {
            if (move_uploaded_file($foto['tmp_name'], $caminhoParaSalvarFoto)) {
                $fotoNova = $novoNomeFoto;
            } else {
                echo ' Erro ao mover arquivo. Verifique as permiss玫es do diret贸rio.';
                header("Location: cadastro_academia.php");
                exit();
            }
        } else {
            echo 'Arquivo vazio ou erro no upload';
            header("Location: cadastro_academia.php");
            exit();
        }
    }else{
        $fotoNova = $foto_antiga;
    }
    // Sanitiza e define os valores
    $atletas->__set("email", cleanWords($_POST["email"]));
    $atletas->__set("fone", cleanWords($_POST["fone"]));
    $atletas->__set("foto", $foto);
    $atletas->__set("faixa", cleanWords($_POST["faixa"]));
    $atletas->__set("peso", cleanWords($_POST["peso"]));
    $atletas->__set("diploma", $diplomaNovo);

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