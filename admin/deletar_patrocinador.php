<?php
// admin/deletar_patrocinador.php
session_start();
require "../func/is_adm.php";
is_adm();
require_once "../classes/patrocinadorClass.php";

// Verificando se existe o ID passado pela URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $idPatrocinador = $_GET['id'];

    try {
        // Conexão com o banco e instância da classe PatrocinadorService
        $con = new Conexao();
        $patrocinador = new Patrocinador();
        $patrocinadorServ = new PatrocinadorService($con, $patrocinador);

        // Chamando o método de exclusão
        if ($patrocinadorServ->deletePatrocinador($idPatrocinador)) {
            header("Location: patrocinadores.php?message=Patrocinador excluído com sucesso&message_type=success");
        } else {
            header("Location: patrocinadores.php?message=Erro ao excluir patrocinador&message_type=error");
        }

    } catch (Exception $e) {
        echo "Erro: " . $e->getMessage();
    }
} else {
    header("Location: patrocinadores.php?message=ID do patrocinador não informado&message_type=error");
}
?>