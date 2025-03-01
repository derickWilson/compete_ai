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
    $senha = password_hash(cleanWords($_POST["senha"]), PASSWORD_BCRYPT);
    $atleta->__set("email", cleanWords($_POST["usuario"]));
    $atleta->__set("senha", $senha);
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
