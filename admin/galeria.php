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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/style.css">
    <link rel="icon" href="/estilos/icone.jpeg">
</head>

<body>
    <?php include "../menu/add_menu.php"; ?>

    <div class="container">
        <?php
        // Mensagem de erro ou sucesso
        if (isset($_GET['message'])) {
            $messageType = isset($_GET['message_type']) ? htmlspecialchars($_GET['message_type']) : 'info';
            ?>
            <div class="alert-message <?php echo $messageType; ?>">
                <i class="fas fa-<?php
                switch ($messageType) {
                    case 'success':
                        echo 'check-circle';
                        break;
                    case 'error':
                        echo 'exclamation-circle';
                        break;
                    default:
                        echo 'info-circle';
                }
                ?>"></i>
                <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
        <?php } ?>

        <h2 class="section-title" style="color: var(--primary-dark); text-shadow: none;">Galeria de Fotos</h2>

        <div class="galeria-admin">
            <?php if (!empty($lista)) { ?>
                <div class="galeria-grid">
                    <?php foreach ($lista as $foto) { ?>
                        <div class="galeria-item">
                            <div class="galeria-imagem">
                                <img src="../galeria/<?php echo htmlspecialchars($foto->imagem); ?>"
                                    alt="<?php echo htmlspecialchars($foto->legenda); ?>">
                            </div>
                            <div class="galeria-info">
                                <h4><?php echo htmlspecialchars($foto->legenda); ?></h4>
                                <div class="galeria-acoes">
                                    <a href="editar_galeria.php?id=<?php echo $foto->id; ?>" class="botao-editar">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <a href="deletar_galeria.php?id=<?php echo $foto->id; ?>" class="danger"
                                        onclick="return confirm('Deseja realmente excluir esta imagem?')">
                                        <i class="fas fa-trash"></i> Excluir
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <div class="nenhum-item">
                    <i class="fas fa-image" style="font-size: 48px; color: var(--gray); margin-bottom: 15px;"></i>
                    <p>Nenhuma imagem cadastrada na galeria.</p>
                </div>
            <?php } ?>
        </div>

        <div class="acao-centralizada">
            <a href="nova_galeria.php" class="botao-acao">
                <i class="fas fa-plus"></i> Adicionar Nova Imagem
            </a>
        </div>
    </div>

    <?php include "../menu/footer.php"; ?>
</body>