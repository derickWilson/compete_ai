<?php
session_start();
require "../func/is_adm.php";
is_adm();
include_once "../classes/atletaService.php";
require_once __DIR__ . "/../func/clearWord.php";
$conn = new Conexao();
$atleta = new Atleta();
$attServ = new atletaService($conn, $atleta);
if (isset($_GET["user"])) {
    $usuario = $attServ->getById(cleanWords($_GET["user"]));
    $telefoneFormatado = $usuario->fone;
    if (!empty($telefoneFormatado)) {
        // Remove todos os caracteres não numéricos
        $telefoneLimpo = preg_replace('/[^0-9]/', '', $telefoneFormatado);
        
        // Se não começa com código de país (assumindo que números brasileiros tem 10-11 dígitos)
        if (!preg_match('/^\+/', $telefoneFormatado) && strlen($telefoneLimpo) >= 10) {
            $telefoneFormatado = '+55' . $telefoneLimpo;
        }
    }
    // Remove tudo que não for número para o WhatsApp
    $foneLimpo = preg_replace('/[^0-9]/', '', $telefoneFormatado); 
} else {
    echo "Selecione um usuário";
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/style.css">
    <title>Controle de Usuário</title>
    <link rel="icon" href="/estilos/icone.jpeg">
</head>
<body>
    <header>
        <?php include "../menu/add_menu.php"; ?>
    </header>
    <div>
        <h1>Controle de Usuário</h1>
        <center>
        <img class ="perfil" src="/fotos/<?php echo $usuario->foto;?>" alt="foto">
        </center>
        <form action="admin_edit.php" method="POST">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($usuario->id); ?>">
            
        <div>
            <label>Nome: </label>
            <span><?php echo htmlspecialchars($usuario->nome); ?></span><br>
            Email: <a href="mailto:<?php echo $usuario->email;?>"><?php echo $usuario->email; ?></a><br>
            Fone: <a href="https://wa.me/<?php echo $foneLimpo; ?>" target="_blank"><?php echo $usuario->fone; ?></a><br>
            Data de Nascimento: <?php echo $usuario->data_nascimento;?><br>
            <strong>Academia:</strong> <?php echo htmlspecialchars($usuario->academia); ?><br>
        </div>

            <div>
                <label>Faixa Atual:</label>
                <span><?php echo htmlspecialchars($usuario->faixa); ?></span>
            </div>
            
            <?php
            // Verifica se o diploma está disponível
            if (!empty($usuario->diploma)) {
                $caminho = $usuario->diploma; // O caminho completo do diploma
                
                // Gerar o HTML para o link do diploma
                echo '<div>';
                echo '<label>Diploma: <a href="../diplomas/' . $caminho. '" download>Baixe o diploma</a></label>';
                echo '</div>';
            } else {
                echo '<div>Diploma não encontrado.</div>';
            }
            ?>
            
            <div>
                <label>Validado:</label>
                <input type="checkbox" name="validado" <?php echo $usuario->validado ? 'checked' : ''; ?>>
            </div>
            <div>
                <label>Nova Faixa:</label>
                <select id="faixas" name="faixa" required>
                    <option value="Branca" <?php echo $usuario->faixa == 'Branca' ? 'selected' : ''; ?>>Branca</option>
                    <option value="Cinza" <?php echo $usuario->faixa == 'Cinza' ? 'selected' : ''; ?>>Cinza</option>
                    <option value="Amarela" <?php echo $usuario->faixa == 'Amarela' ? 'selected' : ''; ?>>Amarela</option>
                    <option value="Laranja" <?php echo $usuario->faixa == 'Laranja' ? 'selected' : ''; ?>>Laranja</option>
                    <option value="Verde" <?php echo $usuario->faixa == 'Verde' ? 'selected' : ''; ?>>Verde</option>
                    <option value="Azul" <?php echo $usuario->faixa == 'Azul' ? 'selected' : ''; ?>>Azul</option>
                    <option value="Roxa" <?php echo $usuario->faixa == 'Roxa' ? 'selected' : ''; ?>>Roxa</option>
                    <option value="Marrom" <?php echo $usuario->faixa == 'Marrom' ? 'selected' : ''; ?>>Marrom</option>
                    <option value="Preta" <?php echo $usuario->faixa == 'Preta' ? 'selected' : ''; ?>>Preta</option>
                </select>
            </div>
            <div>
            <button type="submit">Salvar Alterações</button>|<a class ="danger" href="/admin/excluir.php?id=<?php echo $usuario->id;?>">EXCLUIR</a><br>
            <a href="painel_administrativo.php">Voltar</a>
            </div>
        </form>
    </div>

<?php
include "/menu/footer.php";
?>
</body>
</html>
