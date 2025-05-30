<?php

include_once "../classes/atletaService.php";

//session_start();
//checar se é administrador

require "../func/is_adm.php";
is_adm();
try {
    $con = new Conexao();
    $at = new Atleta();
    $attServ = new atletaService($con, $at);
        
    $lista = $attServ->listAll();
    
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
    <title>Atletas</title>
</head>
<body>
<?php include "../menu/add_menu.php"; ?>

<div class="principal">
        <div>
            <table>
            <tr>
                <th>Nome</th>
                <th>Faixa</th>
                <th>Academia</th>
                <th>Validado</th>
                <th>Pagina Pessoal</th>
            </tr>
            <?php 
                foreach( $lista as $key => $value )://inicio do foreach
                    //listar todo mund?>
                    <tr>
                        <td><?php echo htmlspecialchars($value->nome); ?></td>
                        <td><?php echo htmlspecialchars($value->faixa); ?></td>
                        <td><?php echo htmlspecialchars($value->academia); ?></td>
                        <td><?php echo $value->validado == 1 ? 'sim' : 'não'; ?></td>
                        <td><a href="controle.php?user=<?php echo htmlspecialchars($value->id, ENT_QUOTES, 'UTF-8'); ?>">Ver Mais</a></td>
                        </tr>
                <?php endforeach; //fim do foreach?>
        </table>
        </div>
        </div>
    </div>
<?php
include "../menu/footer.php";
?>
</body>
</html>