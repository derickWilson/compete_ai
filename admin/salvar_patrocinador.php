<?php
// admin/salvar_patrocinador.php
session_start();
require "../func/is_adm.php";
is_adm();
require_once "../classes/patrocinadorClass.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $con = new Conexao();
        $patrocinador = new Patrocinador();
        $patrocinadorServ = new PatrocinadorService($con, $patrocinador);

        $nome = htmlspecialchars($_POST['nome']);
        $link = htmlspecialchars($_POST['link']);
        
        // Processar upload da imagem
        if (isset($_FILES["imagem"]) && $_FILES["imagem"]["error"] === UPLOAD_ERR_OK) {
            $imagem = $_FILES["imagem"];
            $ext = pathinfo($imagem["name"], PATHINFO_EXTENSION);
            $novoNome = "patrocinador_" . time() . '.' . $ext;
            $caminhoParaSalvar = "../patrocinio/" . $novoNome;

            if (move_uploaded_file($imagem["tmp_name"], $caminhoParaSalvar)) {
                $patrocinador->__set('nome', $nome);
                $patrocinador->__set('imagem', $novoNome);
                $patrocinador->__set('link', $link);
                
                $patrocinadorServ->addPatrocinador();
            } else {
                header("Location: novo_patrocinador.php?message=Erro ao fazer upload da imagem&message_type=error");
                exit();
            }
        } else {
            header("Location: novo_patrocinador.php?message=Erro no upload da imagem&message_type=error");
            exit();
        }
    } catch (Exception $e) {
        echo "Erro: " . $e->getMessage();
    }
} else {
    header("Location: novo_patrocinador.php");
    exit();
}
?>