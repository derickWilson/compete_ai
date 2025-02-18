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
        $attServ->logar(); // this is where you call the logar method
        if(!$_SESSION["validado"]){
            header('Location: login.php?erro=1');
        }else{
            header("Location: pagina_pessoal.php");   
        }
    } catch (Exception $e) {
        echo "Erro ao tentar logar: " . $e->getMessage();
    }

} else {
    header("Location: index.php");
}
?>
