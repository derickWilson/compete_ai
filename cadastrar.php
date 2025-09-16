<?php
declare(strict_types=1);
ob_start();

session_start();
// Verificar se o campo 'tipo' existe no POST
if (!isset($_POST['tipo'])) {
    header("Location: index.php");
    exit();
}
error_log("Iniciando processamento de cadastro. Tipo: " . ($_POST['tipo'] ?? 'N/A'));
// Verifica se os dados foram enviados via POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '/classes/atletaService.php';  // Caminho absoluto
include "func/clearWord.php";
require_once "func/validacoes.php";

// Configurações
const MAX_UPLOAD_SIZE_MB = 12;

/**
 * Processa upload de arquivos com validações de segurança
 */
function processarUpload(array $arquivo, string $pastaDestino, string $prefixoNome): array
{
    // Verifica se houve erro no upload
    if (!isset($arquivo) || $arquivo['error'] !== UPLOAD_ERR_OK) {
        switch ($arquivo['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return [false, 'O arquivo é muito grande. Tamanho máximo: ' . MAX_UPLOAD_SIZE_MB . 'MB.'];
            case UPLOAD_ERR_PARTIAL:
                return [false, 'O upload do arquivo foi interrompido. Tente novamente.'];
            case UPLOAD_ERR_NO_FILE:
                return [false, 'Nenhum arquivo foi selecionado.'];
            default:
                return [false, 'Erro no upload do arquivo. Tente novamente.'];
        }
    }
    // Verifica se houve erro no upload
    if (!isset($arquivo) || $arquivo['error'] !== UPLOAD_ERR_OK) {
        return [false, 'Erro no upload do arquivo.'];
    }

    // Verifica tamanho do arquivo
    $maxSizeBytes = MAX_UPLOAD_SIZE_MB * 1024 * 1024;
    if ($arquivo['size'] > $maxSizeBytes) {
        return [false, "O arquivo excede o tamanho máximo de " . MAX_UPLOAD_SIZE_MB . "MB."];
    }

    // Verifica tipo MIME real
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($arquivo['tmp_name']);
    $mimesPermitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];

    if (!array_key_exists($mime, $mimesPermitidos)) {
        return [false, "Tipo de arquivo não permitido."];
    }

    // Gera nome único e seguro
    $extensao = $mimesPermitidos[$mime];
    $novoNome = $prefixoNome . bin2hex(random_bytes(8)) . '.' . $extensao;
    $caminhoCompleto = $pastaDestino . $novoNome;

    // Move o arquivo para o destino final
    if (!move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
        return [false, 'Falha ao salvar o arquivo.'];
    }

    return [true, $novoNome];
}

/**
 * Remove arquivos temporários em caso de falha
 */
function limparArquivosTemporarios(array $arquivos): void
{
    if (!empty($arquivos['foto']['tmp_name']) && file_exists($arquivos['foto']['tmp_name'])) {
        @unlink($arquivos['foto']['tmp_name']);
    }
    if (!empty($arquivos['diploma']['tmp_name']) && file_exists($arquivos['diploma']['tmp_name'])) {
        @unlink($arquivos['diploma']['tmp_name']);
    }
}

/**
 * Valida dados básicos do formulário
 */
/**
 * Valida dados básicos do formulário
 */
function validarDadosBasicos(array $dados): void
{
    // Valida CPF (para ambos os tipos)
    if (!validarCPF($dados['cpf'] ?? '')) {
        throw new Exception("CPF inválido. Por favor, verifique o número digitado.");
    }

    // Valida academia e CEP apenas se for cadastro tipo A
    if (($dados['tipo'] ?? '') === 'A') {
        if (empty($dados['academia'] ?? '')) {
            throw new Exception("O nome da academia é obrigatório.");
        }

        if (!preg_match('/^[\p{L}0-9\s\-\'\(\)\.\,]{3,100}$/u', $dados['academia'] ?? '')) {
            throw new Exception("Nome da academia inválido. Use apenas letras, números, espaços, hífens, apóstrofos e parênteses.");
        }

        // Valida CEP (apenas para cadastro tipo A)
        $cep = preg_replace('/[^0-9]/', '', $dados['cep'] ?? '');
        if (!preg_match('/^[0-9]{8}$/', $cep)) {
            throw new Exception("CEP inválido. Digite um CEP válido com 8 dígitos.");
        }
    }
}

try {
    // Inicializa serviços
    $con = new Conexao();
    $atletas = new Atleta();
    $attServ = new atletaService($con, $atletas);

    // Validação básica dos dados
    validarDadosBasicos($_POST);

    // Processamento específico para cada tipo de cadastro
    if ($_POST["tipo"] == "A") {
        // CADASTRO DE ACADEMIA E RESPONSÁVEL

        // Processa uploads
        [$fotoSuccess, $novoNomeFoto] = processarUpload($_FILES['foto'], 'fotos/', 'foto_');
        [$diplomaSuccess, $novoNomeDiploma] = processarUpload($_FILES['diploma'], 'diplomas/', 'diploma_');

        if (!$fotoSuccess || !$diplomaSuccess) {
            limparArquivosTemporarios($_FILES);
            throw new Exception(($fotoSuccess ? '' : $novoNomeFoto) . ($diplomaSuccess ? '' : $novoNomeDiploma));
        }

        // Prepara dados do responsável
        $telefone_completo = '+55' . preg_replace('/[^0-9]/', '', $_POST["fone"]);

        $atletas->__set("nome", cleanWords($_POST["nome"]));
        $atletas->__set("genero", cleanWords($_POST["genero"]));
        $atletas->__set("cpf", cleanWords($_POST["cpf"]));
        $atletas->__set("senha", password_hash(cleanWords($_POST["senha"]), PASSWORD_DEFAULT));
        $atletas->__set("foto", $novoNomeFoto);
        $atletas->__set("email", strtolower(cleanWords($_POST["email"])));
        $atletas->__set("data_nascimento", $_POST["data_nascimento"]);
        $atletas->__set("fone", $telefone_completo);
        $atletas->__set("faixa", cleanWords($_POST["faixa"]));
        $atletas->__set("peso", (float) $_POST["peso"]);
        $atletas->__set("diploma", $novoNomeDiploma);

        // Verifica se e-mail já existe
        if ($attServ->emailExists($atletas->__get("email"))) {
            throw new Exception("Este e-mail já está cadastrado.");
        }

        // Cadastra academia se não existir
        //$nomeAcademia = cleanWords($_POST["academia"]);

        // Cadastra academia se não existir
        $nomeAcademia = $_POST["academia"]; // ← SEM cleanWords() para manter espaços

        if (!$attServ->existAcad($nomeAcademia)) {
            $idInserido = $attServ->Filiar(
                $nomeAcademia,
                $_POST["cep"],
                $_POST["cidade"], // ← SEM cleanWords()
                $_POST["estado"]  // ← SEM cleanWords()
            );

            // Já temos o ID, não precisa buscar
            $idAcademia = ["id" => $idInserido];
        } else {
            // Se já existe, busca o ID
            $idAcademia = $attServ->getIdAcad($nomeAcademia);
        }

        // Verifica se conseguiu o ID
        if (!$idAcademia || !isset($idAcademia["id"])) {
            throw new Exception("Falha ao obter ID da academia. Nome: " . $nomeAcademia);
        }

        //$attServ->addAcademiaResponsavel($idAcademia["id"]);
        // Obtém ID da academia e cadastra responsável
        // No bloco onde você obtém o ID da academia:
//        $idAcademia = $attServ->getIdAcad($nomeAcademia);

        //if (!$idAcademia || !isset($idAcademia["id"])) {
        //    // Tenta criar a academia novamente se não foi encontrada
        //    if (!$attServ->existAcad($nomeAcademia)) {
        //        $attServ->Filiar(
        //            $nomeAcademia,
        //            preg_replace('/[^0-9]/', '', $_POST["cep"]),
        //            cleanWords($_POST["cidade"]),
        //            strtoupper(cleanWords($_POST["estado"]))
        //        );
        //        // Tenta buscar novamente
        //        $idAcademia = $attServ->getIdAcad($nomeAcademia);
        //    }
//
        //    if (!$idAcademia || !isset($idAcademia["id"])) {
        //        throw new Exception("Falha ao obter ID da academia. Nome: " . $nomeAcademia);
        //    }
        //}

        $attServ->addAcademiaResponsavel($idAcademia["id"]);
        // Redireciona para página de sucesso
        $_SESSION['cadastro_sucesso'] = true;
        header("Location: cadastro_sucesso.php?tipo=A");
        exit();        // Redireciona para página de sucesso
    } elseif ($_POST["tipo"] == "AT") {
        // CADASTRO DE ATLETA NORMAL

        // Processa upload da foto (obrigatória)
        [$fotoSuccess, $novoNomeFoto] = processarUpload($_FILES['foto'], 'fotos/', 'foto_');
        if (!$fotoSuccess) {
            limparArquivosTemporarios($_FILES);
            throw new Exception($novoNomeFoto);
        }
        $novoNomeDiploma = '';
        if (!empty($_FILES['diploma']['name'])) {
            [$diplomaSuccess, $novoNomeDiploma] = processarUpload($_FILES['diploma'], 'diplomas/', 'diploma_');
            if (!$diplomaSuccess) {
                limparArquivosTemporarios($_FILES);
                throw new Exception($novoNomeDiploma);
            }
        }

        // Verifica se academia foi selecionada
        if (empty($_POST["academia"]) || $_POST["academia"] == "") {
            throw new Exception("Por favor, selecione uma academia válida.");
        }

        // Prepara dados do atleta
        $telefone_completo = '+55' . preg_replace('/[^0-9]/', '', $_POST["fone"]);

        $atletas->__set("nome", cleanWords($_POST["nome"]));
        $atletas->__set("cpf", cleanWords($_POST["cpf"]));
        $atletas->__set("genero", cleanWords($_POST["genero"]));
        $atletas->__set("senha", password_hash(cleanWords($_POST["senha"]), PASSWORD_DEFAULT));
        $atletas->__set("email", strtolower(cleanWords($_POST["email"])));
        $atletas->__set("data_nascimento", $_POST["data_nascimento"]);
        $atletas->__set("foto", $novoNomeFoto);
        $atletas->__set("fone", $telefone_completo);

        $academiaId = !empty($_POST["academia"]) ? (int) $_POST["academia"] : null;
        $atletas->__set("academia", $academiaId);
        $atletas->__set("faixa", cleanWords($_POST["faixa"]));

        $atletas->__set("peso", (float) $_POST["peso"]);
        $atletas->__set("diploma", $novoNomeDiploma);

        // Verifica se e-mail já existe
        if ($attServ->emailExists($atletas->__get("email"))) {
            throw new Exception("Este e-mail já está cadastrado.");
        }

        // Verifica se academia foi selecionada
        if (empty($_POST["academia"])) {
            throw new Exception("Por favor, selecione uma academia válida.");
        }

        // Cadastra atleta
        $attServ->addAtleta();

        // Redireciona para página de sucesso
        $_SESSION['cadastro_sucesso'] = true;
        header("Location: cadastro_sucesso.php?tipo=AT");
        exit();
    } else {
        throw new Exception("Tipo de cadastro inválido.");
    }

} catch (Exception $e) {
    // Limpeza e tratamento de erros
    limparArquivosTemporarios($_FILES);
    error_log("Erro no cadastro: " . $e->getMessage());
    error_log("Dados recebidos: " . print_r($_POST, true));
    error_log("Dados de arquivos: " . print_r($_FILES, true));
    // Mensagens mais específicas
    $mensagemErro = $e->getMessage();

    // Tratamento específico para erros de banco de dados
    if (strpos($mensagemErro, 'Duplicate entry') !== false) {
        $mensagemErro = "Este CPF ou e-mail já está cadastrado em nosso sistema.";
    } elseif (strpos($mensagemErro, 'foreign key constraint') !== false) {
        $mensagemErro = "Erro ao vincular academia. Por favor, tente novamente.";
    }

    $_SESSION['erro_cadastro'] = $mensagemErro;

    // Determina código de erro específico
    $erroCode = 5; // Erro genérico

    if (strpos($e->getMessage(), 'selecione uma academia válida') !== false) {
        $erroCode = 3;
    } elseif (strpos($e->getMessage(), 'CPF inválido') !== false) {
        $erroCode = 6;
    } elseif (strpos($e->getMessage(), 'e-mail já está cadastrado') !== false) {
        $erroCode = 1;
    } elseif (strpos($e->getMessage(), 'Tipo de arquivo não permitido') !== false) {
        $erroCode = 2;
    }

    $paginaErro = ($_POST["tipo"] ?? '') == "A" ? "cadastro_academia.php" : "cadastro_atleta.php";
    header("Location: $paginaErro?erro=$erroCode");
    exit();
}
?>