<?php
session_start();
require "../func/is_adm.php";
is_adm();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once "../classes/eventosServices.php";
    include "../func/clearWord.php";

    // Captura dos dados básicos
    $nome = cleanWords($_POST['nome_evento']);
    $local = cleanWords($_POST['local_camp']);
    $data_evento = cleanWords($_POST['data_camp']);
    $descricao = cleanWords($_POST['desc_Evento']);
    $data_limite = cleanWords($_POST['data_limite']);

    // Captura das modalidades
    $tipoCom = isset($_POST['tipo_com']) ? 1 : 0;
    $tipoSem = isset($_POST['tipo_sem']) ? 1 : 0;
    $normal = isset($_POST['normal']) ? 1 : 0;

    // Validação das modalidades
    if ($normal && ($tipoCom || $tipoSem)) {
        die("Erro: Não é possível marcar 'Evento Normal' junto com outras modalidades");
    }

    if (!$normal && !$tipoCom && !$tipoSem) {
        die("Erro: Selecione pelo menos um tipo de modalidade ou marque como 'Evento Normal'");
    }

    // Captura dos preços COM kimono
    $preco = isset($_POST['preco']) ? (float) cleanWords($_POST['preco']) : 0;
    $preco_menor = isset($_POST['preco_menor']) ? (float) cleanWords($_POST['preco_menor']) : 0;
    $preco_abs = isset($_POST['preco_abs']) ? (float) cleanWords($_POST['preco_abs']) : 0;

    // Captura dos preços SEM kimono
    $preco_sem = isset($_POST['preco_sem']) ? (float) cleanWords($_POST['preco_sem']) : 0;
    $preco_sem_menor = isset($_POST['preco_sem_menor']) ? (float) cleanWords($_POST['preco_sem_menor']) : 0;
    $preco_sem_abs = isset($_POST['preco_sem_abs']) ? (float) cleanWords($_POST['preco_sem_abs']) : 0;
    // Validação para eventos normais
    if ($normal && $normal_preco <= 0) {
        die("Erro: Eventos normais devem ter um preço definido");
    }
    
    if (!$normal) {
        $normal_preco = 0;
    }

    // Processamento do documento (ementa)
    $docPath = null;
    $nomeArquivo = null;

    if (isset($_FILES['doc']) && $_FILES['doc']['error'] === UPLOAD_ERR_OK) {
        $doc = $_FILES['doc'];
        $ext = pathinfo($doc['name'], PATHINFO_EXTENSION);

        if (strtolower($ext) !== 'pdf' || mime_content_type($doc['tmp_name']) !== 'application/pdf') {
            die("Arquivo inválido. Apenas PDFs são permitidos.");
        }

        $nomeArquivo = 'doc_' . time() . '.' . $ext;
        $docPath = "../docs/" . $nomeArquivo;

        if (!move_uploaded_file($doc['tmp_name'], $docPath)) {
            die("Erro ao mover a ementa.");
        }
    }

    // Processamento da imagem do evento
    $imgNome = null;
    if (isset($_FILES['img_evento']) && $_FILES['img_evento']['error'] === UPLOAD_ERR_OK) {
        $img_evento = $_FILES['img_evento'];
        $ext = pathinfo($img_evento['name'], PATHINFO_EXTENSION);
        $imgNome = 'img_' . time() . '.' . $ext;
        $caminhoParaSalvar = "../uploads/" . $imgNome;

        if (!move_uploaded_file($img_evento['tmp_name'], $caminhoParaSalvar)) {
            die('Erro ao mover imagem do evento');
        }
    }

    // Criação do objeto evento
    $evento = new Evento();
    $conn = new Conexao();

    $evento->__set('nome', $nome);
    $evento->__set('data_evento', $data_evento);
    $evento->__set('local_camp', $local);
    $evento->__set('img', $imgNome);
    $evento->__set('descricao', $descricao);
    $evento->__set('data_limite', $data_limite);
    $evento->__set('tipoCom', $tipoCom);
    $evento->__set('tipoSem', $tipoSem);
    $evento->__set('normal', $normal);
    $evento->__set('normal_preco', $normal_preco);
    $evento->__set('preco', $preco);
    $evento->__set('preco_menor', $preco_menor);
    $evento->__set('preco_abs', $preco_abs);
    $evento->__set('doc', $nomeArquivo);
    $evento->__set('preco_sem', $preco_sem);
    $evento->__set('preco_sem_menor', $preco_sem_menor);
    $evento->__set('preco_sem_abs', $preco_sem_abs);

    $adEvento = new eventosService($conn, $evento);
    $adEvento->addEvento();
    exit();
}
?>