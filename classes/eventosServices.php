<?php
require_once __DIR__ . "/../func/database.php";
require_once __DIR__ . "/../classes/eventoClass.php";
require_once __DIR__ . "/../func/calcularIdade.php";
require_once __DIR__ . "/../func/security.php";
require_once __DIR__ . "/../func/determinar_categoria.php";
class eventosService
{
    private $conn;
    private $evento;

    public function __construct(Conexao $conn, Evento $evento)
    {
        $this->conn = $conn->conectar();
        $this->evento = $evento;
    }

    //adicionar um evento novo
    public function addEvento()
    {
        $query = "INSERT INTO evento (
        nome, descricao, data_limite, data_evento, local_evento, 
        tipo_com, tipo_sem, imagen, preco, preco_menor, preco_abs,
        preco_sem, preco_sem_menor, preco_sem_abs,
        doc, normal, normal_preco
    ) VALUES (
        :nome, :descricao, :data_limite, :data_evento, :local_camp, 
        :tipoCom, :tipoSem, :img, :preco, :preco_menor, :preco_abs,
        :preco_sem, :preco_sem_menor, :preco_sem_abs,
        :doc, :normal, :normal_preco
    )";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':nome', $this->evento->__get('nome'));
        $stmt->bindValue(':data_evento', $this->evento->__get('data_evento'));
        $stmt->bindValue(':local_camp', $this->evento->__get('local_camp'));
        $stmt->bindValue(':descricao', $this->evento->__get('descricao'));
        $stmt->bindValue(':data_limite', $this->evento->__get('data_limite'));
        $stmt->bindValue(':tipoCom', $this->evento->__get('tipoCom'));
        $stmt->bindValue(':tipoSem', $this->evento->__get('tipoSem'));
        $stmt->bindValue(':preco', $this->evento->__get('preco'));
        $stmt->bindValue(':preco_menor', $this->evento->__get('preco_menor'));
        $stmt->bindValue(':preco_abs', $this->evento->__get('preco_abs'));
        $stmt->bindValue(':img', $this->evento->__get('img'));
        $stmt->bindValue(':doc', $this->evento->__get('doc'));
        $stmt->bindValue(':normal', $this->evento->__get('normal'), PDO::PARAM_STR);
        $stmt->bindValue(':normal_preco', $this->evento->__get('normal_preco'), PDO::PARAM_STR);
        $stmt->bindValue(':preco_sem', $this->evento->__get('preco_sem'));
        $stmt->bindValue(':preco_sem_menor', $this->evento->__get('preco_sem_menor'));
        $stmt->bindValue(':preco_sem_abs', $this->evento->__get('preco_sem_abs'));
        try {
            $stmt->execute();
            header("Location: /eventos.php");
            exit();
        } catch (Exception $e) {
            echo 'Erro ao adicionar evento: ' . $e->getMessage();
        }
    }
    //editar evento
    public function editEvento()
    {
        $query = "UPDATE evento SET
        nome = :nome,
        imagen = :imagen,
        descricao = :descricao,
        data_limite = :data_limite,
        data_evento = :data_evento,
        local_evento = :local_evento,
        tipo_com = :tipoCom,
        tipo_sem = :tipoSem,
        preco = :preco,
        preco_menor = :preco_menor,
        preco_abs = :preco_abs,
        preco_sem = :preco_sem,
        preco_sem_menor = :preco_sem_menor,
        preco_sem_abs = :preco_sem_abs,
        doc = :doc,
        normal = :normal,
        normal_preco = :normal_preco,
        chaveamento = :chaveamento
        WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Mapeamento correto dos campos
        $stmt->bindValue(':id', $this->evento->__get('id'), PDO::PARAM_INT);
        $stmt->bindValue(':nome', $this->evento->__get('nome'), PDO::PARAM_STR);
        $stmt->bindValue(':imagen', $this->evento->__get('img'), PDO::PARAM_STR);
        $stmt->bindValue(':data_evento', $this->evento->__get('data_evento'), PDO::PARAM_STR);
        $stmt->bindValue(':local_evento', $this->evento->__get('local_camp'), PDO::PARAM_STR);
        $stmt->bindValue(':descricao', $this->evento->__get('descricao'), PDO::PARAM_STR);
        $stmt->bindValue(':data_limite', $this->evento->__get('data_limite'), PDO::PARAM_STR);
        $stmt->bindValue(':tipoCom', $this->evento->__get('tipoCom'), PDO::PARAM_INT);
        $stmt->bindValue(':tipoSem', $this->evento->__get('tipoSem'), PDO::PARAM_INT);
        $stmt->bindValue(':preco', $this->evento->__get('preco'));
        $stmt->bindValue(':preco_menor', $this->evento->__get('preco_menor'));
        $stmt->bindValue(':preco_abs', $this->evento->__get('preco_abs'));
        $stmt->bindValue(':preco_sem', $this->evento->__get('preco_sem'));
        $stmt->bindValue(':preco_sem_menor', $this->evento->__get('preco_sem_menor'));
        $stmt->bindValue(':preco_sem_abs', $this->evento->__get('preco_sem_abs'));
        $stmt->bindValue(':doc', $this->evento->__get('doc'), PDO::PARAM_STR);
        $stmt->bindValue(':normal', $this->evento->__get('normal'), PDO::PARAM_STR);
        $stmt->bindValue(':normal_preco', $this->evento->__get('normal_preco'), PDO::PARAM_STR);
        $stmt->bindValue(':chaveamento', $this->evento->__get('chaveamento'), PDO::PARAM_STR);
        try {
            $result = $stmt->execute();
            return $result; // Retorna true/false para o chamador decidir o redirecionamento
        } catch (Exception $e) {
            error_log('Erro ao editar evento: ' . $e->getMessage());
            throw new Exception("Erro ao atualizar o evento no banco de dados");
        }
    }

    //listar todos os eventos
    public function listAll()
    {
        $query = "SELECT id, nome, imagen, normal FROM evento WHERE data_evento >= CURRENT_DATE + INTERVAL 5 DAY";
        $stmt = $this->conn->prepare($query);
        try {
            $stmt->execute();
        } catch (Exception $e) {
            echo 'Erro ao listar eventos : ' . $e->getMessage();
        }
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getById($id)
    {
        $query = "SELECT 
                id, 
                nome, 
                descricao, 
                data_evento, 
                data_limite, 
                local_evento AS local_camp, 
                tipo_com, 
                tipo_sem, 
                preco, 
                preco_menor, 
                preco_abs, 
                preco_sem, 
                preco_sem_menor, 
                preco_sem_abs, 
                imagen, 
                doc,
                normal AS normal,
                normal_preco,
                chaveamento
              FROM evento 
              WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$result) {
                throw new Exception("Evento não encontrado");
            }
            return $result;
        } catch (Exception $e) {
            error_log('Erro ao buscar evento por ID: ' . $e->getMessage());
            throw new Exception("Erro ao buscar informações do evento");
        }
    }
    //pegar todos os inscritos de um evento
    public function getInscritos($id)
    {
        $query = "SELECT 
                e.nome AS evento, 
                e.id AS ide, 
                a.nome AS inscrito, 
                a.data_nascimento, 
                a.id,
                a.faixa, 
                a.peso, 
                a.genero,
                f.nome as academia, 
                f.id as idAcademia,
                i.mod_com, 
                i.mod_sem, 
                i.mod_ab_com, 
                i.mod_ab_sem, 
                i.modalidade as modalidade,
                i.status_pagamento, 
                i.id_cobranca_asaas, 
                i.valor_pago, 
                a.email
            FROM evento e
            JOIN inscricao i ON i.id_evento = e.id
            JOIN atleta a ON a.id = i.id_atleta
            JOIN academia_filiada f ON a.academia = f.id
            WHERE e.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        try {
            $stmt->execute();
        } catch (Exception $e) {
            error_log('Erro ao listar inscritos no evento: ' . $e->getMessage());
            throw new Exception("Erro ao carregar lista de inscritos");
        }

        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Verifica se um evento está disponível para inscrição
     * @param int $id_evento ID do evento
     * @return array Retorna informações sobre a disponibilidade
     * @throws Exception Se o evento não for encontrado
     */
    public function verificarDisponibilidadeEvento($id_evento)
    {
        $query = "SELECT data_limite, data_evento, nome FROM evento WHERE id = :id_evento";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id_evento', $id_evento, PDO::PARAM_INT);
        $stmt->execute();
        $evento = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$evento) {
            throw new Exception("Evento não encontrado");
        }

        $dataLimite = new DateTime($evento->data_limite);
        $dataEvento = new DateTime($evento->data_evento);
        $hoje = new DateTime();

        // Adiciona 1 dia à data limite para considerar o dia inteiro
        $dataLimite->modify('+1 day');

        $disponivel = true;
        $mensagem = "Inscrições abertas";

        if ($hoje >= $dataLimite) {
            $disponivel = false;
            $mensagem = "Inscrições encerradas. O prazo terminou em " . date('d/m/Y', strtotime($evento->data_limite));
        } elseif ($hoje > $dataEvento) {
            $disponivel = false;
            $mensagem = "Este evento já foi realizado em " . date('d/m/Y', strtotime($evento->data_evento));
        }

        return [
            'disponivel' => $disponivel,
            'mensagem' => $mensagem,
            'evento' => $evento
        ];
    }

    //inscrever um atleta em um evento
    public function inscrever($id_atleta, $id_evento, $mod_com, $mod_abs_com, $mod_sem, $mod_abs_sem, $modalidade, $categoria_idade, $aceite_regulamento, $aceite_responsabilidade)
    {
        try {
            // Verifica disponibilidade do evento
            $disponibilidade = $this->verificarDisponibilidadeEvento($id_evento);

            if (!$disponibilidade['disponivel']) {
                throw new Exception($disponibilidade['mensagem']);
            }

            $query = "INSERT INTO inscricao (
                        id_atleta, 
                        id_evento, 
                        mod_com, 
                        mod_sem, 
                        mod_ab_com, 
                        mod_ab_sem, 
                        modalidade,
                        categoria_idade,
                        aceite_regulamento,
                        aceite_responsabilidade
                      ) VALUES (
                        :id_atleta, 
                        :id_evento, 
                        :mod_com, 
                        :mod_sem, 
                        :mod_ab_com, 
                        :mod_ab_sem, 
                        :modalidade,
                        :categoria_idade,
                        :aceite_regulamento,
                        :aceite_responsabilidade
                      )";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id_atleta', $id_atleta, PDO::PARAM_INT);
            $stmt->bindValue(':id_evento', $id_evento, PDO::PARAM_INT);
            $stmt->bindValue(':mod_com', $mod_com, PDO::PARAM_INT);
            $stmt->bindValue(':mod_sem', $mod_sem, PDO::PARAM_INT);
            $stmt->bindValue(':mod_ab_com', $mod_abs_com, PDO::PARAM_INT);
            $stmt->bindValue(':mod_ab_sem', $mod_abs_sem, PDO::PARAM_INT);
            $stmt->bindValue(':modalidade', $modalidade, PDO::PARAM_STR);
            $stmt->bindValue(':categoria_idade', $categoria_idade, PDO::PARAM_STR);
            $stmt->bindValue(':aceite_regulamento', $aceite_regulamento, PDO::PARAM_BOOL);
            $stmt->bindValue(':aceite_responsabilidade', $aceite_responsabilidade, PDO::PARAM_BOOL);

            $result = $stmt->execute();

            return $result;

        } catch (Exception $e) {
            error_log("Erro ao inscrever atleta: " . $e->getMessage());
            throw $e;
        }
    }
    //ver se um atleta ja esta inscrito em um evento
    public function isInscrito($idAtleta, $idEvento)
    {
        $query = "SELECT COUNT(*) as total FROM inscricao 
                  WHERE id_atleta = :idAtleta AND id_evento = :idEvento";
        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(':idAtleta', $idAtleta, PDO::PARAM_INT);
        $stmt->bindValue(':idEvento', $idEvento, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return ($result->total > 0); // Retorna TRUE se já estiver inscrito
        } catch (Exception $e) {
            error_log('Erro ao verificar inscrição: ' . $e->getMessage());
            return false; // Em caso de erro, assume que não está inscrito
        }
    }

    // Atualizar categoria vazia
    //public function atualizarCategoriasVazias($idEvento)
    //{
    //    // Buscar inscrições com categoria vazia e dados dos atletas
    //    $query = "SELECT i.id_atleta, i.id_evento, a.peso, a.data_nascimento, a.genero, i.modalidade
    //          FROM inscricao i
    //          JOIN atleta a ON a.id = i.id_atleta
    //          WHERE i.id_evento = :id_evento AND (i.modalidade IS NULL OR i.modalidade = '')";
//
    //    $stmt = $this->conn->prepare($query);
    //    $stmt->bindValue(':id_evento', $idEvento, PDO::PARAM_INT);
    //    $stmt->execute();
//
    //    $inscricoesSemCategoria = $stmt->fetchAll(PDO::FETCH_OBJ);
    //    $atualizadas = 0;
//
    //    foreach ($inscricoesSemCategoria as $inscricao) {
    //        // Calcular idade
    //        $idade = calcularIdade($inscricao->data_nascimento);
    //        $peso = $inscricao->peso;
    //        $genero = $inscricao->genero;
//
    //        // Determinar categoria usando a função existente
    //        $categoria = determinarCategoriaPeso($peso, $idade, $genero);
//
    //        if (!empty($categoria)) {
    //            $updateQuery = "UPDATE inscricao SET modalidade = :categoria 
    //                       WHERE id_atleta = :id_atleta AND id_evento = :id_evento";
    //            $updateStmt = $this->conn->prepare($updateQuery);
    //            $updateStmt->bindValue(':categoria', $categoria);
    //            $updateStmt->bindValue(':id_atleta', $inscricao->id_atleta, PDO::PARAM_INT);
    //            $updateStmt->bindValue(':id_evento', $idEvento, PDO::PARAM_INT);
//
    //            if ($updateStmt->execute()) {
    //                $atualizadas++;
    //            }
    //        }
    //    }
//
    //    return $atualizadas;
    //}
    public function atualizarValorInscricao($idAtleta, $idEvento, $valor)
    {
        $query = "UPDATE inscricao SET 
                valor_pago = :valor 
              WHERE id_atleta = :id_atleta AND id_evento = :id_evento";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':valor', $valor, PDO::PARAM_STR);
        $stmt->bindValue(':id_atleta', $idAtleta, PDO::PARAM_INT);
        $stmt->bindValue(':id_evento', $idEvento, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Erro ao atualizar valor da inscrição: " . $e->getMessage());
            return false;
        }
    }
    // Limpar evento vencidos
    /**
     * Limpa eventos que já passaram mais de 7 dias da data limite
     * @return array Retorna estatísticas da operação (eventos deletados, arquivos deletados, etc.)
     * @throws Exception Em caso de erro grave
     */
    public function limparEventosExpirados()
    {
        // Primeiro obtemos os eventos que devem ser deletados
        //deleta eventos depois de 12 dias
        if (!$this->conn) {
            throw new Exception("Conexão com o banco de dados não disponível");
        }
        $query = "SELECT id, nome, data_limite FROM evento 
          WHERE data_limite < DATE_SUB(CURRENT_DATE, INTERVAL 12 DAY)
          AND data_limite IS NOT NULL";
        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute();
            $eventosParaDeletar = $stmt->fetchAll(PDO::FETCH_OBJ);
            $resultados = [
                'total_eventos' => count($eventosParaDeletar),
                'eventos_deletados' => 0,
                'erros' => []
            ];

            // Para cada evento, chamamos o método de deleção existente
            foreach ($eventosParaDeletar as $evento) {
                try {
                    $this->deletarEvento($evento->id);
                    $resultados['eventos_deletados']++;
                } catch (Exception $e) {
                    $resultados['erros'][] = [
                        'id_evento' => $evento->id,
                        'erro' => $e->getMessage()
                    ];
                    error_log("Erro ao deletar evento ID {$evento->id}: " . $e->getMessage());
                }
            }

            return $resultados;

        } catch (Exception $e) {
            error_log('Erro ao buscar eventos expirados: ' . $e->getMessage());
            throw new Exception("Erro ao buscar eventos para limpeza");
        }
    }
    //ver e limpar eventos
    public function verificarELimparEventosExpirados()
    {
        try {
            $resultado = $this->limparEventosExpirados();

            // Log detalhado
            $logMessage = sprintf(
                "[%s] Limpeza automática - Eventos: %d/%d removidos. Erros: %d",
                date('Y-m-d H:i:s'),
                $resultado['eventos_deletados'],
                $resultado['total_eventos'],
                count($resultado['erros'])
            );

            error_log($logMessage);

            // Log de erros individuais
            foreach ($resultado['erros'] as $erro) {
                error_log(sprintf(
                    "Erro ao remover evento %d: %s",
                    $erro['id_evento'],
                    $erro['erro']
                ));
            }

            return $resultado;

        } catch (Exception $e) {
            error_log("Falha na limpeza automática: " . $e->getMessage());
            throw $e;
        }
    }

    //Contagem de Inscrição por Categoria, Idade e Faixa
    public function contagemCategoria($id, $idade, $todos = false, $pendentes = false, $modalidade = "com", $faixa = null)
    {
        // Determinar a faixa etária
        $faixa_etaria = match (true) {
            $idade >= 4 && $idade <= 5 => "PRE-MIRIM",
            $idade >= 6 && $idade <= 7 => "MIRIM 1",
            $idade >= 8 && $idade <= 9 => "MIRIM 2",
            $idade >= 10 && $idade <= 11 => "INFANTIL 1",
            $idade >= 12 && $idade <= 13 => "INFANTIL 2",
            $idade >= 14 && $idade <= 15 => "INFANTO-JUVENIL",
            $idade >= 16 && $idade <= 17 => "JUVENIL",
            $idade >= 18 && $idade <= 29 => "ADULTO",
            $idade >= 30 => "MASTER",
            default => "OUTROS"
        };

        // Determinar as faixas que competem juntas
        $faixasDoGrupo = [];

        if ($faixa) {
            switch ($faixa) {
                case 'Cinza':
                case 'Amarela':
                    // Cinzas e Amarelas competem entre si
                    $faixasDoGrupo = ['Cinza', 'Amarela'];
                    break;
                case 'Laranja':
                case 'Verde':
                    // Laranjas e Verdes competem entre si
                    $faixasDoGrupo = ['Laranja', 'Verde'];
                    break;
                default:
                    // Branca, Azul, Roxa, Marrom, Preta, Coral, Vermelha e Branca, Vermelha
                    // competem apenas com a mesma faixa
                    $faixasDoGrupo = [$faixa];
                    break;
            }
        }

        // Condição de status de pagamento
        $query_pendentes = $pendentes ?
            "AND (i.status_pagamento = 'RECEIVED' OR i.status_pagamento = 'ISENTO' OR i.status_pagamento = 'GRATUITO')" :
            "AND i.status_pagamento = 'PENDING'";

        // Determinar qual coluna de modalidade usar - CORREÇÃO AQUI!
        $condicao_modalidade = '';
        switch ($modalidade) {
            case 'com':
                if ($todos) {
                    // ABSOLUTO: conta apenas quem está no absoluto (mod_ab_com = 1)
                    // Quem está no absoluto automaticamente está na categoria normal também
                    $condicao_modalidade = 'AND i.mod_ab_com = 1';
                } else {
                    // CATEGORIA NORMAL: conta quem está na categoria (mod_com = 1)
                    // Isso INCLUI quem está no absoluto, pois eles também estão na categoria normal
                    $condicao_modalidade = 'AND i.mod_com = 1';
                }
                break;
            case 'sem':
                if ($todos) {
                    // ABSOLUTO: conta apenas quem está no absoluto (mod_ab_sem = 1)
                    $condicao_modalidade = 'AND i.mod_ab_sem = 1';
                } else {
                    // CATEGORIA NORMAL: conta quem está na categoria (mod_sem = 1)
                    $condicao_modalidade = 'AND i.mod_sem = 1';
                }
                break;
        }

        // Se não há faixas definidas, retorna 0
        if (empty($faixasDoGrupo)) {
            return 0;
        }

        // Criar placeholders para as faixas
        $placeholders = implode(',', array_fill(0, count($faixasDoGrupo), '?'));

        // Query para contar inscrições
        $query = "SELECT COUNT(*) as quantidade 
              FROM inscricao i
              JOIN atleta a ON a.id = i.id_atleta
              WHERE i.id_evento = ? 
              AND i.categoria_idade = ? 
              AND a.faixa IN ($placeholders)
              " . $query_pendentes . " " . $condicao_modalidade;

        $stmt = $this->conn->prepare($query);

        // Bind dos valores
        $stmt->bindValue(1, $id, PDO::PARAM_INT);
        $stmt->bindValue(2, $faixa_etaria, PDO::PARAM_STR);

        $paramIndex = 3;
        foreach ($faixasDoGrupo as $faixaIndividual) {
            $stmt->bindValue($paramIndex++, $faixaIndividual, PDO::PARAM_STR);
        }

        try {
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_OBJ);
            return $resultado->quantidade;
        } catch (Exception $e) {
            error_log("Erro na contagem de categoria: " . $e->getMessage());
            return 0;
        }
    }

    //**************DELEÇÂO DE EVENTO */
    /**
     * Deleta um evento e seus arquivos associados
     * @param int $idEvento ID do evento a ser deletado
     * @return bool True se a exclusão foi bem-sucedida, false caso contrário
     * @throws Exception Em caso de erro grave
     */
    public function deletarEvento($idEvento)
    {
        validarPermissaoAdmin();
        // Primeiro obtemos os dados do evento para deletar os arquivos
        $evento = $this->getById($idEvento);

        if (!$evento) {
            throw new Exception("Evento não encontrado");
        }

        // Inicia transação para garantir atomicidade
        $this->conn->beginTransaction();

        try {
            // 1. Deletar arquivos associados
            $this->deletarArquivosEvento($evento);

            // 2. Deletar todas as inscrições relacionadas ao evento
            $this->deletarInscricoesEvento($idEvento);

            // 3. Deletar o evento do banco de dados
            $query = "DELETE FROM evento WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id', $idEvento, PDO::PARAM_INT);
            $stmt->execute();

            // Confirma a transação
            $this->conn->commit();

            return true;

        } catch (Exception $e) {
            // Reverte em caso de erro
            $this->conn->rollBack();
            error_log("Erro ao deletar evento ID {$idEvento}: " . $e->getMessage());
            throw new Exception("Erro ao deletar evento");
        }
    }

    /**
     * Método auxiliar para deletar arquivos associados ao evento
     * @param object $evento Objeto evento com propriedades imagen e doc
     */
    private function deletarArquivosEvento($evento)
    {
        validarPermissaoAdmin();
        $diretorios = [
            'imagens' => __DIR__ . '/../uploads/',
            'documentos' => __DIR__ . '/../docs/'
        ];

        foreach ($diretorios as $tipo => $caminho) {
            $arquivo = $tipo === 'imagens' ? $evento->imagen :
                ($tipo === 'documentos' ? ($evento->doc ?? $evento->chaveamento) : null);

            if (!empty($arquivo)) {
                $caminhoCompleto = $caminho . $arquivo;
                if (file_exists($caminhoCompleto)) {
                    if (!unlink($caminhoCompleto)) {
                        error_log("Falha ao deletar {$tipo} do evento ID {$evento->id}");
                    }
                }
            }
        }
    }

    /**
     * Método auxiliar para deletar um único arquivo associados ao evento
     * @param int $evento id do evento com propriedades imagen e doc
     * @param string $tipo tipo de documento a se deletado: imagen, doc, chaveamento
     * @param string $arquivo documento a ser
     */
    public function deletarArquivo($id, $tipo, $arquivo)
    {
        $diretorios = [
            'imagen' => __DIR__ . '/../uploads/',
            'doc' => __DIR__ . '/../docs/',
            'chaveamento' => __DIR__ . '/../docs/'
        ];
        $tabela = $tipo === 'imagen' ? 'imagen' : ($tipo === 'doc' ? 'doc' : 'chaveamento');
        $caminho = $diretorios[$tipo] . $arquivo;
        try {
            $query = "UPDATE evento SET {$tabela} = NULL WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id", $id, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                throw new Exception("Falha ao atualizar banco de dados");
            }
            //apos deletar no BD deletar o arquivo
            if (file_exists($caminho)) {
                throw new Exception("Falha ao deletar arquivo físico");
            } else {
                // Arquivo não existe fisicamente, mas continuamos (já removemos do BD)
                error_log("Arquivo físico não encontrado: {$caminho}");
            }

            return true;

        } catch (Exception $e) {
            error_log("Erro ao deletar arquivo: " . $e->getMessage());
            throw new Exception("Erro ao deletar arquivo: " . $e->getMessage());
        }
    }

    /**
     * Deleta todas as inscrições relacionadas ao evento
     * @param int $idEvento ID do evento
     */
    // Adicione no arquivo eventosServices.php, dentro da classe eventosService

    /**
     * Deleta todas as inscrições relacionadas ao evento e cobranças pendentes no Asaas
     * @param int $idEvento ID do evento
     */
    private function deletarInscricoesEvento($idEvento)
    {
        require_once __DIR__ . "/../classes/atletaClass.php";
        require_once __DIR__ . "/../classes/AssasService.php";
        try {
            // Primeiro obtemos todas as inscrições para verificar cobranças pendentes
            $query = "SELECT id_atleta, id_evento, id_cobranca_asaas, status_pagamento 
                  FROM inscricao 
                  WHERE id_evento = :id_evento";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id_evento', $idEvento, PDO::PARAM_INT);
            $stmt->execute();
            $inscricoes = $stmt->fetchAll(PDO::FETCH_OBJ);

            // Inicializa o serviço Asaas
            $conn = new Conexao();
            $asaasService = new AssasService($conn);

            foreach ($inscricoes as $inscricao) {
                // Se houver cobrança e o status for PENDENTE, deleta no Asaas
                if (!empty($inscricao->id_cobranca_asaas)) {
                    try {
                        // Verifica o status antes de deletar
                        $status = $asaasService->verificarStatusCobranca($inscricao->id_cobranca_asaas);

                        if ($status['status'] === AssasService::STATUS_PENDENTE) {
                            $asaasService->deletarCobranca($inscricao->id_cobranca_asaas);
                            file_put_contents(
                                'asaas_debug.log',
                                "\nCobrança deletada - ID: " . $inscricao->id_cobranca_asaas .
                                " (Evento: $idEvento, Atleta: " . $inscricao->id_atleta . ")",
                                FILE_APPEND
                            );
                        }
                    } catch (Exception $e) {
                        // Loga o erro mas continua o processo
                        file_put_contents(
                            'asaas_error.log',
                            "\nERRO ao deletar cobrança - ID: " . $inscricao->id_cobranca_asaas .
                            "\nMensagem: " . $e->getMessage(),
                            FILE_APPEND
                        );
                    }
                }
            }

            // Depois de processar as cobranças, deleta todas as inscrições
            $deleteQuery = "DELETE FROM inscricao WHERE id_evento = :id_evento";
            $deleteStmt = $this->conn->prepare($deleteQuery);
            $deleteStmt->bindValue(':id_evento', $idEvento, PDO::PARAM_INT);
            $deleteStmt->execute();

        } catch (Exception $e) {
            error_log("Erro ao deletar inscrições do evento ID {$idEvento}: " . $e->getMessage());
            throw new Exception("Erro ao deletar inscrições do evento");
        }
    }
}
?>