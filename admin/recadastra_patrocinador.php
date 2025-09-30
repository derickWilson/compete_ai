<?php
// admin/recadastra_patrocinador.php
session_start();
require "../func/is_adm.php";
is_adm();
require_once "../classes/patrocinadorClass.php";

try {
    $con = new Conexao();
    $patrocinador = new Patrocinador();
    $patrocinadorServ = new PatrocinadorService($con, $patrocinador);
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $id = (int)$_POST['id'];
        $nome = htmlspecialchars($_POST['nome']);
        $link = htmlspecialchars($_POST['link']);
        
        // Recuperar a imagem atual
        $patrocinadorDetalhes = $patrocinadorServ->getById($id);
        $imagemAtual = $patrocinadorDetalhes->imagem;

        // Processar a nova imagem, caso tenha sido enviada
        $imagemFinal = $imagemAtual;
        if (isset($_FILES["imagem_nova"]) && $_FILES["imagem_nova"]["error"] === UPLOAD_ERR_OK) {
            $imagem = $_FILES["imagem_nova"];
            $ext = pathinfo($imagem["name"], PATHINFO_EXTENSION);
            $novoNome = "patrocinador_" . time() . '.' . $ext;
            $caminhoParaSalvar = "../patrocinio/" . $novoNome;

            // Remover a imagem antiga, se existir
            if (!empty($imagemAtual) && file_exists("../patrocinio/" . $imagemAtual)) {
                unlink("../patrocinio/" . $imagemAtual);
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
        $patrocinador->__set('id', $id);
        $patrocinador->__set('nome', $nome);
        $patrocinador->__set('imagem', $imagemFinal);
        $patrocinador->__set('link', $link);
        
        $patrocinadorServ->editarPatrocinador();
    } else {
        header("Location: patrocinadores.php?message=Erro ao editar patrocinador&message_type=error");
        exit();
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
    exit();
}
?>