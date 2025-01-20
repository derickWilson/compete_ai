    <?php
    require "../func/is_adm.php";
    is_adm();
    // Verifica se o formulário foi submetido
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        require_once "../classes/eventosServices.php";
        include "../func/clearWord.php";
 
            $nome = $_POST['nome_evento'];
            $descricao = $_POST['desc_Evento'];
            $data_limite = $_POST['data_limite'];
            $tipoCom = isset($_POST['tipo_com']) ? 1 : 0;
            $tipoSem = isset($_POST['tipo_sem']) ? 1 : 0;
            $preco = $_POST['preco'];
            $caminhoParaSalvar = null;

            // tratar a imagem fornecida
            if(isset($_FILES['img_evento']) && $_FILES['img_evento']['error'] === UPLOAD_ERR_OK){
                $img_evento = $_FILES['img_evento'];
                $ext = pathinfo($img_evento['name'], PATHINFO_EXTENSION);
                $img_evento['name'] = 'img_'.time().'.'.$ext;
                $caminhoParaSalvar = "../uploads/" . $img_evento['name'];

                if($img_evento['size'] > 0){
                    if(move_uploaded_file($img_evento['tmp_name'] , $caminhoParaSalvar)){
                        echo 'arquivo movido com sucesso';
                    }else{
                        echo 'erro ao mover imagem do evento';
                    }
                }
            }

            //objeto evento
            $evento = new Evento();
            $conn = new Conexao();
            $evento->__set('nome', $nome);
            $evento->__set('img', $caminhoParaSalvar);
            $evento->__set('descricao', $descricao);
            $evento->__set('data_limite', $data_limite);
            $evento->__set('tipoCom', $tipoCom);
            $evento->__set('tipoSem', $tipoSem);
            $evento->__set('img', $img_evento['name']);
            $evento->__set('preco', $preco);
            echo "<br>";
            echo "variaveis criadas";
            echo "<br>";
            $adEvento = new eventosService($conn,$evento);


            $adEvento->addEvento();

            header("Location: ../eventos.php");
    }
    ?>
