<?php
// admin/patrocinadores.php
session_start();
require "../func/is_adm.php";
is_adm();
require_once "../classes/patrocinadorClass.php";

try {
    $con = new Conexao();
    $patrocinador = new Patrocinador();
    $patrocinadorServ = new PatrocinadorService($con, $patrocinador);
    $lista = $patrocinadorServ->listPatrocinadores();
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
    <title>Gerenciar Patrocinadores</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/style.css">
    <link rel="icon" href="/estilos/icone.jpeg">
</head>
<body>
    <?php include "../menu/add_menu.php"; ?>

    <div class="container">
        <?php
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

        <h2 class="section-title" style="color: var(--primary-dark); text-shadow: none;">Gerenciar Patrocinadores</h2>

        <div class="patrocinadores-admin">
            <?php if (!empty($lista)) { ?>
                <div class="patrocinadores-grid">
                    <?php foreach ($lista as $patrocinador) { ?>
                        <div class="patrocinador-item">
                            <div class="patrocinador-imagem">
                                <img class="patrocinador" width="340" height="140" src="../patrocinio/<?php echo htmlspecialchars($patrocinador->imagem); ?>"
                                    alt="<?php echo htmlspecialchars($patrocinador->nome); ?>">
                            </div>
                            <div class="patrocinador-info">
                                <h4 class="blue" ><?php echo htmlspecialchars($patrocinador->nome); ?></h4>
                                <p class="blue"><strong>Link:</strong> <?php echo htmlspecialchars($patrocinador->link); ?></p>
                                <div class="patrocinador-acoes">
                                    <a href="editar_patrocinador.php?id=<?php echo $patrocinador->id; ?>" class="botao-editar">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <a href="deletar_patrocinador.php?id=<?php echo $patrocinador->id; ?>" class="danger"
                                        onclick="return confirm('Deseja realmente excluir este patrocinador?')">
                                        <i class="fas fa-trash"></i> Excluir
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <div class="nenhum-item">
                    <i class="fas fa-handshake" style="font-size: 48px; color: var(--gray); margin-bottom: 15px;"></i>
                    <p>Nenhum patrocinador cadastrado.</p>
                </div>
            <?php } ?>
        </div>

        <div class="acao-centralizada">
            <a href="novo_patrocinador.php" class="botao-acao">
                <i class="fas fa-plus"></i> Adicionar Novo Patrocinador
            </a>
        </div>
    </div>

    <?php include "../menu/footer.php"; ?>
</body>
</html>