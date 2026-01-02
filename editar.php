<?php
// Verifica se o formulário foi enviado
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once "classes/atletaService.php";
    include "func/clearWord.php";
    // Criação do objeto de conexão e atleta
    $con = new Conexao();
    $at = new Atleta();
    $attServ = new atletaService($con, $at);

    $atleta = $attServ->getById($_SESSION["id"]);
    $telefone_novo = '+' . preg_replace('/[^0-9]/', '', $_POST["ddd"]) . preg_replace('/[^0-9]/', '', $_POST["fone"]);

    $foto_antiga = $atleta->foto;
    //tratar foto nova
    if (isset($_FILES['foto_nova']) && $_FILES['foto_nova']['error'] === UPLOAD_ERR_OK) {
        $foto = $_FILES['foto_nova'];
        $extensaoFoto = pathinfo($foto['name'], PATHINFO_EXTENSION);
        $novoNomeFoto = 'foto_' . time() . '.' . $extensaoFoto;
        $caminhoParaSalvarFoto = 'fotos/' . $novoNomeFoto;
        //excluir foto antiga   
        if (!empty($foto_antiga) && file_exists("fotos/" . $foto_antiga)) {
            unlink('fotos/' . $foto_antiga);
        }
        if ($foto['size'] > 0) {
            if (move_uploaded_file($foto['tmp_name'], $caminhoParaSalvarFoto)) {
                $fotoNova = $novoNomeFoto;
            } else {
                echo ' Erro ao mover arquivo. Verifique as permiss玫es do diret贸rio.';
                header("Location: cadastro_academia.php?");
                exit();
            }
        } else {
            echo 'Arquivo vazio ou erro no upload';
            header("Location: cadastro_academia.php");
            exit();
        }
    } else {
        $fotoNova = $foto_antiga;
    }
    $email_permissao = isset($_POST["permissao_email"]) ? 1 : 0;
    // Sanitiza e define os valores
    $at->__set("id", cleanWords($_SESSION["id"]));
    $at->__set("email", cleanWords($_POST["email"]));
    $at->__set("fone", $telefone_novo);
    $at->__set("endereco_completo", !empty($_POST["endereco_completo"]) ? cleanWords($_POST["endereco_completo"]) : null);
    $at->__set("permissao_email", $email_permissao);
    $at->__set("foto", $fotoNova);
    $at->__set("peso", cleanWords($_POST["peso"]));

    try {
        // Atualiza o atleta
        $attServ->updateAtleta();
    } catch (Exception $e) {
        echo "Erro ao atualizar os dados: " . $e->getMessage();
    }
}
?>