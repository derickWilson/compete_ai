<?php
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";

    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";
// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once "classes/atletaService.php";
    include "func/clearWord.php";
    echo "to aqui.";
    // Criação do objeto de conexão e atleta
    $con = new Conexao();
    $at = new Atleta();
    $attServ = new atletaService($con, $at);

    $atleta = $attServ->getById($_POST["id"]);

    $diploma_antigo = $atleta->diploma;
    $foto_antiga = $atleta->foto;
    // Verifica se o arquivo foi enviado
    if (isset($_FILES['diploma_novo']) && $_FILES['diploma_novo']['error'] === UPLOAD_ERR_OK) {
        $diploma = $_FILES['diploma_novo'];
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
                echo "troquei a diploma";
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
    //tratar foto nova
    if (isset($_FILES['foto_nova']) && $_FILES['foto_nova']['error'] === UPLOAD_ERR_OK) {
        $foto = $_FILES['foto_nova'];
        $extensaoFoto = pathinfo($foto['name'], PATHINFO_EXTENSION);
        $novoNomeFoto = 'foto_' . time() . '.' . $extensaoFoto;
        $caminhoParaSalvarFoto = 'fotos/' . $novoNomeFoto;
        //excluir foto antiga   
        if(!empty("/fotos/".$diploma_foto_antigaantigo) && file_exists("/fotos/".$foto_antiga)){
            unlink('/fotos/'.$foto_antiga);
        }
        if ($foto['size'] > 0) {
            if (move_uploaded_file($foto['tmp_name'], $caminhoParaSalvarFoto)) {
                $fotoNova = $novoNomeFoto;
                echo "troquei a foto";
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
    $at->__set("email", cleanWords($_POST["email"]));
    $at->__set("fone", cleanWords($_POST["fone"]));
    $at->__set("foto", $fotoNova);
    $at->__set("faixa", cleanWords($_POST["faixa"]));
    $at->__set("peso", cleanWords($_POST["peso"]));
    $at->__set("diploma", $diplomaNovo);

    echo "<pre>";
    print_r($at);
    echo "</pre>";
    
    try {
        // Atualiza o atleta
    $attServ->updateAtleta($idAtleta);
    } catch (Exception $e) {
        echo "Erro ao atualizar os dados: " . $e->getMessage();
    }
}
?>