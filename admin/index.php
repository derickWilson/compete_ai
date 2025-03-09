<?php
if(!isset($_SESSION["admin"]) || !$_SESSION["admin"]){
    header("Location: /index.php");
}else{
    header("Location: /admin/painel_administrativo.php");
}
?>