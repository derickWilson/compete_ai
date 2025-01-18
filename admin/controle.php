<?php
session_start();
if (!isset($_SESSION["admin"]) || !$_SESSION["admin"]) {
    header("Location: ../index.php");
    exit();
}

include_once "../classes/atletaService.php";

$conn = new Conexao();
$atleta = new Atleta();
$attServ = new atletaService($conn, $atleta);

if (isset($_GET["user"])) {
    $usuario = $attServ->getById($_GET["user"]);
} else {
    echo "Selecione um usuário";
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Usuário</title>
</head>
<body>
    <header>
        <?php include "../menu/add_menu.php"; ?>
    </header>
    <div>
        <h1>Controle de Usuário</h1>

        <form action="admin_edit.php" method="POST">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($usuario->id); ?>">
            
            <div>
                <label>Nome: </label>
                <span><?php echo htmlspecialchars($usuario->nome); ?></span>
            </div>
            <div>
                <label>Faixa Atual:</label>
                <span><?php echo htmlspecialchars($usuario->faixa); ?></span>
            </div>
            
            <?php
            // Verifica se o diploma está disponível
            if (!empty($usuario->diploma)) {
                $caminho = $usuario->diploma; // O caminho completo do diploma
                
                // Gerar o HTML para o link do diploma
                echo '<div>';
                echo '<label>Diploma: <a href="../diplomas/' . $caminho. '" download>Baixe o diploma</a></label>';
                echo '</div>';
            } else {
                echo '<div>Diploma não encontrado.</div>';
            }
            ?>
            
            <div>
                <label>Validado:</label>
                <input type="checkbox" name="validado" <?php echo $usuario->validado ? 'checked' : ''; ?>>
            </div>
            <div>
                <label>Nova Faixa:</label>
                <select name="faixa">
                    <option value="Branca" <?php echo $usuario->faixa == 'Branca' ? 'selected' : ''; ?>>Branca</option>
                    <option value="Amarela" <?php echo $usuario->faixa == 'Amarela' ? 'selected' : ''; ?>>Amarela</option>
                    <option value="Laranja" <?php echo $usuario->faixa == 'Laranja' ? 'selected' : ''; ?>>Laranja</option>
                    <option value="Verde" <?php echo $usuario->faixa == 'Verde' ? 'selected' : ''; ?>>Verde</option>
                    <option value="Azul" <?php echo $usuario->faixa == 'Azul' ? 'selected' : ''; ?>>Azul</option>
                    <option value="Roxa" <?php echo $usuario->faixa == 'Roxa' ? 'selected' : ''; ?>>Roxa</option>
                    <option value="Marrom" <?php echo $usuario->faixa == 'Marrom' ? 'selected' : ''; ?>>Marrom</option>
                    <option value="Preta" <?php echo $usuario->faixa == 'Preta' ? 'selected' : ''; ?>>Preta</option>
                    <option value="Coral" <?php echo $usuario->faixa == 'Coral' ? 'selected' : ''; ?>>Coral</option>
                    <option value="Vermelha" <?php echo $usuario->faixa == 'Vermelha' ? 'selected' : ''; ?>>Vermelha</option>
                    <option value="Preta e Vermelha" <?php echo $usuario->faixa == 'Preta e Vermelha' ? 'selected' : ''; ?>>Preta e Vermelha</option>
                    <option value="Preta e Branca" <?php echo $usuario->faixa == 'Preta e Branca' ? 'selected' : ''; ?>>Preta e Branca</option>
                </select>
            </div>
            <div>
                <button type="submit">Salvar Alterações</button>
                <a href="painel_administrativo.php">Voltar</a>
            </div>
        </form>
    </div>
</body>
</html>
