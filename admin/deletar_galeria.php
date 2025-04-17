<?php
// deletar_galeria.php
session_start();
require "../func/is_adm.php";
is_adm();
require_once "../classes/galeriaClass.php";

// Verificando se existe o ID passado pela URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $idGaleria = $_GET['id'];

    try {
        // Conexão com o banco e instância da classe GaleriaService
        $con = new Conexao();
        $galeria = new Galeria();
        $galeriaServ = new GaleriaService($con, $galeria);

        // Chamando o método de exclusão
        if ($galeriaServ->deleteGaleria($idGaleria)) {
            header("Location: galeria.php?message=Imagem excluída com sucesso&message_type=success");
        } else {
            header("Location: galeria.php?message=Erro ao excluir imagem&message_type=error");
        }

    } catch (Exception $e) {
        echo "Erro: " . $e->getMessage();
    }
} else {
    header("Location: galeria.php?message=Novas imagens foram adicionadas&message_type=info");}
?>