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

        //tratar imagen
        $imagen = $velho->imagen;
        if(isset($_FILES["imagen_nova"]) && $_FILES["imagen_nova"]["error"] === UPLOAD_ERR_OK){
            $imagen = $_FILES["imagen_nova"];
            $ext = pathinfo($imagen["imagen_nova"],PATHINFO_EXTENSION);
            $novoNome = "img_".time().'.'.$ext;
            $caminhoParaSalvar = "../uploads/" . $novoNome;
            //remover o antigo
        }
        $evento->__set('id', $id);
        $evento->__set('nome', $nome);
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
        $adEvento->editEvento();
    }
?>