<?php
declare(strict_types=1);
session_start();
ob_start();

// Verifica se os dados foram enviados via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once "classes/atletaService.php";
    include "func/clearWord.php";
    
    // Inicializa variáveis
    $novoNome = '';
    $novoNomeFoto = '';
    $ddd = $_POST['ddd'] ?? '+55';
    
    $con = new Conexao();
    $atletas = new Atleta();
    $attServ = new atletaService($con, $atletas);

    // Função auxiliar para processar uploads com mensagens de erro detalhadas
    function processarUpload($arquivo, $pastaDestino, $prefixoNome, $maxSizeMB = 2) {
        if (!isset($arquivo) || $arquivo['error'] !== UPLOAD_ERR_OK) {
            return [false, 'Nenhum arquivo enviado ou erro no upload.'];
        }
        
        // Verificar tamanho do arquivo
        $maxSizeBytes = $maxSizeMB * 1024 * 1024;
        if ($arquivo['size'] > $maxSizeBytes) {
            return [false, "O arquivo excede o tamanho máximo de {$maxSizeMB}MB."];
        }
        
        // Verificar extensão
        $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'pdf'];
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extensao, $extensoesPermitidas)) {
            return [false, "Formato de arquivo não suportado. Use: " . implode(', ', $extensoesPermitidas)];
        }
        
        // Gerar nome único
        $novoNome = $prefixoNome . time() . '.' . $extensao;
        $caminhoCompleto = $pastaDestino . $novoNome;
        
        if (move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
            return [true, $novoNome];
        }
        
        return [false, 'Falha ao mover o arquivo para o servidor.'];
    }

    try {
        if ($_POST["tipo"] == "A") {
            // Cadastrar academia e responsável
            if ($attServ->existAcad(cleanWords($_POST["academia"]))) {
                $attServ->Filiar(
                    cleanWords($_POST["academia"]),
                    cleanWords($_POST["cep"]),
                    cleanWords($_POST["cidade"]),
                    cleanWords($_POST["estado"])
                );
            }

            // Processar uploads com tratamento de erro
            [$fotoSuccess, $novoNomeFoto] = processarUpload($_FILES['foto'] ?? null, 'fotos/', 'foto_');
            [$diplomaSuccess, $novoNome] = processarUpload($_FILES['diploma'] ?? null, 'diplomas/', 'diploma_');

            if (!$fotoSuccess || !$diplomaSuccess) {
                $errorMsg = ($fotoSuccess ? '' : $novoNomeFoto . '<br>') . 
                           ($diplomaSuccess ? '' : $novoNome);
                $_SESSION['erro_cadastro'] = $errorMsg;
                header("Location: cadastro_academia.php?erro=2");
                exit();
            }

            // Configurar dados do atleta (responsável)
            $telefone_completo = cleanWords($ddd) . cleanWords($_POST["fone"]);

            $atletas->__set("nome", cleanWords($_POST["nome"]));
            $atletas->__set("genero", cleanWords($_POST["genero"]));
            $atletas->__set("cpf", cleanWords($_POST["cpf"]));
            $atletas->__set("senha", password_hash(cleanWords($_POST["senha"]), PASSWORD_DEFAULT));
            $atletas->__set("foto", $novoNomeFoto);
            $atletas->__set("email", cleanWords($_POST["email"]));
            $atletas->__set("data_nascimento", $_POST["data_nascimento"]);
            $atletas->__set("fone", $telefone_completo);
            $atletas->__set("faixa", cleanWords($_POST["faixa"]));
            $atletas->__set("peso", cleanWords($_POST["peso"]));
            $atletas->__set("diploma", $novoNome);

            if ($attServ->emailExists($atletas->__get("email"))) {
                header("Location: cadastro_academia.php?erro=1");
                exit();
            }

            $idAcademia = $attServ->getIdAcad(cleanWords($_POST["academia"]));
            $attServ->addAcademiaResponsavel($idAcademia["id"]);
            
            $_SESSION['cadastro_sucesso'] = true;
            header("Location: cadastro_sucesso.php?tipo=A");
            exit();
        }
        elseif ($_POST["tipo"] == "AT") {
            // Cadastrar atleta normal
            [$fotoSuccess, $novoNomeFoto] = processarUpload($_FILES['foto'] ?? null, 'fotos/', 'foto_');
            
            if (!$fotoSuccess) {
                $_SESSION['erro_cadastro'] = $novoNomeFoto;
                header("Location: cadastro_atleta.php?erro=2");
                exit();
            }

            // Diploma é opcional para atletas
            $novoNome = '';
            if (!empty($_FILES['diploma']['name'])) {
                [$diplomaSuccess, $novoNome] = processarUpload($_FILES['diploma'] ?? null, 'diplomas/', 'diploma_');
                if (!$diplomaSuccess) {
                    $_SESSION['erro_cadastro'] = $novoNome;
                    header("Location: cadastro_atleta.php?erro=4");
                    exit();
                }
            }

            $telefone_completo = cleanWords($ddd) . cleanWords($_POST["fone"]);

            $atletas->__set("nome", cleanWords($_POST["nome"]));
            $atletas->__set("cpf", cleanWords($_POST["cpf"]));
            $atletas->__set("genero", cleanWords($_POST["genero"]));
            $atletas->__set("senha", password_hash(cleanWords($_POST["senha"]), PASSWORD_DEFAULT));
            $atletas->__set("email", cleanWords($_POST["email"]));
            $atletas->__set("data_nascimento", $_POST["data_nascimento"]);
            $atletas->__set("foto", $novoNomeFoto);
            $atletas->__set("fone", $telefone_completo);
            $atletas->__set("academia", cleanWords($_POST["academia"]));
            $atletas->__set("faixa", cleanWords($_POST["faixa"]));
            $atletas->__set("peso", $_POST["peso"]);
            $atletas->__set("diploma", $novoNome);

            if ($attServ->emailExists($_POST["email"])) {
                header("Location: cadastro_atleta.php?erro=1");
                exit();
            }

            if (empty($_POST["academia"])) {
                header("Location: cadastro_atleta.php?erro=3");
                exit();
            }

            $attServ->addAtleta();
            
            $_SESSION['cadastro_sucesso'] = true;
            header("Location: cadastro_sucesso.php?tipo=AT");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['erro_cadastro'] = 'Erro no sistema: ' . $e->getMessage();
        header("Location: " . ($_POST["tipo"] == "A" ? "cadastro_academia.php" : "cadastro_atleta.php") . "?erro=5");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>