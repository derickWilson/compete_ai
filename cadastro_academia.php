<?php
// Deve ser a PRIMEIRA linha do arquivo, sem espaços antes!
declare(strict_types=1);
// Iniciar buffer de saída
session_start();
ob_start();
// Iniciar sessão ANTES de qualquer saída

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
        die('<div class="erro">Erro ao carregar dados: ' . $e->getMessage() . '</div>');
    }
}

// Handle error messages
$erro_message = '';
// Substitua o switch de mensagens de erro por:
if (isset($_GET['erro'])) {
    switch ($_GET['erro']) {
        case 1:
            $erro_message = 'Este e-mail já está cadastrado. Por favor, utilize outro e-mail ou faça login.';
            break;
        case 2:
            $erro_message = 'Arquivo inválido. Por favor, envie foto e diploma nos formatos JPG, JPEG, PNG ou PDF. Tamanho máximo: 12MB.';
            break;
        case 3:
            $erro_message = 'Por favor, selecione uma academia válida.';
            break;
        case 5:
            $erro_message = isset($_SESSION['erro_cadastro']) ? $_SESSION['erro_cadastro'] : 'Ocorreu um erro durante o cadastro. Por favor, verifique todos os dados e tente novamente.';
            unset($_SESSION['erro_cadastro']);
            break;
        case 6:
            $erro_message = 'CPF inválido. Por favor, verifique o número digitado.';
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
    <title>Filiar Academia</title>
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
            <h3>Dados da academia</h3>
            Nome da Academia/Equipe <input type="text" name="academia" id="academia" required><br>
            CEP da academia<input maxlength="20" type="text" name="cep" id="cep" required><br>
            Cidade<input maxlength="50" type="text" name="cidade" id="cidade" required><br>
            Estado
            <select name="estado" id="estado" required>
                <option value="">Selecione um estado</option>
                <option value="AC">Acre</option>
                <option value="AL">Alagoas</option>
                <option value="AP">Amapá</option>
                <option value="AM">Amazonas</option>
                <option value="BA">Bahia</option>
                <option value="CE">Ceará</option>
                <option value="DF">Distrito Federal</option>
                <option value="ES">Espírito Santo</option>
                <option value="GO">Goiás</option>
                <option value="MA">Maranhão</option>
                <option value="MT">Mato Grosso</option>
                <option value="MS">Mato Grosso do Sul</option>
                <option value="MG">Minas Gerais</option>
                <option value="PA">Pará</option>
                <option value="PB">Paraíba</option>
                <option value="PR">Paraná</option>
                <option value="PE">Pernambuco</option>
                <option value="PI">Piauí</option>
                <option value="RJ">Rio de Janeiro</option>
                <option value="RN">Rio Grande do Norte</option>
                <option value="RS">Rio Grande do Sul</option>
                <option value="RO">Rondônia</option>
                <option value="RR">Roraima</option>
                <option value="SC">Santa Catarina</option>
                <option value="SP">São Paulo</option>
                <option value="SE">Sergipe</option>
                <option value="TO">Tocantins</option>
            </select><br>

            <h3>Dados do Responsável</h3>
            Foto 3x4<br>
            <input type="file" placeholder="FOTO" name="foto" id="foto" accept=".jpg,.jpeg,.png" required><br>
            <span class="aviso">Tamanho máximo: 12MB. Formatos aceitos: JPG, JPEG, PNG</span><br>

            Nome do Responsável <input name="nome" type="text" placeholder="Nome completo" required><br>
            CPF<input name="cpf" type="text" placeholder="000.000.000-00" maxlength="14" required><br>
            Genero
            <select name="genero" required>
                <option value="">Selecione...</option>
                <option value="Masculino">Masculino</option>
                <option value="Feminino">Feminino</option>
            </select><br>

            Email <input name="email" type="email" placeholder="exemplo@email.com" required><br>
            senha <input type="password" name="senha" id="senha" required><br>
            <span class="aviso">Mínimo 8 caracteres, incluindo letras e números</span><br>

            Data de Nascimento <input type="date" name="data_nascimento" id="data_nasc" required><br>
            Telefone<br>
            <input type="text" name="ddd" value="+55" style="width: 50px;">
            <input maxlength="15" type="tel" name="fone" id="telefone" placeholder="(00) 00000-0000" required><br>

            Faixa do Responsável <select id="faixas" name="faixa" required>
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

            Diploma<br>
            <input type="file" placeholder="DIPLOMA" name="diploma" id="diploma" accept=".jpg,.jpeg,.png,.pdf"
                required><br>
            <span class="aviso">Envie uma foto ou scan do seu diploma (PDF, JPG, PNG)</span><br>

            Peso <input type="number" name="peso" min="10" step="0.05" required> kg<br>
            <input type="hidden" name="tipo" value="A">
            <input type="submit" value="Cadastrar" class="botao-acao"><br>
        </form>
        <a class="link" href="index.php">Voltar</a>
    </div>

    <?php
    include "menu/footer.php";
    ob_end_flush();
    ?>
</body>

</html>