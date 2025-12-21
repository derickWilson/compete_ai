<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION["logado"])) {
    header("Location: login.php");
    exit();
}

require_once "classes/atletaService.php";
include "func/clearWord.php";

// Criação do objeto de conexão e atleta
$con = new Conexao();
$att = new Atleta();
$attServ = new atletaService($con, $att);

// Obtém os dados do atleta logado
$atleta = $attServ->getById($_SESSION["id"]);
$faixa_atual = $atleta->faixa;
$diploma_antigo = $atleta->diploma;
$diplomaNovo = "";

// Verifica e processa a edição
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Processa a faixa
    $faixa = isset($_POST["faixa"]) ? cleanWords($_POST["faixa"]) : "";
    
    if (empty($faixa)) {
        header("Location: editarFaixa.php?erro=1");
        exit();
    }
    
    if ($faixa == $faixa_atual) {
        header("Location: editarFaixa.php?erro=2");
        exit();
    }
    
    // Processa o diploma
    $diplomaNovo = $diploma_antigo;
    
    if (isset($_FILES['diploma_novo']) && $_FILES['diploma_novo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $diploma = $_FILES['diploma_novo'];
        
        if ($diploma['error'] !== UPLOAD_ERR_OK) {
            header("Location: editarFaixa.php?erro=3");
            exit();
        }
        
        if ($diploma['size'] == 0) {
            header("Location: editarFaixa.php?erro=4");
            exit();
        }
        
        $extensao = strtolower(pathinfo($diploma['name'], PATHINFO_EXTENSION));
        $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (!in_array($extensao, $extensoesPermitidas)) {
            header("Location: editarFaixa.php?erro=5");
            exit();
        }
        
        if ($diploma["size"] > 5 * 1024 * 1024) { // 5MB máximo
            header("Location: editarFaixa.php?erro=6");
            exit();
        }
        
        $novoNome = 'diploma_' . $_SESSION["id"] . '_' . time() . '.' . $extensao;
        $caminhoParaSalvar = 'diplomas/' . $novoNome;
        
        // Exclui diploma antigo se existir
        if (!empty($diploma_antigo) && file_exists("diplomas/" . $diploma_antigo)) {
            unlink("diplomas/" . $diploma_antigo);
        }
        
        if (move_uploaded_file($diploma["tmp_name"], $caminhoParaSalvar)) {
            $diplomaNovo = $novoNome;
        } else {
            error_log("Erro ao mover arquivo. Caminho: " . $caminhoParaSalvar);
            header("Location: editarFaixa.php?erro=7");
            exit();
        }
    }
    
    // Atualiza no banco
    $att->__set("id", $_SESSION["id"]);
    $att->__set("faixa", $faixa);
    $att->__set("diploma", $diplomaNovo);
    
    if ($attServ->updateFaixa()) {
        header("Location: logout.php?msg=faixa_updated");
        exit();
    } else {
        header("Location: editarFaixa.php?erro=8");
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Faixa - FPJJI</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="/estilos/icone.jpeg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <?php include "menu/add_menu.php"; ?>
    <?php include "include_hamburger.php"; ?>
    
    <div class="container">
        <div class="principal">
            <h1 class="section-title" style="text-align: center;">Solicitar Troca de Faixa</h1>
            
            <?php if (isset($_GET['erro'])): ?>
                <div class="alert-message error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php
                    $erro = $_GET['erro'];
                    switch ($erro) {
                        case 1:
                            echo "<h3>Erro: Faixa não selecionada</h3><p>Por favor, selecione uma faixa para continuar.</p>";
                            break;
                        case 2:
                            echo "<h3>Erro: Faixa inválida</h3><p>A faixa selecionada deve ser diferente da sua faixa atual.</p>";
                            break;
                        case 3:
                            echo "<h3>Erro no upload</h3><p>Ocorreu um erro no upload do diploma. Tente novamente.</p>";
                            break;
                        case 4:
                            echo "<h3>Arquivo vazio</h3><p>O arquivo do diploma está vazio. Envie um arquivo válido.</p>";
                            break;
                        case 5:
                            echo "<h3>Formato inválido</h3><p>Formato de arquivo não suportado. Use JPG, PNG ou PDF.</p>";
                            break;
                        case 6:
                            echo "<h3>Arquivo muito grande</h3><p>O arquivo excede o limite de 5MB. Reduza o tamanho e tente novamente.</p>";
                            break;
                        case 7:
                            echo "<h3>Erro ao salvar</h3><p>Não foi possível salvar o diploma. Tente novamente.</p>";
                            break;
                        case 8:
                            echo "<h3>Erro no sistema</h3><p>Não foi possível processar sua solicitação. Tente mais tarde.</p>";
                            break;
                        default:
                            echo "<h3>Erro</h3><p>Ocorreu um erro ao processar sua solicitação.</p>";
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="aviso info" style="margin: 20px 0; padding: 15px; text-align: center;">
                <i class="fas fa-exclamation-circle"></i>
                <h3>Atenção: Procedimento de Troca de Faixa</h3>
                <p>Ao solicitar uma troca de faixa, sua conta ficará inativa até que o administrador valide o novo diploma.</p>
                <p>Você será deslogado automaticamente após a solicitação.</p>
            </div>
            
            <form action="editarFaixa.php" method="POST" enctype="multipart/form-data" class="form-editar-galeria">
                <div class="form-group">
                    <label for="faixa_atual_display" class="label">Faixa Atual</label>
                    <input type="text" id="faixa_atual_display" blocked value="<?php echo htmlspecialchars($faixa_atual); ?>" class="form-input" readonly style="background-color: #f0f0f0;">
                    <small class="form-text">Esta é sua faixa atual registrada no sistema</small>
                </div>
                
                <div class="form-group">
                    <label for="faixa" class="label">Nova Faixa *</label>
                    <select name="faixa" id="faixa" class="form-input" required>
                        <option value="">Selecione a nova faixa</option>
                        <option value="Branca" <?php echo ($faixa_atual == 'Branca') ? 'disabled' : ''; ?>>Branca</option>
                        <option value="Cinza" <?php echo ($faixa_atual == 'Cinza') ? 'disabled' : ''; ?>>Cinza</option>
                        <option value="Amarela" <?php echo ($faixa_atual == 'Amarela') ? 'disabled' : ''; ?>>Amarela</option>
                        <option value="Laranja" <?php echo ($faixa_atual == 'Laranja') ? 'disabled' : ''; ?>>Laranja</option>
                        <option value="Verde" <?php echo ($faixa_atual == 'Verde') ? 'disabled' : ''; ?>>Verde</option>
                        <option value="Azul" <?php echo ($faixa_atual == 'Azul') ? 'disabled' : ''; ?>>Azul</option>
                        <option value="Roxa" <?php echo ($faixa_atual == 'Roxa') ? 'disabled' : ''; ?>>Roxa</option>
                        <option value="Marrom" <?php echo ($faixa_atual == 'Marrom') ? 'disabled' : ''; ?>>Marrom</option>
                        <option value="Preta" <?php echo ($faixa_atual == 'Preta') ? 'disabled' : ''; ?>>Preta</option>
                    </select>
                    <small class="form-text">Selecione a faixa para a qual deseja graduar</small>
                </div>
                
                <div class="form-group">
                    <label class="label">Diploma Atual</label>
                    <div class="imagem-atual">
                        <div class="imagem-container">
                            <?php if (!empty($diploma_antigo) && file_exists("diplomas/" . $diploma_antigo)): ?>
                                <img src="diplomas/<?php echo htmlspecialchars($diploma_antigo); ?>" 
                                     alt="Diploma Atual" 
                                     class="imagem-preview">
                                <p style="margin-top: 10px; color: var(--gray);">
                                    <i class="fas fa-file-alt"></i> Diploma atual registrado
                                </p>
                            <?php else: ?>
                                <div style="padding: 30px; background: #f8d7da; border-radius: 8px;">
                                    <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #721c24;"></i>
                                    <p style="margin-top: 10px; color: #721c24;">
                                        Nenhum diploma encontrado. É obrigatório enviar um diploma para troca de faixa.
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="diploma_novo" class="label">Novo Diploma *</label>
                    <input type="file" 
                           name="diploma_novo" 
                           id="diploma_novo" 
                           class="form-input" 
                           accept=".jpg,.jpeg,.png,.pdf"
                           required>
                    <small class="form-text">
                        <i class="fas fa-info-circle"></i> Envie o diploma da nova faixa (JPG, PNG ou PDF, máximo 5MB)
                    </small>
                    <div id="preview-container" class="preview-nova" style="display: none; margin-top: 15px;">
                        <p style="font-weight: bold; color: var(--success);">
                            <i class="fas fa-check-circle"></i> Arquivo selecionado:
                        </p>
                        <p id="file-name"></p>
                        <p id="file-size"></p>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 30px;">
                    <div class="termos" style="padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <h4><i class="fas fa-file-contract"></i> Termos e Condições</h4>
                        <p style="margin: 10px 0;">
                            <input type="checkbox" id="termos" name="termos" required>
                            <label for="termos">
                                Declaro que as informações fornecidas são verdadeiras e que o diploma enviado é válido.
                                Entendo que minha conta será suspensa até a validação pela administração.
                            </label>
                        </p>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="botao-acao" onclick="return confirm('Tem certeza que deseja solicitar a troca de faixa? Sua conta será suspensa até a validação.')">
                        <i class="fas fa-paper-plane"></i> Enviar Solicitação
                    </button>
                    <a href="pagina_pessoal.php" class="botao-voltar">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <?php include "menu/footer.php"; ?>
    
    <script>
        // Preview do arquivo selecionado
        document.getElementById('diploma_novo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewContainer = document.getElementById('preview-container');
            const fileName = document.getElementById('file-name');
            const fileSize = document.getElementById('file-size');
            
            if (file) {
                previewContainer.style.display = 'block';
                fileName.textContent = 'Nome: ' + file.name;
                
                // Formatar tamanho do arquivo
                const sizeInMB = file.size / (1024 * 1024);
                fileSize.textContent = 'Tamanho: ' + sizeInMB.toFixed(2) + ' MB';
                
                // Verificar tamanho máximo
                if (file.size > 5 * 1024 * 1024) {
                    fileSize.innerHTML += ' <span style="color: var(--danger);">(Arquivo muito grande!)</span>';
                }
                
                // Verificar extensão
                const validExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
                const fileExtension = file.name.split('.').pop().toLowerCase();
                
                if (!validExtensions.includes(fileExtension)) {
                    fileName.innerHTML += ' <span style="color: var(--danger);">(Formato inválido!)</span>';
                }
            } else {
                previewContainer.style.display = 'none';
            }
        });
        
        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            const faixa = document.getElementById('faixa').value;
            const fileInput = document.getElementById('diploma_novo');
            const termos = document.getElementById('termos');
            
            if (!faixa) {
                e.preventDefault();
                alert('Por favor, selecione uma nova faixa.');
                return false;
            }
            
            if (faixa === "<?php echo $faixa_atual; ?>") {
                e.preventDefault();
                alert('Você deve selecionar uma faixa diferente da atual.');
                return false;
            }
            
            if (!fileInput.files[0]) {
                e.preventDefault();
                alert('É obrigatório enviar o diploma da nova faixa.');
                return false;
            }
            
            if (!termos.checked) {
                e.preventDefault();
                alert('Você deve aceitar os termos e condições.');
                return false;
            }
            
            // Validação de tamanho do arquivo
            const file = fileInput.files[0];
            if (file && file.size > 5 * 1024 * 1024) {
                e.preventDefault();
                alert('O arquivo excede o limite de 5MB. Por favor, reduza o tamanho.');
                return false;
            }
            
            // Validação de extensão
            const validExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
            const fileExtension = file.name.split('.').pop().toLowerCase();
            if (!validExtensions.includes(fileExtension)) {
                e.preventDefault();
                alert('Formato de arquivo inválido. Use JPG, PNG ou PDF.');
                return false;
            }
            
            return true;
        });
        
        // Desabilitar a faixa atual no dropdown
        document.addEventListener('DOMContentLoaded', function() {
            const faixaAtual = "<?php echo $faixa_atual; ?>";
            const select = document.getElementById('faixa');
            
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].value === faixaAtual) {
                    select.options[i].disabled = true;
                    select.options[i].text += ' (Faixa Atual)';
                }
            }
        });
    </script>
</body>

</html>