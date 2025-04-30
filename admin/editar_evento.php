<?php
session_start();
require "../func/is_adm.php";
is_adm();

try {
    require_once "../classes/eventosServices.php";
    include "../func/clearWord.php";
} catch (\Throwable $th) {
    die('Erro: '. $th->getMessage());
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

if (!$eventoDetails) {
    header("Location: /eventos.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar evento - <?php echo htmlspecialchars($eventoDetails->nome); ?></title>
</head>
<body>
<?php include "../menu/add_menu.php"; ?>

<div class="principal">
    <h2>Editar Evento</h2>
    
    <form action="recadastrar_evento.php" method="POST" enctype="multipart/form-data">
        <!-- Campo de imagem -->
        <div>
            <label>Imagem atual:</label><br>
            <img src="/uploads/<?php echo htmlspecialchars($eventoDetails->imagen); ?>" 
                 alt="Imagem do evento" width="300"><br>
            <label>Nova Imagem:</label>
            <input type="file" name="imagen_nova" accept="image/*">
        </div>
        
        <!-- Campo de documento -->
        <div>
            <label>Documento atual:</label>
            <?php if(!empty($eventoDetails->doc)): ?>
                <a href="/docs/<?php echo htmlspecialchars($eventoDetails->doc); ?>" target="_blank">Visualizar</a>
            <?php else: ?>
                <span>Nenhum documento enviado</span>
            <?php endif; ?>
            <br>
            <label>Novo Documento (PDF):</label>
            <input type="file" name="nDoc" accept=".pdf">
        </div>
        
        <!-- Campos principais -->
        <div>
            <label>Nome do evento:</label>
            <input type="text" name="nome_evento" required 
                   value="<?php echo htmlspecialchars($eventoDetails->nome); ?>">
        </div>
        
        <div>
            <label>Data do Campeonato:</label>
            <input type="date" name="data_camp" required 
                   value="<?php echo htmlspecialchars($eventoDetails->data_evento); ?>">
        </div>
        
        <div>
            <label>Local:</label>
            <input type="text" name="local_camp" required 
                   value="<?php echo htmlspecialchars($eventoDetails->local_evento); ?>">
        </div>
        
        <div>
            <label>Descrição do evento:</label>
            <textarea name="desc_Evento" required><?php 
                echo htmlspecialchars(trim($eventoDetails->descricao)); 
            ?></textarea>
        </div>
        
        <div>
            <label>Data limite para inscrições:</label>
            <input type="date" name="data_limite" required 
                   value="<?php echo htmlspecialchars($eventoDetails->data_limite); ?>">
        </div>
        
        <!-- Modalidades -->
        <div>
            <label>Modalidades:</label><br>
            <input type="checkbox" name="tipo_com" id="tipo_com" value="1"
                <?php echo $eventoDetails->tipo_com == 1 ? "checked" : ""; ?>>
            <label for="tipo_com">Com Kimono</label><br>
            
            <input type="checkbox" name="tipo_sem" id="tipo_sem" value="1"
                <?php echo $eventoDetails->tipo_sem == 1 ? "checked" : ""; ?>>
            <label for="tipo_sem">Sem Kimono</label>
        </div>
        
        <!-- Preços -->
        <div>
            <label>Preço geral (acima de 15 anos):</label>
            <input type="number" name="preco" step="0.01" min="0" required
                   value="<?php echo htmlspecialchars($eventoDetails->preco); ?>">
        </div>
        
        <div>
            <label>Preço Absoluto:</label>
            <input type="number" name="preco_abs" step="0.01" min="0" required
                   value="<?php echo htmlspecialchars($eventoDetails->preco_abs); ?>">
        </div>
        
        <div>
            <label>Preço para menores de 15 anos:</label>
            <input type="number" name="preco_menor" step="0.01" min="0" required
                   value="<?php echo htmlspecialchars($eventoDetails->preco_menor); ?>">
        </div>
        
        <input type="hidden" name="id" value="<?php echo $eventoId; ?>">
        
        <div>
            <input type="submit" value="Salvar Alterações">
            <a href="/eventos.php?id=<?php echo $eventoId; ?>">Cancelar</a>
        </div>
    </form>
</div>

<?php include "/menu/footer.php"; ?>
</body>
</html>