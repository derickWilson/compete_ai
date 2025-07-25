<?php
session_start();
require "../func/is_adm.php";
is_adm();
require_once "../classes/galeriaClass.php";

try {
    $con = new Conexao();
    $galeria = new Galeria();
    $galeriaServ = new GaleriaService($con, $galeria);
    $lista = $galeriaServ->listGaleria();
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
    <title>Galeria</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<?php include "../menu/add_menu.php"; ?>

<?php 
// Mensagem de erro ou sucesso - CORREÇÃO AQUI
if (isset($_GET['message'])) { 
    $messageType = isset($_GET['message_type']) ? htmlspecialchars($_GET['message_type']) : 'info'; // Valor padrão 'info'
?>
    <div class="aviso <?php echo $messageType; ?>">
        <?php echo htmlspecialchars($_GET['message']); ?>
    </div>
<?php } ?>

<center><h3>Galeria de Fotos</h3></center>
<div class="principal">
    <?php if (!empty($lista)) { 
        foreach ($lista as $foto) { ?>
            <br><div class="campeonato-amostra">
                <img src="../galeria/<?php echo htmlspecialchars($foto->imagem); ?>" alt="Imagem" class="mini-banner">
                <h4><?php echo htmlspecialchars($foto->legenda); ?></h4>
                <a href="editar_galeria.php?id=<?php echo $foto->id; ?>">Editar</a> |
                <a href="deletar_galeria.php?id=<?php echo $foto->id; ?>" class="danger" onclick="return confirm('Deseja realmente excluir esta imagem?')">Excluir</a>
                <br class='clear'>
            </div>
    <?php } ?>
        <br class="clear">
    <?php } else { ?>
        <p class="aviso">Nenhuma imagem cadastrada.</p>
    <?php } ?>
</div>
<div style="clear: both; text-align: center; margin: 30px;">
    <a href="nova_galeria.php" class="botao-acao">Adicionar Nova Imagem</a>
</div>
<?php include "../menu/footer.php"; ?>
</body>
</html>