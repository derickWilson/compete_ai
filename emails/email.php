<?php
function envia_notificacao_para($nome_ev, $id_atleta, $tipo, $dias)
{
    //incluir dependencias
    require_once __DIR__ . "/../classes/atletaService.php";
    $con = new Conexao();
    $atleta = new Atleta();
    $atr = new atletaService($con, $atleta);
    $at = $atr->getById($id_atleta);
    switch ($tipo) {
        case "camp":
            $msg = '
            <html lang="pt">
            <head>
            <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FPJJI - Federação Paulista de Jiu-Jitsu Intenacionaç</title>
    <link rel="stylesheet" href="/style.css">
    <!-- Adicionando ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
                <div class="center-content">
        <div class="logos-mini-container">
            <img src="/estilos/banner1.png" class="logo-mini">
            <img src="/estilos/banner11.png" class="logo-lateral">
        </div>
        <h2 class="blue">Federação Paulista Jiu-Jitsu Internacional</h2>
        <img src="/estilos/banner11.png" class="logo">
        <h2 class="blue">Jiu-Jitsu Internacional</h2>
    </div>

    <h2>Memorando de campeonato<h2/>
    Olá <strong>' . $at->nome . '</strong>
    
    <p><em>Esta é uma mensagem automática, por favor não responda este e-mail.</em></p>
</body>    
            ';
            break;
    }
}
?>