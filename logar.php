<?php
try{
require_once "classes/atletaService.php";
require_once "classes/atletaClass.php";
include "func/clearWord.php";

} catch (Exception $e){
    echo $e->getMessage();
}

if ($_SERVER["REQUEST_METHOD"] == "POST"){    
    $atleta = new Atleta();
    $conn = new Conexao();
    echo " conexao feita ";

    $atleta->__set("email", cleanWords($_POST["usuario"]));
    $atleta->__set("senha", cleanWords($_POST["senha"]));
    echo " <br> objetos criados";

    try {
        $attServ = new atletaService($conn, $atleta);
        echo " <br> connex estab";
        $attServ->logar(); // this is where you call the logar method
        header("Location: pagina_pessoal.php");
    } catch (Exception $e) {
        echo "Erro ao tentar logar: " . $e->getMessage();
    }

} else {
    header("Location: index.php");
}
?>
