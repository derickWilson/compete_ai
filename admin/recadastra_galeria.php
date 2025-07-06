<?php
session_start();
require "../func/is_adm.php";
is_adm();
require_once __DIR__ . "/../classes/galeriaClass.php";
require_once __DIR__ . "/../classes/galeriaService.php";

try {
    $con = new Conexao();
    $galeria = new Galeria();
    $galeriaServ = new GaleriaService($con, $galeria);
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $id = (int)$_POST['id'];
        $legenda = htmlspecialchars($_POST['legenda']);
        
        // Recuperar a imagem atual
        $fotoDetalhes = $galeriaServ->getById($id);
        $imagemAtual = $fotoDetalhes->imagem;

        // Processar a nova imagem, caso tenha sido enviada
        $imagemFinal = $imagemAtual;
        if (isset($_FILES["imagem_nova"]) && $_FILES["imagem_nova"]["error"] === UPLOAD_ERR_OK) {
            $imagem = $_FILES["imagem_nova"];
            $ext = pathinfo($imagem["name"], PATHINFO_EXTENSION);
            $novoNome = "galeria_" . time() . '.' . $ext;
            $caminhoParaSalvar = "../galeria/" . $novoNome;

            // Remover a imagem antiga, se existir
            if (!empty($imagemAtual) && file_exists("../galeria/" . $imagemAtual)) {
                unlink("../galeria/" . $imagemAtual);
            }

            if ($imagem["size"] > 0) {
                if (move_uploaded_file($imagem["tmp_name"], $caminhoParaSalvar)) {
                    $imagemFinal = $novoNome;
                } else {
                    echo "Erro ao mover o arquivo de imagem.";
                    exit();
                }
            }
        }

        // Atualizar o banco de dados
        $galeria->__set('id', $id);
        $galeria->__set('imagem', $imagemFinal);
        $galeria->__set('legenda', $legenda);
        
        $galeriaServ->editarGaleria();
    } else {
        header("Location: galeria.php?message=Erro ao editar imagem&message_type=error");
        exit();
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
    exit();
}
