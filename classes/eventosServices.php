<?php
require_once __DIR__ . "/../func/database.php";
require_once __DIR__ . "/../classes/eventoClass.php";
require_once __DIR__ . "/../func/calcularIdade.php";

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
        $query = "INSERT INTO evento (nome, descricao, data_limite, data_evento, local_evento, tipo_com, tipo_sem, imagen, preco, preco_menor, preco_abs, doc, normal, normal_preco)
    VALUES(:nome, :descricao, :data_limite, :data_evento, :local_camp, :tipoCom, :tipoSem, :img, :preco, :preco_menor, :preco_abs, :doc, :normal, :normal_preco)";
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
            preco_abs = :preco_abs,
            preco_menor = :preco_menor,
            doc = :doc,
            normal = :normal,
            normal_preco = :normal_preco
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
        $stmt->bindValue(':doc', $this->evento->__get('doc'), PDO::PARAM_STR);
        $stmt->bindValue(':normal', $this->evento->__get('normal'), PDO::PARAM_STR);
        $stmt->bindValue(':normal_preco', $this->evento->__get('normal_preco'), PDO::PARAM_STR);

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
        //$query = "SELECT id, nome FROM evento WHERE data_evento >= CURRENT_DATE";
        $query = "SELECT id, nome, imagen, normal FROM evento WHERE data_evento >= CURRENT_DATE";
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
                    imagen, 
                    doc,
                    normal AS normal,
                    normal_preco
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
        $query = "SELECT e.nome AS evento, e.id AS ide, a.nome AS inscrito, a.data_nascimento, a.id,
                a.faixa, a.peso, f.nome as academia, f.id as idAcademia,
                i.mod_com, i.mod_sem, i.mod_ab_com, i.mod_ab_sem, i.modalidade as modalidade,
                i.status_pagamento, i.id_cobranca_asaas, i.valor_pago
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
    //inscrever um atleta em um evento
    public function inscrever($id_atleta, $id_evento, $mod_com, $mod_abs_com, $mod_sem, $mod_abs_sem, $modalidade, $aceite_regulamento, $aceite_responsabilidade)
    {
        try {
            $query = "INSERT INTO inscricao (
                        id_atleta, 
                        id_evento, 
                        mod_com, 
                        mod_sem, 
                        mod_ab_com, 
                        mod_ab_sem, 
                        modalidade,
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
            $stmt->bindValue(':aceite_regulamento', $aceite_regulamento, PDO::PARAM_BOOL);
            $stmt->bindValue(':aceite_responsabilidade', $aceite_responsabilidade, PDO::PARAM_BOOL);

            $result = $stmt->execute();

            return $result;

        } catch (PDOException $e) {
            error_log("Erro ao inscrever atleta: " . $e->getMessage());
            return false;
        }
    }
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
    // Altere para 1 dia ao invés de 7 dias, se quiser limpeza mais frequente
    if (!$this->conn) {
        throw new Exception("Conexão com o banco de dados não disponível");
    }
    $query = "SELECT id, nome, data_limite FROM evento 
          WHERE data_limite < DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
          AND data_limite IS NOT NULL";
    $stmt = $this->conn->prepare($query);

    try {
        $stmt->execute();
        $eventosParaDeletar = $stmt->fetchAll(PDO::FETCH_OBJ); // ← CORREÇÃO AQUI

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

    //**************DELEÇÂO DE EVENTO */
    /**
     * Deleta um evento e seus arquivos associados
     * @param int $idEvento ID do evento a ser deletado
     * @return bool True se a exclusão foi bem-sucedida, false caso contrário
     * @throws Exception Em caso de erro grave
     */
    public function deletarEvento($idEvento)
    {
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
        $diretorios = [
            'imagens' => __DIR__ . '/../../uploads/',
            'documentos' => __DIR__ . '/../../docs/'
        ];

        foreach ($diretorios as $tipo => $caminho) {
            $arquivo = $tipo === 'imagens' ? $evento->imagen : $evento->doc;
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