<?php
if ($_SERVER["REQUEST_METHOD"] == "POST"){
    try{
        require_once "classes/atletaService.php";
        include "func/clearWord.php";
    }catch (Exception $e){
        echo $e->getMessage();
    }
    $atleta = new Atleta();
    $conn = new Conexao();
    $atleta->__set("email", cleanWords($_POST["usuario"]));
    $atleta->__set("senha", cleanWords($_POST["senha"]));
    try {
        $attServ = new atletaService($conn, $atleta);
        $attServ->logar(); 
    } catch (Exception $e) {
        echo "Erro ao tentar logar: " . $e->getMessage();
    }
} else {
    header("Location: index.php");
}
?>