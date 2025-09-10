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
        .edit-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            border-radius: 12px;
            color: white;
        }
        
        .form-header h1 {
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .form-header p {
            opacity: 0.9;
            font-size: 16px;
        }
        
        .photo-section {
            text-align: center;
            margin-bottom: 30px;
            padding: 25px;
            background: var(--light);
            border-radius: 12px;
            box-shadow: var(--box-shadow);
        }
        
        .current-photo {
            width: 180px;
            height: 180px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid var(--primary);
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .file-input-container {
            position: relative;
            display: inline-block;
            margin-top: 15px;
        }
        
        .file-input-label {
            background-color: var(--primary);
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            font-weight: 500;
        }
        
        .file-input-label:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .file-input {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--primary-dark);
            font-size: 15px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
            font-family: inherit;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(50, 46, 192, 0.1);
        }
        
        .form-section {
            background: var(--light);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: var(--box-shadow);
            border-left: 4px solid var(--primary);
        }
        
        .form-section h3 {
            color: var(--primary-dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
            padding: 10px;
            background: white;
            border-radius: 8px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: var(--primary);
        }
        
        .faixa-info {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #e8f4ff;
            border-radius: 8px;
            margin: 15px 0;
            border: 1px solid #b8daff;
        }
        
        .faixa-badge {
            background: var(--primary);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .aviso-troca {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .troca-faixa-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: var(--accent);
            color: var(--dark);
            padding: 12px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            margin: 10px 0;
        }
        
        .troca-faixa-btn:hover {
            background-color: #d4a017;
            transform: translateY(-2px);
            text-decoration: none;
            color: var(--dark);
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .preview-container {
            margin-top: 15px;
            text-align: center;
        }
        
        .photo-preview {
            max-width: 180px;
            max-height: 180px;
            border-radius: 50%;
            border: 3px solid var(--primary);
            display: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .telefone-container {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .ddd-input {
            width: 80px;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
        }
        
        .telefone-input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
        }
        
        .ddd-input:focus,
        .telefone-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(50, 46, 192, 0.1);
        }
        
        .file-info {
            font-size: 13px;
            color: var(--gray);
            margin-top: 10px;
            display: block;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .form-actions button,
            .form-actions a {
                width: 100%;
                text-align: center;
            }
            
            .telefone-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            .ddd-input {
                width: 100%;
            }
        }
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
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iI2RkZCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBkeT0iLjM1ZW0iIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5OTkiPlNlbSBGb3RvPC90ZXh0Pjwvc3ZnPg=='">
                    
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
                                       value="+55" maxlength="3" readonly>
                                <input type="tel" name="fone" id="fone" class="telefone-input" 
                                       maxlength="19" placeholder="(00) 00000-0000" 
                                       value="<?php echo htmlspecialchars($atleta->fone); ?>"
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
        });
    </script>
</body>
</html>