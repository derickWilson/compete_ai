<?php
if(!isset($_SESSION["admin"]) || !$_SESSION["admin"]){
    header("Location: ../index.php");
}else{
    header("Location: painel_administrativo.php");
}
?>