<?php
// Verifica se os dados foram enviados via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once "classes/atletaService.php";
    include "func/clearWord.php";
    
    // Inicializa variáveis para evitar undefined
    $novoNome = '';
    $novoNomeFoto = '';
    $ddd = $_POST['ddd'] ?? ''; // Valor padrão se não existir
    
    $con = new Conexao();
    $atletas = new Atleta();
    $attServ = new atletaService($con, $atletas);

    // Função auxiliar para processar uploads
    function processarUpload($arquivo, $pastaDestino, $prefixoNome) {
        if (isset($arquivo) && $arquivo['error'] === UPLOAD_ERR_OK) {
            $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
            $novoNome = $prefixoNome . time() . '.' . $extensao;
            $caminhoCompleto = $pastaDestino . $novoNome;
            
            if ($arquivo['size'] > 0 && move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
                return $novoNome;
            }
        }
        return false;
    }

    if($_POST["tipo"] == "A") {
        // Cadastrar academia e responsável
        try {
            if($attServ->existAcad(cleanWords($_POST["academia"]))) {
                $attServ->Filiar(
                    cleanWords($_POST["academia"]),
                    cleanWords($_POST["cep"]),
                    cleanWords($_POST["cidade"]),
                    cleanWords($_POST["estado"])
                );
            }
        } catch (Exception $e) {
            die("Erro ao filiar academia: " . $e->getMessage());
        }

        // Processar uploads
        $novoNome = processarUpload($_FILES['diploma'] ?? null, 'diplomas/', 'diploma_');
        $novoNomeFoto = processarUpload($_FILES['foto'] ?? null, 'fotos/', 'foto_');

        if (!$novoNome || !$novoNomeFoto) {
            die("Erro no upload de arquivos. Verifique se selecionou ambos os arquivos.");
        }

        // Configurar dados do atleta (responsável)
        $telefone_completo = cleanWords($ddd) . cleanWords($_POST["fone"]);

        $atletas->__set("nome", cleanWords($_POST["nome"]));
        $atletas->__set("genero", cleanWords($_POST["genero"]));
        $atletas->__set("cpf", cleanWords($_POST["cpf"]));
        $atletas->__set("senha", cleanWords($_POST["senha"]));
        $atletas->__set("foto", $novoNomeFoto);
        $atletas->__set("email", cleanWords($_POST["email"]));
        $atletas->__set("data_nascimento", $_POST["data_nascimento"]);
        $atletas->__set("fone", $telefone_completo);
        $atletas->__set("faixa", cleanWords($_POST["faixa"]));
        $atletas->__set("peso", cleanWords($_POST["peso"]));
        $atletas->__set("diploma", $novoNome);

        if($attServ->emailExists($atletas->__get("email"))) {
            header("Location: cadastro.php?erro=1");
            exit();
        }

        try {
            $idAcademia = $attServ->getIdAcad(cleanWords($_POST["academia"]));
            $attServ->addAcademiaResponsavel($idAcademia["id"]);
        } catch (Exception $e) {
            die("Erro ao cadastrar responsável: " . $e->getMessage());
        }
    }
    elseif($_POST["tipo"] == "AT") {
        // Cadastrar atleta normal
        $novoNomeFoto = processarUpload($_FILES['foto'] ?? null, 'fotos/', 'foto_');
        $novoNome = processarUpload($_FILES['diploma'] ?? null, 'diplomas/', 'diploma_');

        if (!$novoNomeFoto) {
            die("Erro no upload da foto. É obrigatório enviar uma foto.");
        }

        $telefone_completo = cleanWords($ddd) . cleanWords($_POST["fone"]);

        $atletas->__set("nome", cleanWords($_POST["nome"]));
        $atletas->__set("cpf", cleanWords($_POST["cpf"]));
        $atletas->__set("genero", cleanWords($_POST["genero"]));
        $atletas->__set("senha", cleanWords($_POST["senha"]));
        $atletas->__set("email", cleanWords($_POST["email"]));
        $atletas->__set("data_nascimento", $_POST["data_nascimento"]);
        $atletas->__set("foto", $novoNomeFoto);
        $atletas->__set("fone", $telefone_completo);
        $atletas->__set("academia", cleanWords($_POST["academia"]));
        $atletas->__set("faixa", cleanWords($_POST["faixa"]));
        $atletas->__set("peso", $_POST["peso"]);
        $atletas->__set("diploma", $novoNome); // Pode ser vazio se não foi enviado

        if($attServ->emailExists($_POST["email"])) {
            header("Location: cadastro.php?erro=1");
            exit();
        }

        try {
            $attServ->addAtleta();
        } catch (Exception $e) {
            die("Erro ao adicionar atleta: " . $e->getMessage());
        }
    }
}
?>