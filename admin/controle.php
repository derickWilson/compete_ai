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
    <style>
        /* Estilo para página de controle de donos de academia */
        .principal.dono-academia {
            border-left: 6px solid #28a745;
            background: linear-gradient(135deg, #f8fff8 0%, #e8f5e8 100%);
        }

        .principal.dono-academia .section-title {
            color: #28a745;
        }

        .principal.dono-academia .section-title::before {
            content: "🏫 ";
        }
    </style>
</head>

<body>
    <?php include "../menu/add_menu.php"; ?>
    <?php include "../include_hamburger.php"; ?>

    <div class="container">
        <!-- Adicione uma classe ao container principal -->
        <div class="principal <?php echo $usuario->responsavel == 1 ? 'dono-academia' : ''; ?>">
            <h1 class="section-title" style="color: var(--primary-dark); text-shadow: none;">Controle de Usuário</h1>

            <center>
                <img class="perfil" src="/fotos/<?php echo $usuario->foto; ?>" alt="Foto do usuário">
            </center>

            <form action="admin_edit.php" method="POST" class="form-controle">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($usuario->id); ?>">

                <div class="info-usuario">
                    <div class="info-item">
                        <label class="label">Nome: </label>
                        <span class="valor"><?php echo htmlspecialchars($usuario->nome); ?></span>
                    </div>

                    <div class="info-item">
                        <label class="label">Email: </label>
                        <span class="valor">
                            <a href="mailto:<?php echo $usuario->email; ?>" class="link">
                                <?php echo $usuario->email; ?>
                            </a>
                        </span>
                    </div>

                    <div class="info-item">
                        <label class="label">Telefone: </label>
                        <span class="valor">
                            <a href="https://wa.me/<?php echo $foneLimpo; ?>" target="_blank" class="link">
                                <?php echo $usuario->fone; ?>
                            </a>
                        </span>
                    </div>
                    <div class="info-item">
                        <label class="label">Endereço: </label>
                        <span class="valor">
                            <?php if (!empty($usuario->endereco_completo)): ?>
                                <div style="max-width: 400px; word-wrap: break-word; line-height: 1.4;">
                                    <?php echo nl2br(htmlspecialchars($usuario->endereco_completo)); ?>
                                </div>
                            <?php else: ?>
                                <span style="color: #888; font-style: italic;">Não informado</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <label class="label">Data de Nascimento: </label>
                        <span class="valor"><?php echo $usuario->data_nascimento; ?></span>
                    </div>

                    <div class="info-item">
                        <label class="label">Academia: </label>
                        <span class="valor"><?php echo htmlspecialchars($usuario->academia); ?></span>
                    </div>

                    <div class="info-item">
                        <label class="label">Faixa Atual: </label>
                        <span class="valor"><?php echo htmlspecialchars($usuario->faixa); ?></span>
                    </div>

                    <?php if (!empty($usuario->diploma)): ?>
                        <div class="info-item">
                            <label class="label">Diploma: </label>
                            <span class="valor">
                                <a href="../diplomas/<?php echo $usuario->diploma; ?>" download class="botao-acao"
                                    style="padding: 5px 10px; font-size: 14px;">
                                    Baixar Diploma
                                </a>
                            </span>
                        </div>
                    <?php else: ?>
                        <div class="info-item">
                            <label class="label">Diploma: </label>
                            <span class="valor">Não encontrado</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="controles-adm">
                    <div class="form-group">
                        <label for="validado" class="checkbox-label">
                            <input type="checkbox" name="validado" id="validado" <?php echo $usuario->validado ? 'checked' : ''; ?>>
                            Validado
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="faixas" class="label">Nova Faixa:</label>
                        <select id="faixas" name="faixa" class="form-select" required>
                            <option value="Branca" <?php echo $usuario->faixa == 'Branca' ? 'selected' : ''; ?>>Branca
                            </option>
                            <option value="Cinza" <?php echo $usuario->faixa == 'Cinza' ? 'selected' : ''; ?>>Cinza
                            </option>
                            <option value="Amarela" <?php echo $usuario->faixa == 'Amarela' ? 'selected' : ''; ?>>Amarela
                            </option>
                            <option value="Laranja" <?php echo $usuario->faixa == 'Laranja' ? 'selected' : ''; ?>>Laranja
                            </option>
                            <option value="Verde" <?php echo $usuario->faixa == 'Verde' ? 'selected' : ''; ?>>Verde
                            </option>
                            <option value="Azul" <?php echo $usuario->faixa == 'Azul' ? 'selected' : ''; ?>>Azul</option>
                            <option value="Roxa" <?php echo $usuario->faixa == 'Roxa' ? 'selected' : ''; ?>>Roxa</option>
                            <option value="Marrom" <?php echo $usuario->faixa == 'Marrom' ? 'selected' : ''; ?>>Marrom
                            </option>
                            <option value="Preta" <?php echo $usuario->faixa == 'Preta' ? 'selected' : ''; ?>>Preta
                            </option>
                        </select>
                    </div>
                </div>

                <div class="acoes-controle">
                    <button type="submit" class="botao-acao">Salvar Alterações</button>
                    <a class="danger" href="/admin/excluir.php?id=<?php echo $usuario->id; ?>"
                        onclick="return confirm('Tem certeza que deseja excluir este usuário? Esta ação não pode ser desfeita.')">
                        EXCLUIR
                    </a>
                    <br>
                    <a href="painel_administrativo.php" class="botao-voltar">Voltar</a>
                </div>
            </form>
        </div>
    </div>

    <?php include "../menu/footer.php"; ?>
</body>