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
    $eventoDetails = $atserv->getInscricao($eventoId,$_SESSION["id"]);
} else {
    echo "selecione um campeonato";
    header("Location: eventos_cadastrados");
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Inscrição</title>
</head>
<body>
<div class='principal'>
<h1><?php echo htmlspecialchars($eventoDetails->nome); ?></h1>
    <img class='banner' src="uploads/<?php echo $eventoDetails->imagen; ?>" alt="Imagem do Evento">
    <p>Descrição: <?php echo htmlspecialchars($eventoDetails->descricao); ?></p>
    <p>Data do Campeonato: <?php echo htmlspecialchars($eventoDetails->data_evento); ?></p>
    <p>Local do Campeonato: <?php echo htmlspecialchars($eventoDetails->local_evento); ?></p>
    <p>Preço
        <?php
        if(!isset($_SESSION["idade"])){
            echo $eventoDetails->preco . "R$ para maiores de 15 anos<br>";
            echo "Preço " . $eventoDetails->preco_menor . "R$ para maiores de 15 anos";
        }else{
            if($_SESSION["idade"] > 15){
                echo $eventoDetails->preco."R$";
            }else{
                echo $eventoDetails->preco_menor."R$";
            }
        }
         ?></p>
<form action="editar_inscricao.php" method="POST">
       <input type="hidden" name="evento_id" value="<?php echo htmlspecialchars($eventoDetails->id); ?>">
       <input type="hidden" name="valor" value="<?php echo htmlspecialchars($eventoDetails->preco); ?>">
       <?php
       // Caso o tipo de campeonato seja com quimono
       if ($eventoDetails->tipo_com == 1) {
           echo '<input type="checkbox" name="com"> Com Quimono ';
           
           if($_SESSION["idade"]> 15){
               echo '<input type="checkbox" name="abs_com"> Absoluto Com Quimono ';
           }
       }
       // Caso o tipo de campeonato seja sem quimono
       if ($eventoDetails->tipo_sem == 1) {
           echo '<input type="checkbox" name="sem"> Sem Quimono ';
           if($_SESSION["idade"]> 15){
               echo '<input type="checkbox" name="abs_sem"> Absoluto Sem Quimono ';
           }
       }
       ?>
       <br>modalidade<select name="modalidade">
         <option value="galo">Galo</option>
         <option value="pluma">Pluma</option>
         <option value="pena">Pena</option>
         <option value="leve">Leve</option>
         <option value="medio">Médio</option>
         <option value="meio-pesado">Meio-Pesado</option>
         <option value="pesado">Pesado</option>
         <option value="super-pesado">Super-Pesado</option>
         <option value="pesadissimo">Pesadíssimo</option>
         <option value="super-pesadissimo">Super-Pesadíssimo</option>
       </select>
       <br><input type="submit" value="Salvar">
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