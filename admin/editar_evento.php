<?php
session_start();
require "../func/is_adm.php";
is_adm();

try {
    require_once "../classes/eventosServices.php";
    include "../func/clearWord.php";
} catch (\Throwable $th) {
    error_log('Erro ao carregar dependências: '. $th->getMessage());
    die('Erro ao carregar a página. Por favor, tente novamente.');
}

// Verificação segura do ID do evento
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /eventos.php");
    exit();
}

$conn = new Conexao();
$ev = new Evento();
$evserv = new eventosService($conn, $ev);

$eventoId = (int) cleanWords($_GET['id']);
$eventoDetails = $evserv->getById($eventoId);

if (!$eventoDetails || !isset($eventoDetails->id)) {
    header("Location: /eventos.php");
    exit();
}

// Garante que todas as propriedades esperadas existam
$propriedadesObrigatorias = ['nome', 'descricao', 'data_limite', 'data_evento', 'local_evento', 
                           'tipo_com', 'tipo_sem', 'imagen', 'preco', 'preco_menor', 'preco_abs', 'doc'];
foreach ($propriedadesObrigatorias as $prop) {
    if (!property_exists($eventoDetails, $prop)) {
        $eventoDetails->$prop = null;
    }
}

// Mensagens da sessão
$mensagem = $_SESSION['mensagem'] ?? '';
unset($_SESSION['mensagem']);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar evento - <?= htmlspecialchars($eventoDetails->nome) ?></title>
</head>
<body>
<?php include "../menu/add_menu.php"; ?>

<div class="principal">
    <h2>Editar Evento</h2>
    
    <?php if ($mensagem): ?>
        <div class="mensagem"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <form action="recadastrar_evento.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $eventoId ?>">
        
        <!-- Seção de Imagem -->
        <div class="form-section">
            <h3>Imagem do Evento</h3>
            <?php if ($eventoDetails->imagen): ?>
                <img src="/uploads/<?= htmlspecialchars($eventoDetails->imagen) ?>" width="300" alt="Imagem atual"><br>
            <?php endif; ?>
            <input type="file" name="imagen_nova" accept="image/jpeg,image/png">
            <small>Formatos aceitos: JPEG, PNG (Máx. 5MB)</small>
        </div>
        
        <!-- Seção de Documento -->
        <div class="form-section">
            <h3>Documento de Ementa</h3>
            <?php if ($eventoDetails->doc): ?>
                <a href="/docs/<?= htmlspecialchars($eventoDetails->doc) ?>" target="_blank">Visualizar PDF atual</a><br>
            <?php endif; ?>
            <input type="file" name="nDoc" accept=".pdf">
            <small>Apenas PDF (Máx. 5MB)</small>
        </div>
        
        <!-- Informações Básicas -->
        <div class="form-section">
            <h3>Informações do Evento</h3>
            
            <label>Nome do evento:*</label>
            <input type="text" name="nome_evento" required value="<?= htmlspecialchars($eventoDetails->nome) ?>">
            
            <label>Data do Evento:*</label>
            <input type="date" name="data_camp" required value="<?= htmlspecialchars($eventoDetails->data_evento) ?>">
            
            <label>Local:*</label>
            <input type="text" name="local_camp" required value="<?= htmlspecialchars($eventoDetails->local_evento) ?>">
            
            <label>Descrição:*</label>
            <textarea name="desc_Evento" required><?= htmlspecialchars($eventoDetails->descricao) ?></textarea>
        </div>
        
        <!-- Configurações -->
        <div class="form-section">
            <h3>Configurações</h3>
            
            <label>Data limite para inscrições:*</label>
            <input type="date" name="data_limite" required value="<?= htmlspecialchars($eventoDetails->data_limite) ?>">
            
            <label>Modalidades:*</label>
            <div class="checkbox-group">
                <input type="checkbox" name="tipo_com" id="tipo_com" value="1" <?= $eventoDetails->tipo_com ? 'checked' : '' ?>>
                <label for="tipo_com">Com Kimono</label>
                
                <input type="checkbox" name="tipo_sem" id="tipo_sem" value="1" <?= $eventoDetails->tipo_sem ? 'checked' : '' ?>>
                <label for="tipo_sem">Sem Kimono</label>
            </div>
        </div>
        
        <!-- Valores -->
        <div class="form-section">
            <h3>Valores</h3>
            
            <label>Preço geral (R$):*</label>
            <input type="number" name="preco" step="0.01" min="0" required 
                   value="<?= number_format($eventoDetails->preco, 2, '.', '') ?>">
            
            <label>Preço Absoluto (R$):*</label>
            <input type="number" name="preco_abs" step="0.01" min="0" required 
                   value="<?= number_format($eventoDetails->preco_abs, 2, '.', '') ?>">
            
            <label>Preço para menores de 15 anos (R$):*</label>
            <input type="number" name="preco_menor" step="0.01" min="0" required 
                   value="<?= number_format($eventoDetails->preco_menor, 2, '.', '') ?>">
        </div>
        
        <div class="form-actions">
            <button type="submit">Salvar Alterações</button>
            <a href="/eventos.php?id=<?= $eventoId ?>" class="btn-cancel">Cancelar</a>
        </div>
    </form>
</div>
<?php include "../menu/footer.php"; ?>
</body>
</html>