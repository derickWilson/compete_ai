<?php
ob_start(); // INICIA O BUFFER DE SAÍDA
session_start();
try {
    require_once __DIR__ . "/../func/database.php";
    require_once __DIR__ . "/../classes/atletaClass.php";
    require_once __DIR__ . "/../func/calcularIdade.php";
    require_once __DIR__ . "/../func/security.php";
} catch (\Throwable $th) {
    print ("[" . $th->getMessage() . "]");
}
class atletaService
{
    private $conn;
    private $atleta;
    // Adicione na classe AtletaService
    public function __construct(Conexao $conn, Atleta $atleta)
    {
        $this->conn = $conn->conectar();
        $this->atleta = $atleta;
    }

    //adicionar academia e responsavel
    /**
     * Cadastra uma nova academia e seu responsável de forma transacional
     * 
     * Esta função realiza o cadastro completo de uma academia filiada e seu responsável
     * em uma transação atômica, garantindo consistência dos dados. Inclui tratamento
     * de erros robusto e limpeza de recursos em caso de falha.
     * 
     * @param object $acad Objeto contendo dados da academia (nome, cep, estado, cidade)
     * @return void
     * @throws Exception Em caso de erro no processamento do cadastro
     * 
     * @example
     * try {
     *     $controller->addAcademiaResponsavel($academiaData);
     * } catch (Exception $e) {
     *     echo "Erro no cadastro: " . $e->getMessage();
     * }
     */
    public function addAcademiaResponsavel($acad)
    {
        try {
            // Inicia transação atômica para garantir consistência
            $this->conn->beginTransaction();

            // Prepara query para inserção do responsável
            $query = "INSERT INTO 
            atleta (nome, cpf, senha, genero, foto, email, data_nascimento, fone, endereco_completo, faixa, peso, diploma, validado, responsavel, data_filiacao)
          VALUES 
          (:nome, :cpf, :senha, :genero, :foto, :email, :data_nascimento, :fone, :endereco_completo, :faixa, :peso, :diploma, 0, 1, :data_filiacao)";
            $stmt = $this->conn->prepare($query);

            // Gera hash seguro da senha usando algoritmo BCrypt
            $senhaCriptografada = password_hash($this->atleta->__get("senha"), PASSWORD_BCRYPT);

            // Bind dos parâmetros com sanitização apropriada
            $stmt->bindValue(":nome", ucwords($this->atleta->__get("nome")));
            $stmt->bindValue(":cpf", $this->atleta->__get("cpf"));
            $stmt->bindValue(":genero", $this->atleta->__get("genero"));
            $stmt->bindValue(":foto", $this->atleta->__get("foto"));
            $stmt->bindValue(":senha", $senhaCriptografada);
            $stmt->bindValue(":email", $this->atleta->__get("email"));
            $stmt->bindValue(":data_nascimento", $this->atleta->__get("data_nascimento"));
            $stmt->bindValue(":fone", $this->atleta->__get("fone"));
            $stmt->bindValue(":endereco_completo", $this->atleta->__get("endereco_completo"));
            $stmt->bindValue(":faixa", $this->atleta->__get("faixa"));
            $stmt->bindValue(":peso", $this->atleta->__get("peso"));
            $stmt->bindValue(":diploma", $this->atleta->__get("diploma"));
            $stmt->bindValue(":data_filiacao", date('Y-m-d'));

            // Executa a inserção do responsável
            $stmt->execute();

            // Obtém o ID do responsável recém-criado
            $idResponsavel = $this->getResponsavel($this->atleta->__get("email"), $this->atleta->__get("nome"));

            if (!$idResponsavel) {
                throw new Exception("Falha ao obter ID do responsável");
            }

            // Associa a academia ao responsável
            $this->atribuirAcademia($acad, $idResponsavel["id"]);

            // Confirma a transação se tudo ocorreu com sucesso
            $this->conn->commit();

            // Redireciona para página de sucesso
            header("Location: index.php?message=1");
            exit();

        } catch (Exception $e) {
            // Reverte a transação em caso de erro
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            // Limpeza de arquivos enviados em caso de falha
            $this->limparCadastroFalho(
                __DIR__ . '/../fotos/' . $this->atleta->__get("foto"),
                __DIR__ . '/../diplomas/' . $this->atleta->__get("diploma")
            );

            // Tratamento específico para erro de duplicidade
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                throw new Exception("Este CPF ou e-mail já está cadastrado em nosso sistema.");
            }

            // Propaga o erro com mensagem amigável
            throw new Exception("Erro ao processar cadastro: " . $e->getMessage());
        }
    }


    /**
     * Remove arquivos enviados em caso de falha no processo de cadastro
     * 
     * Esta função é responsável pela limpeza de recursos (arquivos) que foram
     * enviados durante um processo de cadastro que falhou. Garante que arquivos
     * temporários não persistam no sistema quando o cadastro não é concluído
     * com sucesso, mantendo a integridade e evitando lixo no sistema de arquivos.
     * 
     * @param string|null $fotoPath Caminho completo do arquivo de foto a ser removido
     * @param string|null $diplomaPath Caminho completo do arquivo de diploma a ser removido
     * @return void
     * 
     * @example
     * // Uso típico após falha em cadastro
     * $this->limparCadastroFalho(
     *     __DIR__ . '/../fotos/foto_temporaria.jpg',
     *     __DIR__ . '/../diplomas/diploma_temporario.pdf'
     * );
     * 
     * @example
     * // Uso para limpar apenas a foto
     * $this->limparCadastroFalho(__DIR__ . '/../fotos/foto_temporaria.jpg');
     * 
     * @note A função verifica a existência do arquivo antes de tentar removê-lo
     * @note Erros durante a remoção são capturados e registrados em log, não interrompendo o fluxo
     * @note Esta função é tipicamente chamada a partir do bloco catch de métodos de cadastro
     * 
     * @since Versão 1.0
     * @lastmodified 2024-03-15
     */
    public function limparCadastroFalho($fotoPath = null, $diplomaPath = null)
    {

        try {
            // Remove arquivo de foto se fornecido e existir
            if ($fotoPath && file_exists($fotoPath)) {
                unlink($fotoPath);
            }

            // Remove arquivo de diploma se fornecido e existir
            if ($diplomaPath && file_exists($diplomaPath)) {
                unlink($diplomaPath);
            }
        } catch (Exception $e) {
            // Registra erro no log sem interromper o fluxo da aplicação
            error_log("Erro ao limpar arquivos de cadastro falho: " . $e->getMessage());
        }
    }

    /**
     * Cadastra um novo atleta no sistema
     * 
     * Esta função realiza a inserção de um novo atleta na base de dados, incluindo
     * tratamento de dados pessoais, hash de senha e validações básicas. O atleta
     * é cadastrado com status "não validado" (aguardando aprovação) e como não responsável.
     * 
     * @return void
     * @throws Exception Em caso de erro no processamento do cadastro, especialmente
     *                   para entradas duplicadas (CPF ou email já existentes)
     * 
     * @example
     * try {
     *     $controller->addAtleta();
     * } catch (Exception $e) {
     *     echo "Erro no cadastro: " . $e->getMessage();
     *     // Realizar limpeza de arquivos enviados se necessário
     * }
     * 
     * @security
     * - Utiliza password_hash com PASSWORD_BCRYPT para hash seguro de senhas
     * - Aplica ucwords() para padronização de nome
     * - Aplica strtolower() para normalização de email
     * - Validação contra entradas duplicadas na base de dados
     * 
     * @validation
     * - O atleta é criado com validado=0 (aguardando validação administrativa)
     * - O atleta é criado com responsavel=0 (não é um responsável de academia)
     * 
     * @todo Implementar sistema de confirmação por email
     * 
     * @since Versão 1.0
     * @lastmodified 2024-03-15
     */
    public function addAtleta()
    {
        // Prepara query para inserção do atleta com todos os campos necessários
        $query = "INSERT INTO atleta (nome, cpf, senha, genero, foto, email, academia, data_nascimento, fone, endereco_completo, faixa, peso, diploma, validado, responsavel, data_filiacao)
          VALUES (:nome, :cpf, :senha, :genero, :foto, :email, :academia, :data_nascimento, :fone, :endereco_completo, :faixa, :peso, :diploma, :validado, :responsavel, :data_filiacao)";
        $stmt = $this->conn->prepare($query);

        // Gera hash seguro da senha usando algoritmo BCrypt
        $senhaCriptografada = password_hash($this->atleta->__get("senha"), PASSWORD_BCRYPT);

        // Bind dos parâmetros com sanitização e formatação apropriada
        $stmt->bindValue(":nome", ucwords($this->atleta->__get("nome")));
        $stmt->bindValue(":cpf", $this->atleta->__get("cpf"));
        $stmt->bindValue(":genero", $this->atleta->__get("genero"));
        $stmt->bindValue(":foto", $this->atleta->__get("foto"));
        $stmt->bindValue(":academia", $this->atleta->__get("academia"));
        $stmt->bindValue(":senha", $senhaCriptografada);
        $stmt->bindValue(":email", $this->atleta->__get("email"));
        $stmt->bindValue(":data_nascimento", $this->atleta->__get("data_nascimento"));
        $stmt->bindValue(":fone", $this->atleta->__get("fone"));
        $stmt->bindValue(":endereco_completo", $this->atleta->__get("endereco_completo"));
        $stmt->bindValue(":faixa", $this->atleta->__get("faixa"));
        $stmt->bindValue(":peso", $this->atleta->__get("peso"));
        $stmt->bindValue(":diploma", $this->atleta->__get("diploma"));
        $stmt->bindValue(":validado", 0); // Atleta aguardando validação
        $stmt->bindValue(":responsavel", 0); // Não é responsável por academia
        $stmt->bindValue(":data_filiacao", date('Y-m-d'));

        // Executar a query dentro de bloco try-catch para tratamento de erros
        try {
            $stmt->execute();
            // Redireciona para página inicial com mensagem de sucesso
            // message=1: "Aguarde sua conta ser validada"
            header("Location: index.php?message=1");
            exit();
        } catch (Exception $e) {
            // Tratamento específico para erro de duplicidade (CPF ou email já existente)
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                throw new Exception("Este CPF ou e-mail já está cadastrado em nosso sistema.");
            }

            // Propaga outros erros com mensagem genérica
            throw new Exception("Erro ao processar cadastro: " . $e->getMessage());
        }
    }
    /**
     * Recupera uma lista completa de todos os atletas do sistema com informações relevantes
     * 
     * Esta função consulta a base de dados para obter todos os atletas cadastrados,
     * retornando informações essenciais para gestão e visualização. Os resultados
     * são ordenados por academia e nome do atleta para melhor organização.
     * 
     * @return array|false Retorna um array de objetos contendo dados dos atletas ou 
     *                     false em caso de erro na execução da query
     * 
     * @example
     * // Uso básico para obter lista de atletas
     * $atletas = $controller->listAll();
     * if ($atletas) {
     *     foreach ($atletas as $atleta) {
     *         echo "Atleta: {$atleta->nome} - Academia: {$atleta->academia}";
     *     }
     * }
     * 
     * @example
     * // Uso em contexto de administração
     * $atletas = $controller->listAll();
     * if (!empty($atletas)) {
     *     // Processar lista para exibição em tabela administrativa
     * }
     * 
     * @data
     * Campos retornados para cada atleta:
     * - id: Identificador único do atleta
     * - nome: Nome completo do atleta
     * - faixa: Faixa de graduação no Jiu-Jitsu
     * - academia: Nome da academia filiada
     * - validado: Status de validação do cadastro (0 = pendente, 1 = validado)
     * 
     * @note A função utiliza PDO::FETCH_OBJ para retornar objetos em vez de arrays
     * @note A ordenação é primeiro por nome da academia, depois por nome do atleta
     * @note Em caso de erro na query, a função exibe a mensagem mas não interrompe a execução
     */
    public function listAll()
    {
        // Query para selecionar atletas com join para obter nome da academia
        $query = "SELECT a.id, a.email, a.nome, a.faixa, f.nome as academia, a.validado, a.responsavel, a.data_filiacao
              FROM atleta AS a 
              JOIN academia_filiada as f ON f.id = a.academia
              ORDER BY f.nome, a.nome";

        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute();
        } catch (Exception $e) {
            // Log do erro sem interromper o fluxo da aplicação
            error_log("Erro ao listar atletas: " . $e->getMessage());
            echo "erro [" . $e->getMessage() . "]";
            return false;
        }

        // Retorna todos os resultados como objetos
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Recupera uma lista de atletas com cadastros pendentes de validação
     * 
     * Esta função consulta a base de dados para obter todos os atletas que possuem
     * cadastros não validados (status validado = 0). É particularmente útil para
     * administradores que precisam revisar e aprovar novos cadastros no sistema.
     * 
     * @return array|false Retorna um array de objetos contendo dados dos atletas pendentes
     *                     ou false em caso de erro na execução da query
     * 
     * @example
     * // Uso para obter lista de atletas pendentes de validação
     * $atletasPendentes = $controller->listInvalido();
     * if ($atletasPendentes) {
     *     foreach ($atletasPendentes as $atleta) {
     *         echo "Atleta pendente: {$atleta->nome} - Email: {$atleta->email}";
     *     }
     * }
     * 
     * @example
     * // Uso em painel administrativo para moderação de cadastros
     * $pendentes = $controller->listInvalido();
     * if (!empty($pendentes)) {
     *     // Exibir lista para aprovação/rejeição de cadastros
     * }
     * 
     * @data
     * Campos retornados para cada atleta pendente:
     * - id: Identificador único do atleta
     * - nome: Nome completo do atleta
     * - email: Endereço de email para contato
     * - data_nascimento: Data de nascimento (formato DATE)
     * - fone: Número de telefone para contato
     * - academia: Nome da academia filiada
     * - faixa: Faixa de graduação no Jiu-Jitsu
     * - peso: Peso do atleta em quilogramas
     * 
     * @note A função retorna apenas atletas com validado = 0 (não validados)
     * @note Utiliza JOIN com academia_filiada para obter o nome da academia
     * @note Em caso de erro na query, a função exibe a mensagem mas continua a execução
     * 
     * @security
     * - Acesso tipicamente restrito a usuários administrativos
     * - Retorna informações sensíveis (email, telefone) - usar com cautela
     */
    public function listInvalido()
    {
        validarPermissaoAdmin();
        // Query para selecionar atletas não validados com informações completas
        $query = "SELECT a.id, a.nome, a.email, a.data_nascimento,
                     a.fone, f.nome as academia, a.responsavel, a.faixa, a.peso
              FROM atleta a
              JOIN academia_filiada f ON f.id = a.academia
              WHERE a.validado = 0";

        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute();
        } catch (Exception $e) {
            // Log do erro para debugging, considerando esconder detalhes em produção
            error_log("Erro ao listar atletas não validados: " . $e->getMessage());
            echo "erro [" . $e->getMessage() . "]";
            return false;
        }

        // Retorna todos os resultados como objetos
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }


    /**
     * Realiza o processo de autenticação de usuários no sistema
     * 
     * Esta função verifica as credenciais de login (email e senha) fornecidas,
     * autentica o usuário através de verificação de hash de senha e inicia
     * a sessão do usuário com seus dados pessoais e permissões.
     * 
     * @return void
     * 
     * @process
     * 1. Busca o usuário pelo email fornecido
     * 2. Verifica se a senha corresponde ao hash armazenado
     * 3. Verifica se a conta está validada
     * 4. Inicia sessão com dados do usuário
     * 5. Redireciona conforme o status da autenticação
     * 
     * @redirection
     * - Login bem-sucedido: pagina_pessoal.php
     * - Senha inválida: login.php?erro=3
     * - Conta não validada: index.php?message=1
     * - Email não encontrado: login.php?erro=1
     * 
     * @session
     * Armazena na sessão:
     * - logado: Status de autenticação
     * - id: ID único do usuário
     * - nome: Nome completo
     * - email: Email do usuário
     * - foto: Caminho da foto de perfil
     * - idade: Idade calculada a partir da data de nascimento
     * - data_nascimento: Data de nascimento
     * - fone: Telefone de contato
     * - academia: Nome da academia (obtido via getAcad())
     * - faixa: Faixa de graduação
     * - peso: Peso do atleta
     * - diploma: Caminho do diploma
     * - cpf: CPF do usuário
     * - admin: Status de administrador (0 ou 1)
     * - responsavel: Status de responsável (0 ou 1)
     * - validado: Status de validação da conta
     * 
     * @security
     * - Utiliza password_verify() para verificação segura de senha
     * - Prevenção contra timing attacks através de verificação consistente
     * - Armazena apenas informações necessárias na sessão
     * 
     * @since Versão 1.0
     */
    public function logar()
    {
        $query = "SELECT id, nome, cpf, genero, senha, foto, academia, email, data_nascimento, fone, faixa, peso, adm, validado, responsavel, diploma
                FROM atleta
                WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":email", $this->atleta->__get("email"));
        try {
            $stmt->execute(); // Tenta executar a consulta
            $atleta = $stmt->fetch(PDO::FETCH_OBJ);
            if ($atleta) {
                if (!password_verify($this->atleta->__get("senha"), $atleta->senha)) {
                    //erro 3 senha inválida
                    header('Location: login.php?erro=3');
                    exit();
                }
                if ($atleta->validado) {
                    // Define as variáveis da sessão
                    $_SESSION["logado"] = true;
                    $_SESSION["id"] = $atleta->id;
                    $_SESSION["nome"] = $atleta->nome;
                    $_SESSION["email"] = $atleta->email;
                    $_SESSION["genero"] = $atleta->genero;
                    $_SESSION["foto"] = $atleta->foto;
                    $_SESSION["idade"] = calcularIdade($atleta->data_nascimento);
                    $_SESSION["data_nascimento"] = $atleta->data_nascimento;
                    $_SESSION["fone"] = $atleta->fone;
                    $_SESSION["endereco_completo"] = $atleta->endereco_completo ?? '';
                    $_SESSION["academia_id"] = $atleta->academia;
                    $_SESSION["academia"] = $this->getAcad($atleta->academia);
                    $_SESSION["faixa"] = $atleta->faixa;
                    $_SESSION["peso"] = $atleta->peso;
                    $_SESSION["diploma"] = $atleta->diploma;
                    $_SESSION["cpf"] = $atleta->cpf;
                    $_SESSION["admin"] = $atleta->adm == 0 ? 0 : 1;
                    $_SESSION["responsavel"] = $atleta->responsavel == 0 ? 0 : 1;
                    $_SESSION["validado"] = true;
                    header("Location: pagina_pessoal.php");
                    exit();
                } else {
                    //erro dois conta não validada
                    header('Location: index.php?message=1');
                    exit();
                }
            } else {
                header('Location: login.php?erro=1');
                exit();
            }
        } catch (PDOException $e) {
            // Captura qualquer erro gerado pela execução da consulta
            echo "Erro ao tentar logar: " . $e->getMessage();
        }
    }

    //***********TROCAR SENHA */
    /**
     * Gera código de recuperação e armazena em sessão
     */
    public function gerarCodigoRecuperacao($email)
    {
        if (!$this->emailExists($email)) {
            throw new Exception("Email não encontrado em nosso sistema");
        }

        $codigo = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        $_SESSION['recuperacao_senha'] = [
            'email' => $email,
            'codigo' => $codigo,
            'expiracao' => time() + 1800, // 30 minutos
            'tentativas' => 0
        ];

        return $codigo;
    }

    /**
     * Verifica o código de recuperação
     */
    public function verificarCodigoRecuperacao($codigo)
    {
        if (!isset($_SESSION['recuperacao_senha'])) {
            throw new Exception("Nenhuma solicitação de recuperação ativa");
        }

        $dados = $_SESSION['recuperacao_senha'];

        if (time() > $dados['expiracao']) {
            unset($_SESSION['recuperacao_senha']);
            throw new Exception("Código expirado. Solicite um novo.");
        }

        if ($dados['tentativas'] >= 3) {
            unset($_SESSION['recuperacao_senha']);
            throw new Exception("Muitas tentativas inválidas. Solicite um novo código.");
        }

        $_SESSION['recuperacao_senha']['tentativas']++;

        if ($codigo !== $dados['codigo']) {
            throw new Exception("Código inválido");
        }

        $_SESSION['recuperacao_senha']['codigo_verificado'] = true;
        return true;
    }

    /**
     * Redefine a senha após verificação
     */
    public function redefinirSenha($novaSenha)
    {
        if (
            !isset($_SESSION['recuperacao_senha']) ||
            !$_SESSION['recuperacao_senha']['codigo_verificado']
        ) {
            throw new Exception("Verificação do código necessária");
        }

        $email = $_SESSION['recuperacao_senha']['email'];
        $senhaHash = password_hash($novaSenha, PASSWORD_BCRYPT);

        $query = "UPDATE atleta SET senha = :senha WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":senha", $senhaHash);
        $stmt->bindValue(":email", $email);

        if (!$stmt->execute()) {
            throw new Exception("Erro ao atualizar senha no banco de dados");
        }

        unset($_SESSION['recuperacao_senha']);
        return true;
    }

    //retornar um atleta especifico
    public function getById($id)
    {
        $query = "SELECT a.id, a.nome, a.email, a.data_nascimento, a.foto, a.academia as acadid,
                a.fone, a.endereco_completo, f.nome AS academia, a.faixa, a.peso, a.validado, a.diploma, a.responsavel, a.data_filiacao,
                a.permissao_email, a.responsavel, a.cpf
                FROM atleta a JOIN academia_filiada f ON a.academia = f.id WHERE a.id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":id", $id);
        try {
            $stmt->execute();
        } catch (Exception $e) {
            echo "erro [" . $e->getMessage() . "]";
        }
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return $result;
    }
    //edição feita pelo administrador
    public function editAdmin($id, $validado, $faixa)
    {
        validarPermissaoAdmin();

        $query = "UPDATE atleta SET validado = :validado, faixa = :faixa WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":validado", $validado);
        $stmt->bindValue(":faixa", $faixa);
        $stmt->bindValue(":id", $id);
        try {
            $stmt->execute();
        } catch (Exception $e) {
            echo "erro [" . $e->getMessage() . "]";
        }
        //header("Location: controle?user=".$id."?msg=sucesso");
    }
    //ver se um email existe
    public function emailExists($email)
    {
        // Query para verificar se o e-mail existe
        $query = "SELECT COUNT(*) as count FROM atleta WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":email", $email);
        $stmt->execute();
        // Obtém o número de registros encontrados
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Verifica se um CPF já está cadastrado no sistema
     * 
     * Esta função consulta a base de dados para verificar se o CPF fornecido
     * já está cadastrado para algum atleta, prevenindo duplicidades.
     * 
     * @param string $cpf CPF a ser verificado (com ou sem formatação)
     * @return bool Retorna true se o CPF já existe, false caso contrário
     * 
     * @example
     * if ($atletaService->cpfExists('123.456.789-00')) {
     *     echo "CPF já cadastrado!";
     * }
     * 
     * @security
     * - Remove caracteres não numéricos antes da verificação
     * - Previne SQL injection através de prepared statements
     */
    public function cpfExists($cpf)
    {
        // Remove caracteres não numéricos
        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);

        // Query para verificar se o CPF existe
        $query = "SELECT COUNT(*) as count FROM atleta WHERE REPLACE(REPLACE(cpf, '.', ''), '-', '') = :cpf";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":cpf", $cpfLimpo);

        try {
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Erro ao verificar CPF: " . $e->getMessage());
            return false; // Em caso de erro, assume que não existe para não bloquear o cadastro
        }
    }
    public function listarCampeonatos($id_atleta)
    {
        $query = 'SELECT 
                    e.id as idC, 
                    e.nome as campeonato, 
                    e.local_evento as lugar, 
                    e.data_evento as dia,
                    i.mod_com as mcom, 
                    i.mod_sem as msem, 
                    i.mod_ab_com as macom, 
                    i.mod_ab_sem as masem, 
                    i.modalidade,
                    i.id_cobranca_asaas as assas, 
                    i.valor_pago, 
                    i.status_pagamento,
                    e.data_limite as data_limite
                  FROM inscricao i
                  JOIN evento e ON e.id = i.id_evento
                  WHERE i.id_atleta = :idAtleta
                  ORDER BY e.data_evento DESC, e.nome ASC';

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":idAtleta", $id_atleta, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_OBJ);
            return $result ?: []; // Retorna array vazio se não houver resultados

        } catch (PDOException $e) {
            error_log("Erro ao listar campeonatos para atleta {$id_atleta}: " . $e->getMessage());
            throw new Exception("Não foi possível carregar suas inscrições. Por favor, tente novamente mais tarde.");
        }
    }

    //editar atleta
    public function updateAtleta()
    {
        $query = "UPDATE atleta SET email = :email, fone = :fone, endereco_completo = :endereco_completo, foto = :foto, peso = :peso, permissao_email = :permissao_email
            WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":email", $this->atleta->__get("email"));
        $stmt->bindValue(":permissao_email", $this->atleta->__get("permissao_email"));
        $stmt->bindValue(":fone", $this->atleta->__get("fone"));
        $stmt->bindValue(":endereco_completo", $this->atleta->__get("endereco_completo"));
        $stmt->bindValue(":foto", $this->atleta->__get("foto"));
        $stmt->bindValue(":peso", $this->atleta->__get("peso"));
        $stmt->bindValue(":id", $this->atleta->__get("id"));
        try {
            $stmt->execute();
            $_SESSION["email"] = $this->atleta->__get("email");
            $_SESSION["foto"] = $this->atleta->__get("foto");
            $_SESSION["fone"] = $this->atleta->__get("fone");
            $_SESSION["peso"] = $this->atleta->__get("peso");
            header("Location: /pagina_pessoal.php");
            exit();
        } catch (Exception $e) {
            print ("Erro ao editar: " . $e->getMessage());
        }
    }
    //editar faixa
    public function updateFaixa()
    {
        try {
            $query = "UPDATE atleta SET faixa = :faixa, diploma = :diploma, validado = 0 
                 WHERE id = :id";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(":faixa", $this->atleta->__get("faixa"));
            $stmt->bindValue(":diploma", $this->atleta->__get("diploma"));
            $stmt->bindValue(":id", $this->atleta->__get("id"));

            $stmt->execute();

            // Destruir a sessão para deslogar o usuário
            session_destroy();

            return true;

        } catch (Exception $e) {
            error_log("Erro ao atualizar faixa: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Solicita troca de faixa para um atleta (função para responsável)
     * 
     * @param int $atletaId ID do atleta
     * @param string $novaFaixa Nova faixa solicitada
     * @param string $diploma Nome do arquivo do novo diploma
     * @return bool True se a solicitação foi bem-sucedida
     * @throws Exception Em caso de erro
     */
    public function solicitarTrocaFaixaParaAluno($atletaId, $novaFaixa, $diploma)
    {
        try {
            // Verificar se o responsável tem permissão
            if (!isset($_SESSION['responsavel']) || $_SESSION['responsavel'] != 1) {
                throw new Exception("Acesso não autorizado.");
            }

            // Verificar se o atleta pertence à academia do responsável
            $query = "SELECT academia FROM atleta WHERE id = :atletaId";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':atletaId', $atletaId, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result || $result['academia'] != $_SESSION['academia_id']) {
                throw new Exception("Atleta não pertence à sua academia.");
            }

            // Atualizar faixa e diploma do atleta
            $query = "UPDATE atleta 
                 SET faixa = :faixa, 
                     diploma = :diploma, 
                     validado = 0 
                 WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":faixa", $novaFaixa);
            $stmt->bindValue(":diploma", $diploma);
            $stmt->bindValue(":id", $atletaId, PDO::PARAM_INT);

            return $stmt->execute();

        } catch (Exception $e) {
            error_log("Erro ao solicitar troca de faixa: " . $e->getMessage());
            throw new Exception("Não foi possível solicitar a troca de faixa: " . $e->getMessage());
        }
    }

    //função para pegar inscricao
    public function getInscricao($evento, $atleta)
    {
        $query = "SELECT e.nome AS nome, e.id, e.preco, e.preco_menor, e.preco_abs, 
                        e.tipo_com, e.tipo_sem, 
                        i.mod_com, i.mod_ab_com, i.mod_sem, i.mod_ab_sem, i.modalidade,
                        i.id_cobranca_asaas, i.valor_pago
                      FROM inscricao i
                      JOIN evento e ON e.id = i.id_evento
                      JOIN atleta a ON a.id = i.id_atleta
                      WHERE i.id_evento = :evento AND i.id_atleta = :atleta";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":evento", $evento, PDO::PARAM_INT);
        $stmt->bindValue(":atleta", $atleta, PDO::PARAM_INT);

        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            error_log("Erro ao buscar inscrição: " . $e->getMessage());
            return null;
        }
    }
    //editar inscrição
    public function editarInscricao($evento, $idAtleta, $com, $abCom, $sem, $abSem, $moda)
    {
        $query = "UPDATE inscricao
            SET mod_com = :com, mod_sem = :sem, mod_ab_com = :abCom, mod_ab_sem = :abSem, modalidade = :mod
            WHERE id_evento = :evento AND id_atleta = :idAtleta";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":com", ($com || $abCom) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(":sem", ($sem || $abSem) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(":abCom", $abCom);
        $stmt->bindValue(":abSem", $abSem);
        $stmt->bindValue(":mod", $moda);
        $stmt->bindValue(":evento", $evento);
        $stmt->bindValue(":idAtleta", $idAtleta);
        try {
            return $stmt->execute();
        } catch (Exception $e) {
            echo "erro ao editar inscricao [ " . $e->getMessage() . " ]";
        }
    }
    /**
     * Atualiza o valor pago em uma inscrição
     * @param int $eventoId ID do evento
     * @param int $atletaId ID do atleta
     * @param float $novoValor Novo valor da inscrição
     * @return bool True se a atualização foi bem-sucedida
     * @throws Exception Em caso de erro na execução
     */
    public function atualizarValorInscricao($eventoId, $atletaId, $novoValor)
    {
        try {
            $query = "UPDATE inscricao SET 
                        valor_pago = :valor,
                        status_pagamento = 'PENDING'
                      WHERE id_atleta = :atletaId 
                      AND id_evento = :eventoId";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':valor', $novoValor, PDO::PARAM_STR);
            $stmt->bindValue(':atletaId', $atletaId, PDO::PARAM_INT);
            $stmt->bindValue(':eventoId', $eventoId, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Erro ao atualizar valor: " . $errorInfo[2]);
            }

            return true;

        } catch (Exception $e) {
            error_log("ERRO atualizarValorInscricao: " . $e->getMessage());
            throw new Exception("Não foi possível atualizar o valor da inscrição");
        }
    }

    //Deletar atleta
    public function excluirAtleta($id)
    {
        if (!$_SESSION['admin']) {
            throw new Exception("Sem permissão para excluir este atleta");
        }
        $dados = $this->getById($id);
        $quary = "DELETE FROM atleta WHERE id = :id";
        $stmt = $this->conn->prepare($quary);
        $stmt->bindValue(":id", $id);
        try {
            $stmt->execute();
            //remover diploma e foto

            $diplomaPath = __DIR__ . '/../diplomas/' . $dados->diploma;
            $fotoPath = __DIR__ . '/../fotos/' . $dados->foto;

            if (file_exists($diplomaPath)) {
                unlink($diplomaPath);
            } else {
                error_log("Arquivo de diploma não encontrado: " . $diplomaPath);
            }

            if (file_exists($fotoPath)) {
                unlink($fotoPath);
            } else {
                error_log("Arquivo de foto não encontrado: " . $fotoPath);
            }
            if ($dados->responsavel) {
                //deletar todos os atletas
                $quary = "SELECT id FROM atleta WHERE academia = :acad";
                $stmt = $this->conn->prepare($quary);
                $stmt->bindValue(":acad", $dados->acadid);
                try {
                    $stmt->execute();
                    $lista = $stmt->fetchAll(PDO::FETCH_OBJ);
                    // Deletar cada atleta da academia
                    $idsAtletas = array_map(function ($ar) {
                        return $ar->id;
                    }, $lista);
                    if (count($idsAtletas) > 0) {
                        // Se não houver IDs, não executa
                        if (count($idsAtletas) > 0) {
                            // Cria um placeholder para cada ID (ex: ":id0, :id1, :id2")
                            $placeholders = implode(',', array_map(function ($index) {
                                return ':id' . $index;
                            }, array_keys($idsAtletas)));

                            $queryDeleteAtletas = "DELETE FROM atleta WHERE id IN ($placeholders)";
                            $stmtDelete = $this->conn->prepare($queryDeleteAtletas);

                            // Faz o bind de cada valor
                            foreach ($idsAtletas as $index => $id) {
                                $stmtDelete->bindValue(':id' . $index, $id, PDO::PARAM_INT);
                            }

                            $stmtDelete->execute();
                        }

                        // Remover diploma e foto de todos os atletas
                        foreach ($lista as $ar) {
                            $atletaData = $this->getById($ar->id);
                            unlink("diplomas/" . $atletaData->diploma);
                            unlink("fotos/" . $atletaData->foto);
                        }
                    }

                    // Deletar a própria academia
                    $query = "DELETE FROM academia_filiada WHERE id = :acad";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindValue(":acad", $dados->acadid);
                    $stmt->execute();
                } catch (Exception $e) {
                    echo "erro ao editar inscricao [ " . $e->getMessage() . " ]";
                }

            }
            header("Location: /admin/pessoas.php");
            exit();
        } catch (Exception $e) {
            echo "erro ao excluit [ " . $e->getMessage() . " ]";
        }
    }

    //excluir inscricao
    public function excluirInscricao($evento, $atleta)
    {
        $quary = "DELETE FROM inscricao WHERE id_atleta = :idA AND id_evento = :idE";
        $stmt = $this->conn->prepare($quary);
        $stmt->bindValue(":idA", $atleta);
        $stmt->bindValue(":idE", $evento);
        try {
            $stmt->execute();
            header("Location: /eventos_cadastrados.php");
            exit();
        } catch (Exception $e) {
            echo "erro ao excluit inscricao [ " . $e->getMessage() . " ]";
        }
    }
    //***************************FUNÇÕES DE ACADEMIA*******************/
    //funçoes de afiliação de academia
    public function Filiar($nome, $cep, $cidade, $estado)
    {
        try {
            $query = "INSERT INTO academia_filiada (nome, cep, cidade, estado) VALUES (:nome, :cep, :cidade, :estado)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":nome", ucwords($nome));
            $stmt->bindValue(":cep", preg_replace('/[^0-9]/', '', $cep));
            $stmt->bindValue(":cidade", ucwords($cidade));
            $stmt->bindValue(":estado", strtoupper($estado));

            if (!$stmt->execute()) {
                throw new Exception("Falha ao executar INSERT da academia");
            }

            // Retorna o ID da academia inserida
            return $this->conn->lastInsertId();

        } catch (Exception $e) {
            error_log("ERRO no Filiar(): " . $e->getMessage());
            error_log("Dados: nome=$nome, cep=$cep, cidade=$cidade, estado=$estado");
            throw new Exception("Erro ao cadastrar academia: " . $e->getMessage());
        }
    }
    //ver se academia existe
    public function existAcad($nome)
    {
        $query = "SELECT COUNT(*) as num FROM academia_filiada WHERE nome = :nome";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":nome", ucwords($nome));

        try {
            $stmt->execute();
            $resp = $stmt->fetch(PDO::FETCH_OBJ);

            // DEBUG
            error_log("existAcad('$nome'): " . $resp->num . " registros encontrados");

            return $resp->num > 0; // Retorna true se NÃO existir

        } catch (Exception $e) {
            error_log("Erro em existAcad: " . $e->getMessage());
            return true; // Assume que não existe em caso de erro
        }
    }
    //funçao para consegui id da academia
    public function getIdAcad($nomeAcad)
    {
        $nomeFormatado = ucwords($nomeAcad);

        // Primeira tentativa: busca exata
        $query = "SELECT id FROM academia_filiada WHERE nome = :nome";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":nome", $nomeFormatado);

        try {
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            error_log("Busca academia: '" . $nomeFormatado . "' - Resultado: " . ($result ? $result['id'] : 'NÃO ENCONTRADO'));

            if ($result) {
                return $result; // ← MANTÉM como array, não apenas o ID
            }

            // Segunda tentativa: busca com LIKE para casos de diferenças
            $queryLike = "SELECT id FROM academia_filiada WHERE nome LIKE :nome";
            $stmtLike = $this->conn->prepare($queryLike);
            $stmtLike->bindValue(":nome", "%" . $nomeFormatado . "%");
            $stmtLike->execute();
            $resultLike = $stmtLike->fetch(PDO::FETCH_ASSOC);

            error_log("Busca com LIKE: " . ($resultLike ? $resultLike['id'] : 'NÃO ENCONTRADO'));

            return $resultLike ?: false; // ← Retorna array ou false

        } catch (Exception $e) {
            error_log("Erro ao buscar ID da academia '" . $nomeAcad . "': " . $e->getMessage());
            return false;
        }
    }

    //conseguir o id do responsavel
    public function getResponsavel($email, $nome)
    {
        $query = "SELECT id FROM atleta WHERE email = :email AND nome = :nome";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":nome", $nome);
        $stmt->bindValue(":email", $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    //vincular uma academia a um responsavel e viceversa
    public function atribuirAcademia($acad, $professor)
    {
        try {
            $query = "UPDATE academia_filiada SET responsavel = :responsavel WHERE id = :academia";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":responsavel", $professor, PDO::PARAM_INT);
            $stmt->bindValue(":academia", $acad, PDO::PARAM_INT);
            $stmt->execute();

            $query = "UPDATE atleta SET academia = :academia WHERE id = :responsavel";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":responsavel", $professor, PDO::PARAM_INT);
            $stmt->bindValue(":academia", $acad, PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw new Exception("Erro de atribuição: " . $e->getMessage());
        }
    }

    //conseguir o nome da academia
    public function getAcad($id)
    {
        $query = "SELECT nome FROM academia_filiada WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        try {
            $stmt->execute();
        } catch (Exception $e) {
            echo "erro [" . $e->getMessage() . "]";
        }
        $acad = $stmt->fetch(PDO::FETCH_ASSOC);
        return $acad["nome"];
    }

    public function getAcademias()
    {
        $query = "SELECT f.id, f.nome FROM academia_filiada f 
            JOIN atleta a ON f.responsavel = a.id 
            WHERE a.validado = 1";
        $stmt = $this->conn->prepare($query);
        try {
            $stmt->execute();
        } catch (Exception $e) {
            echo "erro [" . $e->getMessage() . "]";
        }
        $lista = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $lista;
    }

    /**
     * Lista todos os alunos de uma academia específica
     * 
     * @param int $academiaId ID da academia
     * @return array|false Array de objetos com dados dos alunos ou false em caso de erro
     */
    public function listarAlunosAcademia($academiaId)
    {
        // Primeiro verificar a sessão
        if (!isset($_SESSION["academia_id"]) || $_SESSION["academia_id"] != $academiaId || $_SESSION["responsavel"] != 1) {
            // Se for uma requisição AJAX ou API, lançar exceção
            if (
                php_sapi_name() !== 'cli' && (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest')
            ) {
                // Para requisições normais, redirecionar
                header("Location: /index.php");
                exit();
            }
            throw new Exception("Acesso não autorizado. Você não tem permissão para visualizar estes dados.");
        }

        try {
            $query = "SELECT a.id, a.nome, a.email, a.faixa, a.peso, 
                     a.data_nascimento, a.validado, a.data_filiacao,
                     a.fone, a.endereco_completo, a.genero, a.cpf
              FROM atleta a
              WHERE a.academia = :academia_id 
              AND a.responsavel = 0
              ORDER BY a.nome ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':academia_id', $academiaId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            error_log("Erro ao listar alunos da academia {$academiaId}: " . $e->getMessage());
            throw new Exception("Não foi possível listar os alunos. Tente novamente mais tarde.");
        }
    }

    /********API**********/

    public function verificarStatusPagamento($atletaId, $eventoId)
    {
        $query = "SELECT status_pagamento FROM inscricao 
                      WHERE id_atleta = :atletaId AND id_evento = :eventoId";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':atletaId', $atletaId);
        $stmt->bindValue(':eventoId', $eventoId);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && isset($result['status_pagamento'])) {
            return $result['status_pagamento'] == 'RECEIVED' ? 'PAGO' : 'PENDENTE';
        }

        return 'NÃO ENCONTRADO';
    }
}
?>