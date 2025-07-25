<?php
declare(strict_types=1);
session_start();
ob_start();

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
const MAX_CADASTRO_TENTATIVAS = 3;
const TEMPO_BLOQUEIO_MINUTOS = 60;

// Controle de tentativas de cadastro
if (!isset($_SESSION['cadastro_tentativas'])) {
    $_SESSION['cadastro_tentativas'] = 0;
    $_SESSION['ultimo_cadastro'] = time();
}

if (
    $_SESSION['cadastro_tentativas'] > MAX_CADASTRO_TENTATIVAS &&
    (time() - $_SESSION['ultimo_cadastro'] < TEMPO_BLOQUEIO_MINUTOS * 60)
) {
    die("Muitas tentativas de cadastro. Por favor, tente novamente mais tarde.");
}

/**
 * Processa upload de arquivos com validações de segurança
 */
function processarUpload(array $arquivo, string $pastaDestino, string $prefixoNome): array
{
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
function validarDadosBasicos(array $dados): void
{
    // Valida nome da academia (se for cadastro tipo A)
    if (($dados['tipo'] ?? '') === 'A' && !preg_match('/^[a-zA-Z0-9\s\-]{3,100}$/', $dados['academia'] ?? '')) {
        throw new Exception("Nome da academia inválido.");
    }

    // Valida CEP
    $cep = preg_replace('/[^0-9]/', '', $dados['cep'] ?? '');
    if (!preg_match('/^[0-9]{8}$/', $cep)) {
        throw new Exception("CEP inválido.");
    }

    // Valida CPF
    if (!validarCPF($dados['cpf'] ?? '')) {
        throw new Exception("CPF inválido.");
    }

    // Valida e-mail
    if (!filter_var($dados['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        throw new Exception("E-mail inválido.");
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
        $nomeAcademia = cleanWords($_POST["academia"]);
        if (!$attServ->existAcad($nomeAcademia)) {
            $attServ->Filiar(
                $nomeAcademia,
                preg_replace('/[^0-9]/', '', $_POST["cep"]),
                cleanWords($_POST["cidade"]),
                strtoupper(cleanWords($_POST["estado"]))
            );
        }
        // Obtém ID da academia e cadastra responsável
        $idAcademia = $attServ->getIdAcad($nomeAcademia);
        $attServ->addAcademiaResponsavel($idAcademia["id"]);

        // Redireciona para página de sucesso
        $_SESSION['cadastro_sucesso'] = true;
        header("Location: cadastro_sucesso.php?tipo=A");
        exit();

    } elseif ($_POST["tipo"] == "AT") {
        // CADASTRO DE ATLETA NORMAL

        // Processa upload da foto (obrigatória)
        [$fotoSuccess, $novoNomeFoto] = processarUpload($_FILES['foto'], 'fotos/', 'foto_');
        if (!$fotoSuccess) {
            limparArquivosTemporarios($_FILES);
            throw new Exception($novoNomeFoto);
        }

        // Processa diploma (opcional)
        $novoNomeDiploma = '';
        if (!empty($_FILES['diploma']['name'])) {
            [$diplomaSuccess, $novoNomeDiploma] = processarUpload($_FILES['diploma'], 'diplomas/', 'diploma_');
            if (!$diplomaSuccess) {
                limparArquivosTemporarios($_FILES);
                throw new Exception($novoNomeDiploma);
            }
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
        $atletas->__set("academia", (int) $_POST["academia"]);
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

    $_SESSION['erro_cadastro'] = $e->getMessage();
    $_SESSION['cadastro_tentativas']++;
    $_SESSION['ultimo_cadastro'] = time();

    $paginaErro = ($_POST["tipo"] ?? '') == "A" ? "cadastro_academia.php" : "cadastro_atleta.php";
    header("Location: $paginaErro?erro=5");
    exit();
}
?>