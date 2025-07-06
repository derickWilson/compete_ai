<?php
session_start();
if (!isset($_SESSION["logado"]) || !$_SESSION["logado"]) {
    header("Location: index.php");
    exit();
}

try {
    require_once "classes/atletaService.php";
    include "func/clearWord.php";
    require_once __DIR__ . "/config_taxa.php";
} catch (\Throwable $th) {
    print ('[' . $th->getMessage() . ']');
}

$conn = new Conexao();
$at = new Atleta();
$atserv = new atletaService($conn, $at);

if (isset($_GET["inscricao"])) {
    $eventoId = (int) cleanWords($_GET["inscricao"]);
    $inscricao = $atserv->getInscricao($eventoId, $_SESSION["id"]);

    if (!$inscricao) {
        $_SESSION['erro'] = "Inscrição não encontrada";
        header("Location: eventos_cadastrados.php");
        exit();
    }

    $eventoGratuito = ($inscricao->preco == 0 && $inscricao->preco_menor == 0 && $inscricao->preco_abs == 0);
    $inscricao->preco = number_format($inscricao->preco * TAXA, 2, ',', '.');
    $inscricao->preco_menor = number_format($inscricao->preco_menor * TAXA, 2, ',', '.');
    $inscricao->preco_abs = number_format($inscricao->preco_abs * TAXA, 2, ',', '.');
} else {
    $_SESSION['erro'] = "Selecione um campeonato";
    header("Location: eventos_cadastrados.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Inscrição</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="/estilos/icone.jpeg">
</head>
<body>
    <?php include "menu/add_menu.php"; ?>
    <div class='principal'>
        <h3><?php echo htmlspecialchars($inscricao->nome); ?></h3>
        <p>Preço
            <?php
            if ($_SESSION["idade"] > 15) {
                echo $inscricao->preco . " R$";
                echo "<br>Preço Absoluto " . $inscricao->preco_abs . " R$";
            } else {
                echo $inscricao->preco_menor . " R$";
            }
            ?>
        </p>
        <form action="editar_inscricao.php" method="POST">
            <input type="hidden" name="evento_id" value="<?php echo htmlspecialchars($inscricao->id); ?>">
            <?php
            if ($inscricao->tipo_com == 1) {
                echo '<input type="checkbox" name="com" ' . ($inscricao->mod_com == 1 ? 'checked' : '') . '> Categoria ';

                if ($_SESSION["idade"] > 15) {
                    echo '<input type="checkbox" name="abs_com"' . ($inscricao->mod_ab_com == 1 ? 'checked' : '') . '> Categoria + Absoluto ';
                }
            }
            if ($inscricao->tipo_sem == 1) {
                echo '<input type="checkbox" name="sem"' . ($inscricao->mod_sem == 1 ? 'checked' : '') . '> Categoria sem Quimono ';

                if ($_SESSION["idade"] > 15) {
                    echo '<input type="checkbox" name="abs_sem"' . ($inscricao->mod_ab_sem == 1 ? 'checked' : '') . '> Categoria sem Quimono + Absoluto sem Quimono ';
                }
            }
            ?>
            <br>modalidade
            <select name="modalidade">
                <option value="galo" <?php echo $inscricao->modalidade == "galo" ? "selected" : ""; ?>>Galo</option>
                <option value="pluma" <?php echo $inscricao->modalidade == "pluma" ? "selected" : ""; ?>>Pluma</option>
                <option value="pena" <?php echo $inscricao->modalidade == "pena" ? "selected" : ""; ?>>Pena</option>
                <option value="leve" <?php echo $inscricao->modalidade == "leve" ? "selected" : ""; ?>>Leve</option>
                <option value="medio" <?php echo $inscricao->modalidade == "medio" ? "selected" : ""; ?>>Médio</option>
                <option value="meio-pesado" <?php echo $inscricao->modalidade == "meio-pesado" ? "selected" : ""; ?>>Meio-Pesado</option>
                <option value="pesado" <?php echo $inscricao->modalidade == "pesado" ? "selected" : ""; ?>>Pesado</option>
                <option value="super-pesado" <?php echo $inscricao->modalidade == "super-pesado" ? "selected" : ""; ?>>Super-Pesado</option>
                <option value="pesadissimo" <?php echo $inscricao->modalidade == "pesadissimo" ? "selected" : ""; ?>>Pesadíssimo</option>
                <?php if ($_SESSION["idade"] > 15): ?>
                    <option value="super-pesadissimo" <?php echo ($inscricao->modalidade == "super-pesadissimo") ? "selected" : ""; ?>>Super-Pesadíssimo</option>
                <?php endif; ?>
            </select>
            <div class="form-actions">
                <input type="submit" name="action" value="Salvar Alterações" class="botao-acao">
                <input type="submit" name="action" value="Excluir Inscrição" class="danger" 
                       onclick="return confirm('Tem certeza que deseja excluir esta inscrição?')">
            </div>
        </form>
        <br>
        <center>Tabela de Pesos</center>
        <center>
            <object data="tabela_de_pesos.pdf" type="application/pdf" width="50%"></object>
        </center>
        <br><a class="link" href="eventos_cadastrados.php">voltar</a>
    </div>
    <?php include "menu/footer.php"; ?>
</body>
</html>