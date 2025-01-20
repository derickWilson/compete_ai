<?php
session_start();
function is_adm(){
    if(!isset($_SESSION["admin"]) || !$_SESSION["admin"]){
        header("Location: /compete_ai");
    }
}
?>