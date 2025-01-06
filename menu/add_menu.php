<?php
if (isset($_SESSION["logado"]) && $_SESSION["logado"]){
    if (isset($_SESSION["admin"]) && $_SESSION["admin"]){
                include "menu_admin.php";
    }else{
        include "menu_pessoal.php";
    }
}else{
    include "menu.php";
}
?>