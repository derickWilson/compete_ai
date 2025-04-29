<?php
require_once __DIR__ . '/config_asaas.php';
require_once __DIR__ . "/../func/database.php";

/**
 * Classe responsável por integrar com a API do Asaas.
 */
class AsaasService {
    private $apiKey;
    private $baseUrl;
    private $timeout = 30;
    private $conn;

    /**
     * Construtor da classe.
     *
     * @param Conexao $conn Instância de conexão com o banco de dados.
     * @param string $baseUrl URL base da API Asaas.
     */
    public function __construct(Conexao $conn, $baseUrl = 'https://api.asaas.com/v3') {
        $this->apiKey = ASAAS_TOKEN;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->conn = $conn->conectar();
    }

    /**
     * Cria um novo cliente no Asaas.
     *
     * @param array $dadosCliente Informações do cliente (name, cpfCnpj, email...).
     * @return array Resposta da API.
     */
    public function criarCliente(array $dadosCliente) {
        $required = ['name', 'cpfCnpj', 'email'];
        $this->validateFields($dadosCliente, $required);

        return $this->sendRequest('POST', '/customers', $dadosCliente);
    }

    /**
     * Busca um cliente pelo ID.
     *
     * @param string $clienteId ID do cliente no Asaas.
     * @return array Resposta da API.
     */
    public function buscarCliente($clienteId) {
        return $this->sendRequest('GET', '/customers/' . $clienteId);
    }

    /**
     * Busca cliente pelo CPF/CNPJ.
     *
     * @param string $cpfCnpj CPF ou CNPJ (será limpo).
     * @return array Resultado da API.
     */
    public function buscarClientePorCpfCnpj($cpfCnpj) {
        $cpfLimpo = $this->clearNumber($cpfCnpj);
        return $this->sendRequest('GET', '/customers?cpfCnpj=' . $cpfLimpo);
    }

    /**
     * Cria uma nova cobrança para um cliente.
     *
     * @param array $dadosCobranca Informações da cobrança.
     * @return array Resposta da API.
     */
    public function criarCobranca(array $dadosCobranca) {
        $required = ['customer', 'value', 'dueDate'];
        $this->validateFields($dadosCobranca, $required);

        return $this->sendRequest('POST', '/payments', $dadosCobranca);
    }

    /**
     * Busca uma cobrança pelo ID.
     *
     * @param string $cobrancaId ID da cobrança.
     * @return array Dados da cobrança.
     */
    public function buscarCobranca($cobrancaId) {
        return $this->sendRequest('GET', '/payments/' . $cobrancaId);
    }

    /**
     * Lista cobranças com filtros opcionais.
     *
     * @param array $filtros Filtros da query (opcional).
     * @return array Lista de cobranças.
     */
    public function listarCobrancas($filtros = []) {
        $query = !empty($filtros) ? '?' . http_build_query($filtros) : '';
        return $this->sendRequest('GET', '/payments' . $query);
    }

    /**
     * Deleta uma cobrança no Asaas.
     *
     * @param string $cobrancaId ID da cobrança a ser excluída.
     * @return array Resposta da API.
     * @throws Exception Se a exclusão falhar.
     */
    public function deletarCobranca($cobrancaId) {
        return $this->sendRequest('DELETE', '/payments/' . $cobrancaId);
    }

    /**
     * Atualiza a inscrição do atleta com dados da cobrança.
     *
     * @param int $atletaId
     * @param int $eventoId
     * @param string $cobrancaId
     * @param string $status
     * @param float $valor
     * @throws Exception Em caso de erro na atualização.
     */
    public function atualizarInscricaoComPagamento($atletaId, $eventoId, $cobrancaId, $status, $valor) {
        $query = "UPDATE inscricao SET 
                    id_cobranca_asaas = :cobranca_id,
                    status_pagamento = :status,
                    valor_pago = :valor
                  WHERE id_atleta = :atleta_id AND id_evento = :evento_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':cobranca_id', $cobrancaId);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':valor', $valor);
            $stmt->bindValue(':atleta_id', $atletaId);
            $stmt->bindValue(':evento_id', $eventoId);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Erro ao atualizar inscrição: " . $e->getMessage());
            throw new Exception("Falha ao atualizar inscrição com dados de pagamento");
        }
    }

    /**
     * Tenta buscar um cliente existente ou cria um novo.
     *
     * @param array $dadosAtleta ['nome', 'cpf', 'email', 'fone', 'id']
     * @return string ID do cliente no Asaas.
     */
    public function buscarOuCriarCliente($dadosAtleta) {
        $cpfLimpo = $this->clearNumber($dadosAtleta['cpf']);
        $foneLimpo = $this->clearNumber($dadosAtleta['fone']);

        $clientes = $this->buscarClientePorCpfCnpj($cpfLimpo);

        if (!empty($clientes['data']) && count($clientes['data']) > 0) {
            return $clientes['data'][0]['id'];
        }

        $novoCliente = $this->criarCliente([
            'name' => $dadosAtleta['nome'],
            'cpfCnpj' => $cpfLimpo,
            'email' => $dadosAtleta['email'],
            'mobilePhone' => $foneLimpo,
            'externalReference' => 'ATL_' . $dadosAtleta['id']
        ]);

        return $novoCliente['id'];
    }

    /**
     * Envia requisição HTTP para a API Asaas.
     *
     * @param string $method Método HTTP (GET, POST, DELETE, etc.)
     * @param string $endpoint Caminho da API.
     * @param array|null $data Dados a serem enviados (opcional).
     * @return array Resposta da API decodificada.
     * @throws Exception Se houver erro de conexão ou resposta inválida.
     */
    private function sendRequest($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;

        $curl = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "access_token: " . $this->apiKey,
                "content-type: application/json"
            ]
        ];

        if ($data) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($error) {
            throw new Exception("Erro na conexão: " . $error);
        }

        $result = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMsg = $result['errors'][0]['description'] ?? 'Erro desconhecido';
            throw new Exception("Erro na API Asaas: " . $errorMsg, $httpCode);
        }

        return $result;
    }

    /**
     * Valida campos obrigatórios para requisições.
     *
     * @param array $data Dados enviados.
     * @param array $requiredFields Campos obrigatórios.
     * @throws InvalidArgumentException Se algum campo estiver ausente.
     */
    private function validateFields($data, $requiredFields) {
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("O campo '$field' é obrigatório");
            }
        }
    }

    /**
     * Remove todos os caracteres não numéricos de uma string.
     *
     * @param string $str Entrada original.
     * @return string Somente números.
     */
    public function clearNumber($str) {
        return preg_replace('/\D/', '', $str);
    }

    /**
     * Traduz status da API Asaas para português.
     *
     * @param string $status Código do status.
     * @return string Status traduzido.
     */
    public function traduzirStatus($status) {
        $traducoes = [
            self::STATUS_PENDENTE => 'Pendente',
            self::STATUS_PAGO => 'Pago',
            self::STATUS_CONFIRMADO => 'Confirmado',
            'OVERDUE' => 'Atrasado',
            'REFUNDED' => 'Reembolsado'
        ];

        return $traducoes[$status] ?? $status;
    }

    // Constantes de status da cobrança
    const STATUS_PENDENTE = 'PENDING';
    const STATUS_PAGO = 'RECEIVED';
    const STATUS_CONFIRMADO = 'CONFIRMED';
}
?>