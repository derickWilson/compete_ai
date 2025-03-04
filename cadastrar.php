<?php
// Verifica se os dados foram enviados via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if($_POST["tipo"] == "A"){
        //cadastrar academia
        //cadastrar academia primeiro
        require_once "classes/atletaService.php";
        include "func/clearWord.php";
        $con = new Conexao();
        $atletas = new Atleta();
        $attServ = new atletaService($con, $atletas);
        //filiar academia
        $attServ->Filiar(cleanWords($_POST["academia"]),
                        cleanWords($_POST["cep"]),
                        cleanWords($_POST["cidade"]),
                        cleanWords($_POST["estado"]));
        //Verifica se o diploma foi enviado corretamente
        if (isset($_FILES['diploma']) && $_FILES['diploma']['error'] === UPLOAD_ERR_OK) {
            $diploma = $_FILES['diploma'];
            $extensao = pathinfo($diploma['name'], PATHINFO_EXTENSION);
            $novoNome = 'diploma_' . time() . '.' . $extensao;
            $caminhoParaSalvar = 'diplomas/' . $novoNome;
            if ($diploma['size'] > 0) {
                if (move_uploaded_file($diploma['tmp_name'], $caminhoParaSalvar)) {
                } else {
                    echo ' Erro ao mover arquivo. Verifique as permiss玫es do diret贸rio.';
                    header("Location: cadastro.php");
                    exit();
                }
            } else {
                echo 'Arquivo vazio ou erro no upload';
            }

        }
        //tratar foto enviada
        if (isset($_FILES['diploma']) && $_FILES['diploma']['error'] === UPLOAD_ERR_OK) {
            $diploma = $_FILES['diploma'];
            $extensao = pathinfo($diploma['name'], PATHINFO_EXTENSION);
            $novoNome = 'diploma_' . time() . '.' . $extensao;
            $caminhoParaSalvar = 'diplomas/' . $novoNome;
            if ($diploma['size'] > 0) {
                if (move_uploaded_file($diploma['tmp_name'], $caminhoParaSalvar)) {
                } else {
                    echo ' Erro ao mover arquivo. Verifique as permiss玫es do diret贸rio.';
                    header("Location: cadastro.php");
                    exit();
                }
            } else {
                echo 'Arquivo vazio ou erro no upload';
            }
        }
        $atletas->__set("nome", cleanWords($_POST["nome"]));
        $atletas->__set("senha", cleanWords($_POST["senha"]));
        $atletas->__set("email", cleanWords($_POST["email"]));
        $atletas->__set("data_nascimento", $_POST["data_nascimento"]);
        $atletas->__set("fone", cleanWords($_POST["fone"]));
        $atletas->__set("academia", cleanWords($_POST["academia"]));
        $atletas->__set("faixa", cleanWords($_POST["faixa"]));
        $atletas->__set("peso", $_POST["peso"]);
        $atletas->__set("diploma",$novoNome);
        if($attServ->emailExists($_POST["email"])){
            header("Location: cadastro.php?erro=1");
            exit();
        }
        try {
            $attServ->addAtleta();
        } catch (Exception $e) {
            echo "Erro ao adicionar atleta: " . $e->getMessage();
        }

    }//fim do cadastro de academia
    if($_POST["tipo"] == "AT"){
        //cadastrar atleta
    
    }
}
?>