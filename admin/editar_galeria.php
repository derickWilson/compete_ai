<?php
session_start();
require "../func/is_adm.php";
is_adm();
require_once "../classes/galeriaService.php";

try {
    $con = new Conexao();
    $galeria = new Galeria();
    $galeriaServ = new GaleriaService($con, $galeria);
    
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $fotoDetalhes = $galeriaServ->getById($id);
    } else {
        header("Location: galeria.php?message=Imagem não encontrada&message_type=error");
        exit();
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/style.css">
    <link rel="icon" href="/estilos/icone.jpeg">    <title>Editar Galeria</title>
</head>
<body>
    <?php include "../menu/add_menu.php"; ?>
    
    <div class="container">
        <div class="principal">
            <h2 class="section-title" style="color: var(--primary-dark); text-shadow: none;">Editar Imagem da Galeria</h2>
            
            <form action="recadastra_galeria.php" method="POST" id="editar_galeria" enctype="multipart/form-data" class="form-editar-galeria">
                <div class="imagem-atual">
                    <h4>Imagem Atual:</h4>
                    <div class="imagem-container">
                        <img src="../galeria/<?php echo htmlspecialchars($fotoDetalhes->imagem); ?>" 
                             alt="Imagem Atual" class="imagem-preview">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="imagem_nova" class="label">Nova Imagem (opcional):</label>
                    <input type="file" name="imagem_nova" id="imagem_nova" 
                           accept=".jpg,.jpeg,.png,.webp" class="form-input">
                    <small class="form-text">Formatos aceitos: JPG, JPEG, PNG, WEBP. Tamanho máximo: 10MB</small>
                    
                    <div class="preview-nova" id="previewNova" style="display: none;">
                        <h4>Pré-visualização da Nova Imagem:</h4>
                        <img id="previewImagem" src="" alt="Preview" class="imagem-preview">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="legenda" class="label">Legenda:</label>
                    <input type="text" name="legenda" id="legenda" 
                           value="<?php echo htmlspecialchars($fotoDetalhes->legenda); ?>" 
                           class="form-input" required>
                </div>
                
                <input type="hidden" name="id" value="<?php echo $fotoDetalhes->id; ?>">
                
                <div class="form-actions">
                    <button type="submit" class="botao-acao">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                    <a href="galeria.php" class="botao-voltar">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php include "../menu/footer.php"; ?>

    <script>
        // Preview da nova imagem
        document.getElementById('imagem_nova').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewDiv = document.getElementById('previewNova');
            const previewImg = document.getElementById('previewImagem');
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewDiv.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
                
                // Validação do tamanho do arquivo
                if (file.size > 10 * 1024 * 1024) { // 10MB
                    alert('O arquivo é muito grande. O tamanho máximo permitido é 10MB.');
                    e.target.value = '';
                    previewDiv.style.display = 'none';
                }
                
                // Validação do tipo de arquivo
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Tipo de arquivo não permitido. Use apenas JPG, PNG ou WEBP.');
                    e.target.value = '';
                    previewDiv.style.display = 'none';
                }
            } else {
                previewDiv.style.display = 'none';
            }
        });
    </script>
</body>