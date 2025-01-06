<?php

require_once "classes/atletaService.php";
require_once "classes/atletaClass.php";

include "func/clearWord.php";

if ($_SERVER["REQUEST_METHOD"] == "POST"){    
    session_start();    
    $atleta = new Atleta();
    $conn = new Conexao();
    $atleta->__set("email", cleanWords($_POST["usuario"]));
    $atleta->__set("senha", cleanWords($_POST["senha"]));

    $attServ = new atletaService($conn, $atleta);
    $attServ->logar();
}else{
    echo "deu bosta";
}

?>