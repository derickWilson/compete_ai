<?php
    require "../func/is_adm.php";
    is_adm();
    //echo "<pre>";
    //print_r($_POST);
    //echo "</pre>";
    // Verifica se o formulário foi submetido
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        require_once "../classes/eventosServices.php";
        include "../func/clearWord.php";
        //objeto evento
        $evento = new Evento();
        $conn = new Conexao();
        $adEvento = new eventosService($conn,$evento);

        $id = $_POST['id'];
        //pegar o evento
        $velho = $adEvento->getById($id);

        $nome = cleanWords($_POST['nome_evento']);
        $local = cleanWords($_POST['local_camp']);
        $data_camp = cleanWords($_POST['data_camp']);
        $descricao = cleanWords($_POST['desc_Evento']);
        $data_limite = cleanWords($_POST['data_limite']);
        $tipoCom = isset($_POST['tipo_com']) ? 1 : 0;
        $tipoSem = isset($_POST['tipo_sem']) ? 1 : 0;
        //$tipoAbcom = isset($_POST['tipo_com']) ? 1 : 0;
        //$tipoAbSem = isset($_POST['tipo_sem']) ? 1 : 0;
        $preco = cleanWords($_POST['preco']);
        $preco_menor = cleanWords($_POST['preco_menor']); 
        $preco_abs = cleanWords($_POST['preco_abs']); 

        //tratar imagen
        $imagenDefinitiva = $velho->imagen;
        if(isset($_FILES["imagen_nova"]) && $_FILES["imagen_nova"]["error"] === UPLOAD_ERR_OK){
            $imagen = $_FILES["imagen_nova"];
            $ext = pathinfo($imagen["imagen_nova"],PATHINFO_EXTENSION);
            $novoNome = "img_".time().'.'.$ext;
            $caminhoParaSalvar = "../uploads/" . $novoNome;
            //remover o antigo
            if(!empty("../uploads/".$imagenDefinitiva) && file_exists("../uploads/".$imagenDefinitiva)){
                unlink("../uploads/".$imagenDefinitiva);
            }
            if($imagen["size"] > 0){
                if(move_uploaded_file($imagen["tmp_name"], $caminhoParaSalvar)){
                    $imagenDefinitiva = $novoNome;
                }else{
                    echo "erro ao mover arquivo";
                }
            }else{
                echo "erroc no arquivo";
            }
        }

        //tratar documento de ementa
        $docDef = $velho->doc;
        // tratar o novo documento (ementa)
        if (isset($_FILES['nDoc']) && $_FILES['nDoc']['error'] === UPLOAD_ERR_OK) {
            $doc = $_FILES['nDoc'];
            $ext = pathinfo($doc['name'], PATHINFO_EXTENSION);
        
            // validação extra (aceita apenas .pdf)
            if (strtolower($ext) !== 'pdf' || mime_content_type($doc['tmp_name']) !== 'application/pdf') {
                echo "Arquivo inválido. Apenas PDF é permitido.";
                exit();
            }
        
            $novoNomeDoc = "doc_" . time() . '.' . $ext;
            $caminhoDoc = "../docs/" . $novoNomeDoc;
        
            // remove documento antigo se existir
            if (!empty("../docs/" . $docDef) && file_exists("../docs/" . $docDef)) {
                unlink("../docs/" . $docDef);
            }
        
            // move novo documento
            if ($doc['size'] > 0) {
                if (move_uploaded_file($doc['tmp_name'], $caminhoDoc)) {
                    $docDef = $novoNomeDoc;
                } else {
                    echo "Erro ao mover o novo documento.";
                }
            }
        }

        $evento->__set('id', $id);
        $evento->__set('nome', $nome);
        $evento->__set('img', $imagenDefinitiva);
        $evento->__set('data_camp', $data_camp);
        $evento->__set('local_camp', $local);
        $evento->__set('descricao', $descricao);
        $evento->__set('data_limite', $data_limite);
        $evento->__set('tipoCom', $tipoCom);
        $evento->__set('tipoSem', $tipoSem);
        //$evento->__set('tipoAbCom', $tipoAbCom);
        //$evento->__set('tipoAbSem', $tipoAbSem);
        $evento->__set('preco', $preco);
        $evento->__set('preco_menor', $preco_menor);
        $evento->__set('preco_abs', $preco_abs);
        $evento->__set('doc', $docDef);
        $adEvento->editEvento();
    }
?>