<?php
function envia_notificacao_para($nome_ev, $id_atleta, $tipo, $dias)
{
    //incluir dependencias
    require_once __DIR__ . "/../classes/atletaService.php";
    $con = new Conexao();
    $atleta = new Atleta();
    $atr = new atletaService($con, $atleta);
    $at = $atr->getById($id_atleta);
    //para cada tipo
    switch ($tipo) {
        case "camp":
            $msg = '
            <!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FPJJI - Federação Paulista de Jiu-Jitsu Internacional</title>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Federação Paulista de Jiu-Jitsu Internacional</h1>
        </div>
        
        <div class="content">
            <h2>Memorando de Campeonato</h2>
            
            <p>Olá <strong>' . $at->nome . '</strong>,</p>
            
            <div class="info-box">
                <p>Este é um lembrete importante sobre o evento' . $nome_ev . '<br>
                que ocorrerá ' . $dias == 1? "<strong>Amanhã</strong><br>
                
            <div>
                <h3>📋 Checklist de Preparação:</h3>
                <ul>
                    <li>✅ Confirme sua categoria de peso e faixa</li>
                    <li>✅ Verifique o local e horário do evento</li>
                    <li>✅ Separe seu kimono e equipamentos necessários</li>
                    <li>✅ Leve documento de identificação com foto</li>
                    <li>✅ Chegue com pelo menos 1 hora de antecedência</li>
                    <li>✅ Hidrate-se adequadamente antes da competição</li>
                </ul>
            </div>
                ":'em <strong>'.$dias.'</strong> dias.</p>
            </div>
            
            <p>Estamos ansiosos para tê-lo conosco neste grande evento de Jiu-Jitsu. Para garantir que tudo corra bem, pedimos que verifique as informações importantes abaixo:</p>
            
            <p>Para mais informações sobre regulamento, tabela de pesagem ou qualquer dúvida, acesse nosso sistema ou entre em contato conosco.</p>
            
            <p>Desejamos a você uma excelente competição!</p>
            
            <p>Atenciosamente,<br>
            <strong>Equipe FPJJI - Federação Paulista de Jiu-Jitsu Internacional</strong></p>
        </div>
        
        <div class="footer">
            <p><em>Esta é uma mensagem automática, por favor não responda este e-mail.</em></p>            
            <p>© ' . date('Y') . ' FPJJI - Federação Paulista de Jiu-Jitsu Internacional. Todos os direitos reservados.</p>
            <p>Caso não queira receber mais estas comunicações, <a href="#">clique aqui</a>.</p>
        </div>
    </div>
</body>
</html>';
            break;
    }
}
?>