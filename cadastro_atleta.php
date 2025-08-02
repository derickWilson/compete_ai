<?php
// Start session at the beginning
session_start();

if (isset($_SESSION["logado"])) {
    header("Location: index.php");
    exit();
} else {
    require_once "classes/atletaService.php";
    try {
        $conn = new Conexao();
        $atleta = new Atleta();
        $ev = new atletaService($conn, $atleta);
        $academias = $ev->getAcademias();
    } catch (Exception $e) {
        die('<div class="erro">Erro ao carregar academias: ' . $e->getMessage() . '</div>');
    }
}

// Handle error messages
$erro_message = '';
if (isset($_GET['erro'])) {
    switch ($_GET['erro']) {
        case 1:
            $erro_message = 'Este e-mail já está cadastrado. Por favor, utilize outro e-mail ou faça login.';
            break;
        case 2:
            $erro_message = 'Por favor, envie uma foto no formato correto (JPG, JPEG ou PNG).';
            break;
        case 3:
            $erro_message = 'Por favor, selecione uma academia válida.';
            break;
        case 5:
            $erro_message = $_SESSION['erro_cadastro'] ?? 'Ocorreu um erro durante o cadastro. Por favor, tente novamente.';
            unset($_SESSION['erro_cadastro']); // Limpa após usar
            break;
        case 6:
            $erro_message = $_SESSION['erro_cadastro'] ?? 'CPF inválido. Por favor, verifique o número digitado.';
            break;
        default:
            $erro_message = 'Ocorreu um erro durante o cadastro. Por favor, tente novamente.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Atleta</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="/estilos/icone.jpeg">
</head>

<body>
    <?php include "menu/add_menu.php"; ?>

    <div class="principal">
        <?php if (!empty($erro_message)): ?>
            <div class="erro"><?= $erro_message ?></div>
        <?php endif; ?>

        <form method="post" action="cadastrar.php" enctype="multipart/form-data">
            <h3>Dados Pessoais</h3>
            Foto 3x4<br>
            <input type="file" placeholder="FOTO" name="foto" id="foto" accept=".jpg,.jpeg,.png" required><br>
            <span class="aviso">Tamanho máximo: 12MB. Formatos aceitos: JPG, JPEG, PNG</span><br>

            Nome Completo<input name="nome" type="text" placeholder="Nome completo" required><br>
            CPF<input name="cpf" type="text" placeholder="000.000.000-00" maxlength="14" required><br>
            Genero
            <select name="genero" required>
                <option value="">Selecione...</option>
                <option value="Masculino">Masculino</option>
                <option value="Feminino">Feminino</option>
            </select><br>

            Email <input name="email" type="email" placeholder="exemplo@email.com" required><br>
            Senha <input type="password" name="senha" id="senha" required><br>
            <span class="aviso">Mínimo 8 caracteres, incluindo letras e números</span><br>

            Data de Nascimento <input type="date" name="data_nascimento" id="data_nasc" required><br>
            Telefone<br>
            <input type="text" name="ddd" value="+55" style="width: 50px;">
            <input maxlength="15" type="tel" name="fone" id="telefone" placeholder="(00) 00000-0000" required><br>

            Academia/Equipe
            <select name="academia" id="academia" required>
                <option value="">-- Selecione sua academia --</option>
                <?php foreach ($academias as $academia): ?>
                    <option value="<?= $academia->id ?>"><?= htmlspecialchars($academia->nome) ?></option>
                <?php endforeach; ?>
            </select><br>
            <span class="aviso">Caso sua academia não apareça, espere sua validação ou entre em contato
                conosco.</span><br>

            Faixa
            <select id="faixas" name="faixa" required>
                <option value="">Selecione sua graduação</option>
                <option value="Branca">Branca</option>
                <option value="Cinza">Cinza</option>
                <option value="Amarela">Amarela</option>
                <option value="Laranja">Laranja</option>
                <option value="Verde">Verde</option>
                <option value="Azul">Azul</option>
                <option value="Roxa">Roxa</option>
                <option value="Marrom">Marrom</option>
                <option value="Preta">Preta</option>
            </select><br>


            Foto Diploma ou Foto do RG<br>
            <input type="file" placeholder="DIPLOMA" name="diploma" id="diploma" accept=".jpg,.jpeg,.png,.pdf"
                required><br>
            <span class="aviso">Envie uma foto ou scan do seu diploma ou RG (PDF, JPG, PNG)</span><br>

            Peso <input type="number" name="peso" min="10" step="0.05" required> kg<br>
            <input type="hidden" name="tipo" value="AT">
            <input type="submit" value="Cadastrar" class="botao-acao"><br>
        </form>
        <a class="link" href="index.php">Voltar</a>
    </div>

    <?php include "menu/footer.php"; ?>
</body>

</html>