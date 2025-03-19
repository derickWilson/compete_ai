<?php
    require "../func/is_adm.php";
    is_adm();
    // Verifica se o formulário foi submetido
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        require_once "../classes/eventosServices.php";
        include "../func/clearWord.php";

        $id = $_POST['id'];
        $nome = cleanWords($_POST['nome_evento']);
        $local = $_POST['local_camp'];
        $data_camp = cleanWords($_POST['data_camp']);
        $descricao = cleanWords($_POST['desc_Evento']);
        $data_limite = cleanWords($_POST['data_limite']);
        $tipoCom = isset($_POST['tipo_com']) ? 1 : 0;
        $tipoSem = isset($_POST['tipo_sem']) ? 1 : 0;
        $preco = cleanWords($_POST['preco']);
        $preco_menor = cleanWords($_POST['preco_menor']);
        //objeto evento
        $evento = new Evento();
        $conn = new Conexao();
        $evento->__set('id', $id);
        $evento->__set('nome', $nome);
        $evento->__set('data_camp', $data_camp);
        $evento->__set('local_camp', $local);
        $evento->__set('descricao', $descricao);
        $evento->__set('data_limite', $data_limite);
        $evento->__set('tipoCom', $tipoCom);
        $evento->__set('tipoSem', $tipoSem);
        $evento->__set('preco', $preco);
        $evento->__set('preco_menor', $preco_menor);
        $adEvento = new eventosService($conn,$evento);
        $adEvento->editEvento();
    }
?>