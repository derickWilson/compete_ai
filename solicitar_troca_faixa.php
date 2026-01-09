<?php
session_start();

// Verifica se o usuário está logado e é responsável
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || $_SESSION['responsavel'] != 1) {
    header('Location: /login.php');
    exit();
}

require_once "classes/atletaService.php";
require_once "func/clearWord.php";

// Verificar se foi passado o ID do aluno
if (!isset($_GET['aluno_id'])) {
    header('Location: lista_alunos.php?error=Aluno não especificado');
    exit();
}

$alunoId = intval($_GET['aluno_id']);

// Criação dos objetos
$con = new Conexao();
$att = new Atleta();
$attServ = new atletaService($con, $att);

// Obtém os dados do aluno
$aluno = $attServ->getById($alunoId);

// Verificar se o aluno pertence à academia do responsável
if (!$aluno || $aluno->acadid != $_SESSION["academia_id"]) {
    header('Location: lista_alunos.php?error=Acesso não autorizado a este aluno');
    exit();
}

$faixaAtual = $aluno->faixa;
$diplomaAntigo = $aluno->diploma;
$erro = '';

// Processar o formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Processa a faixa
    $faixa = isset($_POST["faixa"]) ? cleanWords($_POST["faixa"]) : "";
    
    if (empty($faixa)) {
        $erro = "Faixa não selecionada";
    } elseif ($faixa == $faixaAtual) {
        $erro = "A faixa selecionada deve ser diferente da atual";
    }
    
    // Processa o diploma
    $diplomaNovo = $diplomaAntigo;
    $diplomaEnviado = false;
    
    if (isset($_FILES['diploma_novo']) && $_FILES['diploma_novo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $diploma = $_FILES['diploma_novo'];
        $diplomaEnviado = true;
        
        if ($diploma['error'] !== UPLOAD_ERR_OK) {
            $erro = "Erro no upload do diploma. Código do erro: " . $diploma['error'];
        } elseif ($diploma['size'] == 0) {
            $erro = "Arquivo do diploma está vazio";
        } elseif ($diploma["size"] > 5 * 1024 * 1024) {
            $erro = "Arquivo excede o limite de 5MB";
        } else {
            $extensao = strtolower(pathinfo($diploma['name'], PATHINFO_EXTENSION));
            $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'pdf'];
            
            if (!in_array($extensao, $extensoesPermitidas)) {
                $erro = "Formato de arquivo não suportado. Use JPG, PNG ou PDF";
            } else {
                $novoNome = 'diploma_' . $alunoId . '_' . time() . '.' . $extensao;
                $caminhoParaSalvar = 'diplomas/' . $novoNome;
                
                // Exclui diploma antigo se existir
                if (!empty($diplomaAntigo) && file_exists("diplomas/" . $diplomaAntigo)) {
                    unlink("diplomas/" . $diplomaAntigo);
                }
                
                if (move_uploaded_file($diploma["tmp_name"], $caminhoParaSalvar)) {
                    $diplomaNovo = $novoNome;
                } else {
                    $erro = "Não foi possível salvar o diploma";
                }
            }
        }
    } elseif (empty($diplomaAntigo)) {
        $erro = "É obrigatório enviar um diploma";
    }
    
    // Verificar termos
    if (empty($erro) && (!isset($_POST['termos']) || $_POST['termos'] !== 'on')) {
        $erro = "Você deve aceitar a declaração do responsável";
    }
    
    // Se não houve erro, processar a solicitação
    if (empty($erro)) {
        try {
            // Atualizar faixa e diploma do aluno
            $query = "UPDATE atleta 
                     SET faixa = :faixa, 
                         diploma = :diploma, 
                         validado = 0 
                     WHERE id = :id";
            
            $conn = $con->conectar();
            $stmt = $conn->prepare($query);
            $stmt->bindValue(":faixa", $faixa);
            $stmt->bindValue(":diploma", $diplomaNovo);
            $stmt->bindValue(":id", $alunoId, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                header("Location: lista_alunos.php?success=Solicitação de troca de faixa enviada para " . htmlspecialchars($aluno->nome));
                exit();
            } else {
                $erro = "Erro ao processar solicitação";
            }
        } catch (Exception $e) {
            $erro = "Erro no sistema: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Troca de Faixa para Aluno - FPJJI</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="/estilos/icone.jpeg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <?php include "menu/add_menu.php"; ?>
    <?php include "include_hamburger.php"; ?>
    
    <div class="container">
        <div class="principal">
            <h1 class="section-title">
                <i class="fas fa-user-graduate"></i> Solicitar Troca de Faixa para Aluno
            </h1>
            
            <!-- Informações do aluno -->
            <div class="info-usuario" style="margin-bottom: 25px;">
                <div class="info-item">
                    <span class="label">Aluno:</span>
                    <span class="valor"><?php echo htmlspecialchars($aluno->nome); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Email:</span>
                    <span class="valor"><?php echo htmlspecialchars($aluno->email); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Faixa Atual:</span>
                    <span class="valor"><?php echo htmlspecialchars($faixaAtual); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Academia:</span>
                    <span class="valor"><?php echo htmlspecialchars($aluno->academia); ?></span>
                </div>
            </div>
            
            <!-- Aviso importante -->
            <div class="aviso info" style="margin: 20px 0;">
                <h4><i class="fas fa-exclamation-triangle"></i> Atenção Responsável</h4>
                <p>Ao solicitar uma troca de faixa para seu aluno:</p>
                <ul style="margin: 10px 0 10px 20px;">
                    <li>A conta do aluno ficará inativa até que o administrador valide o novo diploma</li>
                    <li>O aluno não poderá se inscrever em novos eventos até a validação</li>
                    <li>Certifique-se de que o diploma enviado é válido e legível</li>
                    <li>Esta ação não poderá ser desfeita sem contato com a administração</li>
                </ul>
            </div>
            
            <?php if ($erro): ?>
                <div class="alert-message error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Erro no Processamento</h3>
                    <p><?php echo htmlspecialchars($erro); ?></p>
                </div>
            <?php endif; ?>
            
            <form action="solicitar_troca_faixa.php?aluno_id=<?php echo $alunoId; ?>" method="POST" enctype="multipart/form-data" class="form-editar-galeria">
                <div class="form-group">
                    <label for="faixa_atual_display" class="label">Faixa Atual do Aluno</label>
                    <input type="text" id="faixa_atual_display" value="<?php echo htmlspecialchars($faixaAtual); ?>" class="form-input" readonly style="background-color: #f0f0f0;">
                    <small class="form-text">Esta é a faixa atual registrada no sistema</small>
                </div>
                
                <div class="form-group">
                    <label for="faixa" class="label">Nova Faixa *</label>
                    <select name="faixa" id="faixa" class="form-input" required>
                        <option value="">Selecione a nova faixa</option>
                        <option value="Branca" <?php echo ($faixaAtual == 'Branca') ? 'disabled' : ''; ?>>Branca</option>
                        <option value="Cinza" <?php echo ($faixaAtual == 'Cinza') ? 'disabled' : ''; ?>>Cinza</option>
                        <option value="Amarela" <?php echo ($faixaAtual == 'Amarela') ? 'disabled' : ''; ?>>Amarela</option>
                        <option value="Laranja" <?php echo ($faixaAtual == 'Laranja') ? 'disabled' : ''; ?>>Laranja</option>
                        <option value="Verde" <?php echo ($faixaAtual == 'Verde') ? 'disabled' : ''; ?>>Verde</option>
                        <option value="Azul" <?php echo ($faixaAtual == 'Azul') ? 'disabled' : ''; ?>>Azul</option>
                        <option value="Roxa" <?php echo ($faixaAtual == 'Roxa') ? 'disabled' : ''; ?>>Roxa</option>
                        <option value="Marrom" <?php echo ($faixaAtual == 'Marrom') ? 'disabled' : ''; ?>>Marrom</option>
                        <option value="Preta" <?php echo ($faixaAtual == 'Preta') ? 'disabled' : ''; ?>>Preta</option>
                    </select>
                    <small class="form-text">Selecione a faixa para a qual o aluno deseja graduar</small>
                </div>
                
                <div class="form-group">
                    <label class="label">Diploma Atual</label>
                    <div class="imagem-atual">
                        <div class="imagem-container">
                            <?php if (!empty($diplomaAntigo) && file_exists("diplomas/" . $diplomaAntigo)): ?>
                                <img src="diplomas/<?php echo htmlspecialchars($diplomaAntigo); ?>" 
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
                
                <div class="form-group">
                    <div class="termos">
                        <h4><i class="fas fa-file-contract"></i> Declaração do Responsável</h4>
                        <p style="margin: 10px 0;">
                            <input type="checkbox" id="termos" name="termos" required>
                            <label for="termos" style="display: inline; margin-left: 5px;">
                                Declaro como responsável pela academia que as informações fornecidas são verdadeiras 
                                e que o diploma enviado é válido para o aluno <?php echo htmlspecialchars($aluno->nome); ?>.
                                Estou ciente de que a conta do aluno será suspensa até a validação pela administração.
                            </label>
                        </p>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="botao-acao" onclick="return confirm('Tem certeza que deseja solicitar a troca de faixa para <?php echo htmlspecialchars($aluno->nome); ?>? A conta do aluno será suspensa até a validação.')">
                        <i class="fas fa-paper-plane"></i> Enviar Solicitação
                    </button>
                    <a href="lista_alunos.php" class="botao-voltar">
                        <i class="fas fa-arrow-left"></i> Voltar para Lista de Alunos
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
        
        // Desabilitar a faixa atual no dropdown
        document.addEventListener('DOMContentLoaded', function() {
            const faixaAtual = "<?php echo $faixaAtual; ?>";
            const select = document.getElementById('faixa');
            
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].value === faixaAtual) {
                    select.options[i].disabled = true;
                    select.options[i].text += ' (Faixa Atual)';
                }
            }
        });
        
        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            const faixa = document.getElementById('faixa').value;
            const fileInput = document.getElementById('diploma_novo');
            const termos = document.getElementById('termos');
            
            if (!faixa) {
                e.preventDefault();
                alert('Por favor, selecione uma nova faixa para o aluno.');
                return false;
            }
            
            if (faixa === "<?php echo $faixaAtual; ?>") {
                e.preventDefault();
                alert('Você deve selecionar uma faixa diferente da atual do aluno.');
                return false;
            }
            
            if (!fileInput.files[0]) {
                e.preventDefault();
                alert('É obrigatório enviar o diploma da nova faixa.');
                return false;
            }
            
            if (!termos.checked) {
                e.preventDefault();
                alert('Você deve aceitar a declaração do responsável.');
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
    </script>
</body>

</html>