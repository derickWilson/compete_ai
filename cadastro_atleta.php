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
// Substitua o switch de mensagens de erro por:
if (isset($_GET['erro'])) {
    switch ($_GET['erro']) {
        case 1:
            $erro_message = 'Este e-mail já está cadastrado. Por favor, utilize outro e-mail ou faça login.';
            break;
        case 2:
            $erro_message = 'Arquivo inválido. Por favor, envie uma foto nos formatos JPG, JPEG ou PNG. Tamanho máximo: 12MB.';
            break;
        case 3:
            $erro_message = 'Por favor, selecione uma academia válida da lista. Caso sua academia não apareça, aguarde a validação ou entre em contato conosco.';
            break;
        case 5:
            $erro_message = isset($_SESSION['erro_cadastro']) ? $_SESSION['erro_cadastro'] : 'Ocorreu um erro durante o cadastro. Por favor, verifique todos os dados e tente novamente.';
            unset($_SESSION['erro_cadastro']);
            break;
        case 6:
            $erro_message = 'CPF inválido. Por favor, verifique o número digitado.';
            break;
        case 7:
            $erro_message = 'Este CPF já está cadastrado. Por favor, utilize outro CPF ou faça login.';
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
    <?php include "include_hamburger.php"; ?>


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
            <input type="text" name="ddd" value="55" style="width: 60px;">
            <input oninput="formatPhone(this)" maxlength="15" type="tel" name="fone" id="fone"
                placeholder="(00) 00000-0000" required><br>

            Endereço Completo <br>
            <textarea name="endereco_completo" id="endereco_completo"
                placeholder="Rua, número, bairro, cidade, complemento..." maxlength="255" rows="3" style="width: 300px;"
                required></textarea><br>
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
    <script>
        // Função para formatar telefone
        function formatPhone(input) {
            // Remove tudo que não é número
            let value = input.value.replace(/\D/g, '');

            // Aplica a máscara
            if (value.length <= 11) {
                value = value.replace(/(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
            }

            input.value = value;
        }

        // Inicializar formatação do telefone se já houver valor
        document.addEventListener('DOMContentLoaded', function () {
            const phoneInput = document.getElementById('fone');
            if (phoneInput.value) {
                // Forçar formatação do valor existente
                formatPhone(phoneInput);
            }

            // Validar DDD (apenas números, máximo 4 dígitos)
            const dddInput = document.getElementById('ddd');
            dddInput.addEventListener('input', function () {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 4) {
                    this.value = this.value.slice(0, 2);
                }
            });
        });
    </script>
</body>

</html>