<?php
require "../func/is_adm.php";
is_adm();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once "../classes/galeriaClass.php";
    include "../func/clearWord.php";

    $legenda = cleanWords($_POST['legenda']);
    $imagemPath = null;

    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $imagem = $_FILES['imagem'];
        $ext = pathinfo($imagem['name'], PATHINFO_EXTENSION);
        $nomeImagem = 'galeria_' . time() . '.' . $ext;
        $imagemPath = "../galeria/" . $nomeImagem;

        // Apenas imagens permitidas
        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        if (!in_array(mime_content_type($imagem['tmp_name']), $tiposPermitidos)) {
            echo "Arquivo inválido. Apenas imagens JPG, PNG ou WEBP são permitidas.";
            exit();
        }

        if ($imagem['size'] > 0) {
            if (!move_uploaded_file($imagem['tmp_name'], $imagemPath)) {
                echo "Erro ao mover a imagem.";
                exit();
            }
        }
    } else {
        echo "Erro: Nenhuma imagem foi enviada.";
        exit();
    }

    // Criar e salvar a imagem no banco
    $galeria = new Galeria();
    $galeria->__set('img', $nomeImagem);
    $galeria->__set('legenda', $legenda);

    $conn = new Conexao();
    $servico = new GaleriaService($conn, $galeria);

    $servico->addGaleria();

    header("Location: galeria_listar.php?message=1");
    exit();
}
?>