<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION["logado"])) {
    header("Location: index.php");
    exit();
}
include "func/calcularIdade.php";
// Acessa as variáveis de sessão
$id = $_SESSION["id"] ?? 'Não disponível';
$foto = $_SESSION["foto"] ?? 'Não disponível';
$nome = $_SESSION["nome"] ?? 'Não disponível';
$email = $_SESSION["email"] ?? 'Não disponível';
$data_nascimento = $_SESSION["data_nascimento"] ?? 'Não disponível';
$idade = $_SESSION["idade"] = calcularIdade($data_nascimento);
$fone = $_SESSION["fone"] ?? 'Não disponível';
$academia = $_SESSION["academia"] ?? 'Não disponível';
$faixa = $_SESSION["faixa"] ?? 'Não disponível';
$peso = $_SESSION["peso"] ?? 'Não disponível';
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Página Pessoal</title>
</head>
<body>
    <?php
    include "menu/add_menu.php";
    ?>
    <div class="principal">
        <h1>Informações Pessoais</h1>
        <center><img class="perfil" src="fotos/<?php echo $foto?>"></center>
        <p><strong>ID:</strong> <?php echo htmlspecialchars($id); ?></p>
        <p><strong>Nome:</strong> <?php echo htmlspecialchars($nome); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
        <p><strong>Idade:</strong> <?php echo htmlspecialchars($idade); ?></p>
        <p><strong>Data de Nascimento:</strong> <?php echo htmlspecialchars($data_nascimento); ?></p>
        <p><strong>Telefone:</strong> <?php echo htmlspecialchars($fone); ?></p>
        <p><strong>Academia:</strong> <?php echo htmlspecialchars($academia); ?></p>
        <p><strong>Faixa:</strong> <?php echo htmlspecialchars($faixa); ?></p>
        <p><strong>Peso:</strong> <?php echo htmlspecialchars($peso); ?></p>

        <a href="index.php">Voltar</a>|<a href="edit.php">Editar</a>
    </div>
<?php
include "menu/footer.php";
?>
</body>
</html>
