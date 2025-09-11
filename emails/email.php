<?php
function obter_mensagem_base($nome_ev, $id_atleta, $tipo, $dias)
{
    require_once __DIR__ . "/../classes/atletaService.php";
    $con = new Conexao();
    $atleta = new Atleta();
    $atr = new atletaService($con, $atleta);
    $at = $atr->getById($id_atleta);
    
    if (!$at) {
        return ''; // Retorna vazio se atleta não for encontrado
    }

    $msg = '';
    
    switch ($tipo) {
        case "campeonato_lembrete":
            $dias_texto = $dias == 1 ? "<strong>Amanhã</strong>" : "em <strong>$dias dias</strong>";
            
            $msg = '
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FPJJI - Federação Paulista de Jiu-Jitsu Internacional</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f9f9f9;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 20px; border-radius: 5px;">
        <div style="background-color: #2520a0; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;">
            <h1 style="margin: 0; font-size: 24px;">Federação Paulista de Jiu-Jitsu Internacional</h1>
        </div>
        
        <div style="padding: 25px;">
            <h2 style="color: #2520a0; font-size: 20px; margin-bottom: 15px; border-bottom: 2px solid #e9b949; padding-bottom: 5px;">Memorando de Campeonato</h2>
            
            <p style="margin-bottom: 15px;">Olá <strong style="color: #2520a0;">' . $at->nome . '</strong>,</p>
            
            <div style="background-color: #e8eaf6; border-left: 4px solid #2520a0; padding: 15px; margin: 20px 0;">
                <p style="margin: 0; font-size: 16px;">
                    Este é um lembrete importante sobre o evento <strong style="color: #2520a0;">' . $nome_ev . '</strong> que ocorrerá ' . $dias_texto . '.
                </p>
            </div>
            
            <div style="background-color: #f8f9fa; border-radius: 8px; padding: 15px; margin: 20px 0; border: 1px solid #e0e0e0;">
                <h3 style="color: #2520a0; margin-top: 0; margin-bottom: 12px; font-size: 18px;">📋 Checklist de Preparação:</h3>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li style="margin-bottom: 8px; color: #2d3748;">✅ Confirme sua categoria de peso e faixa</li>
                    <li style="margin-bottom: 8px; color: #2d3748;">✅ Verifique o local e horário do evento</li>
                    <li style="margin-bottom: 8px; color: #2d3748;">✅ Separe seu kimono e equipamentos necessários</li>
                    <li style="margin-bottom: 8px; color: #2d3748;">✅ Leve documento de identificação com foto</li>
                    <li style="margin-bottom: 8px; color: #2d3748;">✅ Chegue com pelo menos 1 hora de antecedência</li>
                    <li style="margin-bottom: 8px; color: #2d3748;">✅ Hidrate-se adequadamente antes da competição</li>
                </ul>
            </div>
            
            <p style="margin-bottom: 15px;">Para mais informações sobre regulamento, tabela de pesagem ou qualquer dúvida, acesse nosso sistema ou entre em contato conosco.</p>
            
            <p style="margin-bottom: 15px;">Desejamos a você uma excelente competição!</p>
            
            <p style="margin-bottom: 5px;">Atenciosamente,</p>
            <p style="margin: 0; font-weight: bold; color: #2520a0;">Equipe FPJJI - Federação Paulista de Jiu-Jitsu Internacional</p>
        </div>
        
        <div style="background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 5px 5px;">
            <p style="margin: 0 0 10px 0; font-style: italic;">Esta é uma mensagem automática, por favor não responda este e-mail.</p>            
            <p style="margin: 0 0 10px 0;">Caso Não Queira receber esse tipo de notificação pode trocar em <a href="https://fpjji.com/edit.php"> editar dados</a></p>
            <p style="margin: 0 0 10px 0;">© ' . date('Y') . ' FPJJI - Federação Paulista de Jiu-Jitsu Internacional. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>';
            break;

        //corpo do lembrete de cobrança
        case "cobranca_lembrete":
            $msg='
            <!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FPJJI - Cobrança em Aberto</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f9f9f9;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 20px; border-radius: 5px;">
        <div style="background-color: #2520a0; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;">
            <h1 style="margin: 0; font-size: 24px;">Federação Paulista de Jiu-Jitsu Internacional</h1>
        </div>
        
        <div style="padding: 25px;">
            
            <h2 style="color: #2520a0; font-size: 20px; margin-bottom: 15px; border-bottom: 2px solid #e9b949; padding-bottom: 5px;">Lembrete de Cobrança</h2>
            
            <p style="margin-bottom: 15px;">Olá <strong style="color: #2520a0;">' . $at->nome . '</strong>,</p>
            
            <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
                <p style="margin: 0; font-size: 16px; color: #856404;">
                    ⚠️ <strong>Há uma cobrança em aberto</strong> no seu nome para o evento:
                </p>
                <h3 style="color: #2520a0; margin: 10px 0; font-size: 18px;">' . $nome_ev . '</h3>
            </div>
            
            <p style="margin-bottom: 15px;">Para regularizar sua situação e garantir sua participação, acesse a página do evento:</p>
            
            <div style="text-align: center; margin: 25px 0;">
                <a href="https://fpjji.com/eventos_cadastrados.php" 
                   style="background-color: #d14141; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">
                   📋 Ver Eventos Cadastrados
                </a>
            </div>
            
            <div style="background-color: #f8f9fa; border-radius: 8px; padding: 15px; margin: 20px 0; border: 1px solid #e0e0e0;">
                <h3 style="color: #2520a0; margin-top: 0; margin-bottom: 12px; font-size: 18px;">📝 Como Regularizar:</h3>
                <ol style="margin: 10px 0; padding-left: 20px;">
                    <li style="margin-bottom: 8px; color: #2d3748;">Acesse <a href="https://fpjji.com/eventos_cadastrados.php" style="color: #2520a0; text-decoration: none; font-weight: bold;">Eventos Cadastrados</a></li>
                    <li style="margin-bottom: 8px; color: #2d3748;">Localize o evento <strong>' . $nome_ev . '</strong></li>
                    <li style="margin-bottom: 8px; color: #2d3748;">Clique em "Pagamento"</li>
                    <li style="margin-bottom: 8px; color: #2d3748;">Siga as instruções para concluir o pagamento</li>
                </ol>
            </div>
            
            <p style="margin-bottom: 15px; color: #666; font-size: 14px;">
                💡 <em>Em caso de dúvidas ou se você já efetuou o pagamento, entre em contato conosco para regularizar sua situação.</em>
            </p>
        </div>
        
        <div style="background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 5px 5px;">
            <p style="margin: 0 0 10px 0; font-style: italic;">Esta é uma mensagem automática, por favor não responda este e-mail.</p>            
            <p style="margin: 0 0 10px 0;">Caso não queira receber este tipo de notificação, você pode desativar em <a href="https://fpjji.com/edit.php" style="color: #2520a0; text-decoration: none; font-weight: bold;">Editar Dados</a></p>
            <p style="margin: 0 0 10px 0;">© ' . date('Y') . ' FPJJI - Federação Paulista de Jiu-Jitsu Internacional. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>';
            break;
    }

    return $msg;
}
?>