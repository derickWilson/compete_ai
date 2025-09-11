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
$atletas = new Atleta();
$attServ = new atletaService($con, $atletas);

// Obtém os dados do atleta logado
$atleta = $attServ->getById($_SESSION["id"]);

// Extrair DDD e número do telefone completo
$telefone_completo = $atleta->fone;
$ddd = '55'; // Valor padrão para Brasil
$numero_telefone = '';

if (!empty($telefone_completo)) {
    // Remove o código do país (+55) se existir
    $telefone_sem_codigo_pais = str_replace('+55', '', $telefone_completo);
    
    // Extrai os primeiros 2 dígitos como DDD
    if (strlen($telefone_sem_codigo_pais) >= 2) {
        $ddd = substr($telefone_sem_codigo_pais, 0, 2); // DDD (11, 21, etc.)
        $numero_telefone = substr($telefone_sem_codigo_pais, 2); // Número real
    } else {
        $numero_telefone = $telefone_sem_codigo_pais;
    }
}

// Formatar o número para exibição
$telefone_formatado = $numero_telefone;
if (!empty($numero_telefone)) {
    // Aplica formatação (XX) XXXXX-XXXX
    if (strlen($numero_telefone) === 8) {
        $telefone_formatado = substr($numero_telefone, 0, 4) . '-' . substr($numero_telefone, 4);
    } elseif (strlen($numero_telefone) === 9) {
        $telefone_formatado = substr($numero_telefone, 0, 5) . '-' . substr($numero_telefone, 5);
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Meus Dados - Sistema de Competições</title>
    <link rel="stylesheet" href="style.css">
    <!-- Adicionando ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ESTILOS ESPECÍFICOS PARA A PÁGINA DE EDIÇÃO */
        /* ... (estilos mantidos) ... */
    </style>
</head>
<body>
    <?php include "menu/add_menu.php"; ?>
    
    <div class="container">
        <div class="principal">
            <div class="form-header">
                <h1><i class="fas fa-user-edit"></i> Editar Meus Dados</h1>
                <p>Atualize suas informações pessoais e mantenha seu perfil sempre atualizado</p>
            </div>
            
            <form method="POST" action="editar.php" enctype="multipart/form-data" class="edit-container">
                <!-- Seção de Foto -->
                <div class="photo-section">
                    <img src="/fotos/<?php echo htmlspecialchars($_SESSION["foto"]); ?>" 
                         class="current-photo" 
                         id="currentPhoto"
                         alt="Foto atual"
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iI2RkZCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBdyT0iLjM1ZW0iIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5OTkiPlNlbSBGb3RvPC90ZXh0Pjwvc3ZnPg=='">
                    
                    <div class="file-input-container">
                        <label for="foto_nova" class="file-input-label">
                            <i class="fas fa-camera"></i> Alterar Foto
                        </label>
                        <input type="file" name="foto_nova" id="foto_nova" 
                               accept=".jpg,.jpeg,.png" class="file-input"
                               onchange="previewImage(this)">
                    </div>
                    
                    <span class="file-info">Formatos: JPG, PNG. Tamanho máximo: 2MB</span>
                    
                    <div class="preview-container">
                        <img id="photoPreview" class="photo-preview" alt="Prévia da nova foto">
                    </div>
                </div>
                
                <!-- Informações Pessoais -->
                <div class="form-section">
                    <h3><i class="fas fa-user-circle"></i> Informações Pessoais</h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-input" 
                                   placeholder="exemplo@email.com" 
                                   value="<?php echo htmlspecialchars($atleta->email); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Telefone</label>
                            <div class="telefone-container">
                                <input type="text" name="ddd" id="ddd" class="ddd-input"
                                       value="<?php echo htmlspecialchars($ddd); ?>" maxlength="2" placeholder="DDD"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                                <input type="tel" name="fone" id="fone" class="telefone-input" 
                                       maxlength="15" placeholder="(00) 00000-0000" 
                                       value="<?php echo htmlspecialchars($telefone_formatado); ?>"
                                       oninput="formatPhone(this)" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="peso" class="form-label">Peso (kg)</label>
                        <input type="number" name="peso" id="peso" class="form-input" 
                               min="10" max="200" step="0.05" 
                               value="<?php echo htmlspecialchars($atleta->peso); ?>" required>
                        <span class="file-info">Informe seu peso atual para competições</span>
                    </div>
                </div>
                
                <!-- Informações de Faixa -->
                <div class="form-section">
                    <h3><i class="fas fa-award"></i> Informações de Faixa</h3>
                    
                    <div class="faixa-info">
                        <span class="faixa-badge"><?php echo htmlspecialchars($_SESSION["faixa"]); ?></span>
                        <span>Esta é sua faixa atual no sistema</span>
                    </div>
                    
                    <div class="aviso-troca">
                        <strong><i class="fas fa-exclamation-triangle"></i> Atenção:</strong> 
                        Para alterar sua faixa, utilize o botão específico abaixo. 
                        A troca de faixa requer aprovação do administrador e pode levar até 48 horas.
                    </div>
                    
                    <a href="editarFaixa.php" class="troca-faixa-btn">
                        <i class="fas fa-exchange-alt"></i> Solicitar Troca de Faixa
                    </a>
                </div>
                
                <!-- Preferências de Notificação -->
                <div class="form-section">
                    <h3><i class="fas fa-bell"></i> Preferências de Notificação</h3>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" name="permissao_email" id="permissao_email" value="1"
                            <?php echo $atleta->permissao_email ? 'checked' : ''; ?>>
                        <label for="permissao_email" class="form-label">
                            Desejo receber notificações por Email sobre eventos e novidades
                        </label>
                    </div>
                </div>
                
                <!-- Ações do Formulário -->
                <div class="form-actions">
                    <button type="submit" class="botao-acao">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                    
                    <a href="pagina_pessoal.php" class="botao-voltar">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <?php include "menu/footer.php"; ?>
    
    <script>
        // Função para pré-visualizar a imagem
        function previewImage(input) {
            const preview = document.getElementById('photoPreview');
            const current = document.getElementById('currentPhoto');
            const fileInfo = document.querySelector('.file-info');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const fileSize = file.size / 1024 / 1024; // MB
                const validTypes = ['image/jpeg', 'image/png'];
                
                // Verificar tipo de arquivo
                if (!validTypes.includes(file.type)) {
                    alert('Por favor, selecione apenas imagens JPG ou PNG.');
                    input.value = '';
                    return;
                }
                
                // Verificar tamanho do arquivo
                if (fileSize > 2) {
                    alert('A imagem deve ter no máximo 2MB.');
                    input.value = '';
                    return;
                }
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    current.style.display = 'none';
                    fileInfo.textContent = `Imagem selecionada: ${file.name} (${(fileSize).toFixed(2)}MB)`;
                    fileInfo.style.color = 'var(--success)';
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
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
        document.addEventListener('DOMContentLoaded', function() {
            const phoneInput = document.getElementById('fone');
            if (phoneInput.value) {
                // Forçar formatação do valor existente
                formatPhone(phoneInput);
            }
            
            // Adicionar validação de peso
            const pesoInput = document.getElementById('peso');
            pesoInput.addEventListener('change', function() {
                if (this.value < 10) {
                    this.value = 10;
                } else if (this.value > 200) {
                    this.value = 200;
                }
            });
            
            // Validar DDD (apenas números, máximo 2 dígitos)
            const dddInput = document.getElementById('ddd');
            dddInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 2) {
                    this.value = this.value.slice(0, 2);
                }
            });
        });
    </script>
</body>
</html>