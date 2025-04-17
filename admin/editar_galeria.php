<?php
session_start();
require "../func/is_adm.php";
is_adm();
require_once "../classes/galeriaService.php";

try {
    $con = new Conexao();
    $galeria = new Galeria();
    $galeriaServ = new GaleriaService($con, $galeria);
    
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $fotoDetalhes = $galeriaServ->getById($id);
    } else {
        header("Location: galeria.php?message=Imagem não encontrada&message_type=error");
        exit();
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/style.css">
    <title>Editar Galeria</title>
</head>
<body>
<?php include "../menu/add_menu.php"; ?>
    <div class="principal">
        <h3>Editar Imagem da Galeria</h3>
        <form action="recadastra_galeria.php" method="POST" id="editar_galeria" enctype="multipart/form-data">
            <p>Imagem Atual:</p>
            <img src="../galeria/<?php echo htmlspecialchars($fotoDetalhes->imagem); ?>" alt="Imagem Atual" width="100%" height="auto">
            
            <p>Nova Imagem:</p>
            <input type="file" name="imagem_nova" id="imagem_nova"><br><br>
            
            <p>Legenda:</p>
            <input type="text" name="legenda" id="legenda" value="<?php echo htmlspecialchars($fotoDetalhes->legenda); ?>"><br><br>
            
            <input type="hidden" name="id" value="<?php echo $fotoDetalhes->id; ?>">
            
            <input type="submit" value="Salvar Alterações">
        </form>
    </div>
<?php include "../menu/footer.php"; ?>
</body>
</html>