<?php
require "../func/is_adm.php";
is_adm();

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: /eventos.php");
    exit();
}

require_once "../classes/eventosServices.php";
include "../func/clearWord.php";

// Verifica ID do evento
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    die("ID do evento inválido");
}

// Objetos necessários
$evento = new Evento();
$conn = new Conexao();
$adEvento = new eventosService($conn, $evento);

$id = (int) cleanWords($_POST['id']);
$velho = $adEvento->getById($id);

if (!$velho) {
    die("Evento não encontrado");
}

// Processar dados do formulário
$nome = cleanWords($_POST['nome_evento']);
$local = cleanWords($_POST['local_camp']);
$data_camp = cleanWords($_POST['data_camp']);
$descricao = cleanWords($_POST['desc_Evento']);
$data_limite = cleanWords($_POST['data_limite']);
$tipoCom = isset($_POST['tipo_com']) ? 1 : 0;
$tipoSem = isset($_POST['tipo_sem']) ? 1 : 0;
$preco = (float) str_replace(',', '.', cleanWords($_POST['preco']));
$preco_menor = (float) str_replace(',', '.', cleanWords($_POST['preco_menor'])); 
$preco_abs = (float) str_replace(',', '.', cleanWords($_POST['preco_abs']));

// Configurações de upload
$uploadDirImg = "../uploads/";
$uploadDirDoc = "../docs/";
$allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

// Verificar e criar diretórios se não existirem
if (!file_exists($uploadDirImg)) mkdir($uploadDirImg, 0755, true);
if (!file_exists($uploadDirDoc)) mkdir($uploadDirDoc, 0755, true);

// Tratar imagem
$imagenDefinitiva = $velho->imagen;
if (isset($_FILES["imagen_nova"]) && $_FILES["imagen_nova"]["error"] === UPLOAD_ERR_OK) {
    $imagen = $_FILES["imagen_nova"];
    
    // Validar imagem
    if (!in_array($imagen['type'], $allowedImageTypes)) {
        die("Tipo de imagem não permitido. Use JPEG, PNG ou GIF.");
    }
    
    if ($imagen['size'] > $maxFileSize) {
        die("Imagem muito grande. Tamanho máximo: 5MB.");
    }
    
    $ext = pathinfo($imagen['name'], PATHINFO_EXTENSION);
    $novoNome = "img_" . time() . '.' . strtolower($ext);
    $caminhoParaSalvar = $uploadDirImg . $novoNome;
    
    // Remover a antiga se existir
    if (!empty($velho->imagen) && file_exists($uploadDirImg . $velho->imagen)) {
        unlink($uploadDirImg . $velho->imagen);
    }
    
    if (move_uploaded_file($imagen['tmp_name'], $caminhoParaSalvar)) {
        $imagenDefinitiva = $novoNome;
    } else {
        die("Erro ao mover a nova imagem.");
    }
}

// Tratar documento de ementa
$docDef = $velho->doc;
if (isset($_FILES['nDoc']) && $_FILES['nDoc']['error'] === UPLOAD_ERR_OK) {
    $doc = $_FILES['nDoc'];
    
    // Validação do PDF
    if ($doc['type'] != 'application/pdf' || 
        pathinfo($doc['name'], PATHINFO_EXTENSION) != 'pdf' ||
        mime_content_type($doc['tmp_name']) != 'application/pdf') {
        die("Apenas arquivos PDF são permitidos para ementa.");
    }
    
    if ($doc['size'] > $maxFileSize) {
        die("Documento muito grande. Tamanho máximo: 5MB.");
    }
    
    $novoNomeDoc = "doc_" . time() . '.pdf';
    $caminhoDoc = $uploadDirDoc . $novoNomeDoc;
    
    // Remove documento antigo se existir
    if (!empty($velho->doc) && file_exists($uploadDirDoc . $velho->doc)) {
        unlink($uploadDirDoc . $velho->doc);
    }
    
    if (move_uploaded_file($doc['tmp_name'], $caminhoDoc)) {
        $docDef = $novoNomeDoc;
    } else {
        die("Erro ao mover o novo documento.");
    }
}

// Atualizar evento
$evento->__set('id', $id);
$evento->__set('nome', $nome);
$evento->__set('img', $imagenDefinitiva);
$evento->__set('data_camp', $data_camp);
$evento->__set('local_camp', $local);
$evento->__set('descricao', $descricao);
$evento->__set('data_limite', $data_limite);
$evento->__set('tipoCom', $tipoCom);
$evento->__set('tipoSem', $tipoSem);
$evento->__set('preco', $preco);
$evento->__set('preco_menor', $preco_menor);
$evento->__set('preco_abs', $preco_abs);
$evento->__set('doc', $docDef);

try {
    $adEvento->editEvento();
    header("Location: /eventos.php?id=" . $id);
    exit();
} catch (Exception $e) {
    die("Erro ao atualizar evento: " . $e->getMessage());
}
?>