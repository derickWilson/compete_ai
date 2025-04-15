<?php
session_start();
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
require "../func/is_adm.php";
is_adm();
include_once "../classes/atletaClass.php";
include_once "../classes/atletaService.php";
try {
    $con = new Conexao();
    $at = new Atleta();
    $attServ = new atletaService($con, $at);
    $lista = $attServ->listInvalido();
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/style.css">
    <title>Galeria</title>
</head>
<body>
    <?php include "../menu/add_menu.php"; ?>
    <div class="principal">
    </div>
<?php
include "menu/footer.php";
?>
</body>
</html>