<?php
session_start();
if (!isset($_SESSION["logado"]) || !$_SESSION["logado"]) {
    header("Location: index.php");
    exit();
}

try {
    require_once "classes/atletaService.php";
    require_once "classes/eventosServices.php";
    include "func/clearWord.php";
    require_once __DIR__ . "/config_taxa.php";
} catch (\Throwable $th) {
    print ('[' . $th->getMessage() . ']');
}

$conn = new Conexao();
$at = new Atleta();
$atserv = new atletaService($conn, $at);
$eventoServ = new eventosService($conn, new Evento());

if (isset($_GET["inscricao"])) {
    $eventoId = (int) cleanWords($_GET["inscricao"]);
    $inscricao = $atserv->getInscricao($eventoId, $_SESSION["id"]);
    $dadosEvento = $eventoServ->getById($eventoId);

    if (!$inscricao || !$dadosEvento) {
        $_SESSION['erro'] = "Inscrição não encontrada";
        header("Location: eventos_cadastrados.php");
        exit();
    }

    // Verifica se é evento gratuito considerando todos os preços
    $eventoGratuito = ($dadosEvento->preco == 0 && $dadosEvento->preco_menor == 0 && 
                      $dadosEvento->preco_abs == 0 && $dadosEvento->preco_sem == 0 && 
                      $dadosEvento->preco_sem_menor == 0 && $dadosEvento->preco_sem_abs == 0);
    
    // Formata os preços para exibição
    $inscricao->preco = number_format($inscricao->preco * TAXA, 2, ',', '.');
    $inscricao->preco_menor = number_format($inscricao->preco_menor * TAXA, 2, ',', '.');
    $inscricao->preco_abs = number_format($inscricao->preco_abs * TAXA, 2, ',', '.');
    $inscricao->preco_sem = number_format($dadosEvento->preco_sem * TAXA, 2, ',', '.');
    $inscricao->preco_sem_menor = number_format($dadosEvento->preco_sem_menor * TAXA, 2, ',', '.');
    $inscricao->preco_sem_abs = number_format($dadosEvento->preco_sem_abs * TAXA, 2, ',', '.');
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
        
        <?php if (!$eventoGratuito): ?>
        <p>Preços:
            <?php
            if ($_SESSION["idade"] > 15) {
                echo "<br>Com Kimono: " . $inscricao->preco . " R$";
                echo "<br>Absoluto Com Kimono: " . $inscricao->preco_abs . " R$";
                echo "<br>Sem Kimono: " . $inscricao->preco_sem . " R$";
                echo "<br>Absoluto Sem Kimono: " . $inscricao->preco_sem_abs . " R$";
            } else {
                echo "<br>Com Kimono: " . $inscricao->preco_menor . " R$";
                echo "<br>Sem Kimono: " . $inscricao->preco_sem_menor . " R$";
            }
            ?>
            <br><small>Desconto de 40% para múltiplas modalidades</small>
        </p>
        <?php else: ?>
        <p>Evento Gratuito</p>
        <?php endif; ?>
        
        <form action="editar_inscricao.php" method="POST">
            <input type="hidden" name="evento_id" value="<?php echo htmlspecialchars($inscricao->id); ?>">
            <?php
            if ($inscricao->tipo_com == 1) {
                echo '<div class="modalidade-group">';
                echo '<input type="checkbox" name="com" id="com" ' . ($inscricao->mod_com == 1 ? 'checked' : '') . '>';
                echo '<label for="com"> Categoria Com Kimono</label>';
                
                if ($_SESSION["idade"] > 15) {
                    echo '<br><input type="checkbox" name="abs_com" id="abs_com"' . ($inscricao->mod_ab_com == 1 ? 'checked' : '') . '>';
                    echo '<label for="abs_com"> Absoluto Com Kimono</label>';
                }
                echo '</div>';
            }
            
            if ($inscricao->tipo_sem == 1) {
                echo '<div class="modalidade-group">';
                echo '<input type="checkbox" name="sem" id="sem"' . ($inscricao->mod_sem == 1 ? 'checked' : '') . '>';
                echo '<label for="sem"> Categoria Sem Kimono</label>';
                
                if ($_SESSION["idade"] > 15) {
                    echo '<br><input type="checkbox" name="abs_sem" id="abs_sem"' . ($inscricao->mod_ab_sem == 1 ? 'checked' : '') . '>';
                    echo '<label for="abs_sem"> Absoluto Sem Kimono</label>';
                }
                echo '</div>';
            }
            ?>
            
            <br>
            <label for="modalidade">Modalidade:</label>
            <select name="modalidade" id="modalidade">
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