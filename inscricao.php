<?php
// Incluindo arquivos necessários
session_start();/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/
if (!isset($_SESSION["logado"]) || !$_SESSION["logado"]){
    header("Location: index.php");
}
try {
    require_once "classes/atletaService.php";
    include "func/clearWord.php";
} catch (\Throwable $th) {
    print('['. $th->getMessage() .']');
}
    $conn = new Conexao();
    $at = new Atleta();
    $atserv = new atletaService($conn, $at);
if (isset($_GET["inscricao"])) {
    // Usado para listar os detalhes de um único evento
    $eventoId = (int) cleanWords($_GET["inscricao"]);
    $inscricao = $atserv->getInscricao($eventoId,$_SESSION["id"]);

echo "<pre>";
print_r($inscricao);
echo "</pre>";    
} else {
    echo "selecione um campeonato";
    header("Location: eventos_cadastrados.php");
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
    <?php
        include "menu/add_menu.php";
    ?>
<div class='principal'>
<h3><?php echo htmlspecialchars($inscricao->nome); ?></h3>
    <p>Preço
        <?php
        if($_SESSION["idade"] > 15){
            echo $inscricao->preco." R$";
            echo "<br>Preco Absoluto ".$inscricao->preco_abs." R$";
        }else{
            echo $inscricao->preco_menor." R$";
        }
         ?></p>
<form action="editar_inscricao.php" method="POST">
       <input type="hidden" name="evento_id" value="<?php echo htmlspecialchars($inscricao->id); ?>">
       <?php
       // Caso o tipo de campeonato seja com quimono
       if ($inscricao->tipo_com == 1) {
           echo '<input type="checkbox" name="com" '.($inscricao->mod_com == 1 ? 'checked' : '') .'> Com Quimono ';
           
           if($_SESSION["idade"]> 15){
               echo '<input type="checkbox" name="abs_com"'.($inscricao->mod_ab_com == 1 ? 'checked' : '') .'> Absoluto Com Quimono ';
           }
       }
       if ($inscricao->tipo_sem == 1) {
        echo '<input type="checkbox" name="sem"'.($inscricao->mod_semm == 1 ? 'checked' : '') .'> Com Quimono ';
        
        if($_SESSION["idade"]> 15){
            echo '<input type="checkbox" name="abs_sem"'.($inscricao->mod_ab_sem == 1 ? 'checked' : '') .'> Absoluto Com Quimono ';
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
        <option value="super-pesadissimo" <?php echo ($inscricao->modalidade == "super-pesadissimo") ? "selected" : ""; ?>>Super-Pesadíssimo</option>
    </select>
       <br><input type="submit" value="editar">|<a href="exclui_inscricao.php?id=<?php echo $eventoId;?>">Remover Inscrição</a>
</form>
    <br><center>Tabela de Pesos</center>
    <center>
    <object data="tabela_de_pesosw.pdf" type="application/pdf" width="50%"></object>
    </center>
    <br><a class="link" href="eventos_cadastrados.php">voltar</a>
</div>
<?php
include "menu/footer.php";
?>
</body>
</html>