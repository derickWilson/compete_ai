<?php
require_once __DIR__ . "/../func/database.php";
class AssasService {
    private $apiKey;
    private $baseUrl;
    private $timeout = 30;
    private $conn;

    // Constantes de status
    const STATUS_PENDENTE = 'PENDING';
    const STATUS_PAGO = 'RECEIVED';
    const STATUS_CONFIRMADO = 'CONFIRMED';

    // Token de acesso (substitua pelo seu token real)
    private const ASAAS_TOKEN = 'aact_hmlg_000MzkwODA2MWY2OGM3MWRlMDU2NWM3MzJlNzZmNGZhZGY6OjlhMDQ4ODc0LTJmMjMtNDIwMC1hY2JkLTAyMTViZDdiYmZkMzo6JGFhY2hfZjExMWYyNGYtNGU5NC00ZmZiLWFmNTEtMzk2N2NjZDQwMTk2';

    public function __construct(Conexao $conn, $baseUrl = 'https://api-sandbox.asaas.com/v3') {
        $this->apiKey = self::ASAAS_TOKEN;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->conn = $conn->conectar();
    }
    /**
     * Versão otimizada com cache e tratamento de erros
     */
    public function buscarOuCriarCliente($dadosAtleta) {
        // Validação básica
        if (empty($dadosAtleta['cpf']) || empty($dadosAtleta['email'])) {
            throw new InvalidArgumentException("Dados incompletos para cadastro");
        }

        $cpfLimpo = $this->clearNumber($dadosAtleta['cpf']);

        try {
            // 1. Tenta buscar cliente existente
            $busca = $this->buscarClientePorCpfCnpj($cpfLimpo);

            if ($busca['success']) {
                return $busca['id']; // Retorna ID existente
            }

            // 2. Cria novo cliente com estrutura completa
            $novoCliente = $this->criarCliente([
                'name' => $dadosAtleta['nome'],
                'cpfCnpj' => $cpfLimpo,
                'email' => $dadosAtleta['email'],
                'phone' => $this->clearNumber($dadosAtleta['fone']),
                'company' => $dadosAtleta['academia'] ?? 'Não informado',
                'externalReference' => 'ATL_' . $dadosAtleta['id'],
                'notificationDisabled' => true
            ]);

            return $novoCliente['id'];

        } catch (Exception $e) {
            error_log("Erro ao processar cliente: " . $e->getMessage());
            throw new Exception("Falha no cadastro do cliente. Por favor, verifique os dados e tente novamente");
        }
    }
    /**
     * Cria um novo cliente no Asaas (versão simplificada)
     */
    public function criarCliente(array $dadosCliente) {
        // Campos obrigatórios mínimos
        $this->validateFields($dadosCliente, ['name', 'cpfCnpj']);

        // Padroniza os dados com valores default
        $payload = [
            'name' => $dadosCliente['name'],
            'cpfCnpj' => $this->clearNumber($dadosCliente['cpfCnpj']),
            'email' => $dadosCliente['email'] ?? '',
            'phone' => $this->clearNumber($dadosCliente['phone'] ?? ''),
            'mobilePhone' => $this->clearNumber($dadosCliente['mobilePhone'] ?? ''),
            'company' => $dadosCliente['company'] ?? '',
            'externalReference' => $dadosCliente['externalReference'] ?? ''
        ];

        return $this->sendRequest('POST', '/customers', $payload);
    }

    /**
     * Busca um cliente pelo ID
     */
    public function buscarCliente($clienteId) {
        return $this->sendRequest('GET', '/customers/' . $clienteId);
    }

    /**
     * Cria cobrança com PIX como padrão e tratamento completo da resposta
     */
    public function criarCobranca(array $dadosCobranca) {
        // Campos obrigatórios
        $required = ['customer', 'value', 'dueDate', 'description'];
        $this->validateFields($dadosCobranca, $required);

        // Formata os dados conforme a API espera
        $payload = [
            'customer' => $dadosCobranca['customer'], // ID do cliente no Asaas
            'billingType' => 'PIX', // Fixo como PIX conforme sua requisição
            'value' => number_format((float)$dadosCobranca['value'], 2, '.', ''),
            'dueDate' => date('Y-m-d', strtotime($dadosCobranca['dueDate'])),
            'description' => $dadosCobranca['description'],
            'externalReference' => $dadosCobranca['externalReference'] ?? null,
            'discount' => $dadosCobranca['discount'] ?? null,
            'fine' => $dadosCobranca['fine'] ?? null,
            'interest' => $dadosCobranca['interest'] ?? null
        ];

        // Remove valores nulos para evitar erros na API
        $payload = array_filter($payload, function($value) {
            return $value !== null;
        });

        $resposta = $this->sendRequest('POST', '/payments', $payload);

        // Padroniza a resposta
        return [
            'success' => true,
            'payment' => [
                'id' => $resposta['id'],
                'status' => $resposta['status'],
                'value' => $resposta['value'],
                'netValue' => $resposta['netValue'],
                'dueDate' => $resposta['dueDate'],
                'invoiceUrl' => $resposta['invoiceUrl'],
                'description' => $resposta['description'],
                'billingType' => $resposta['billingType'],
                'externalReference' => $resposta['externalReference']
            ]
        ];
    }
        /**
     * Verifica o status atual de uma cobrança
     * @param string $cobrancaId ID da cobrança no Asaas
     * @return array Retorna o status e informações adicionais
     * @throws Exception Em caso de erro na API
     */
    public function verificarStatusCobranca($cobrancaId) {
        if (empty($cobrancaId)) {
            throw new InvalidArgumentException("ID da cobrança é obrigatório");
        }

        $resposta = $this->sendRequest('GET', "/payments/{$cobrancaId}/status");

        return [
            'status' => $resposta['status'],
            'traduzido' => $this->traduzirStatus($resposta['status'])
        ];
    }
    public function traduzirStatus($status) {
        $traducoes = [
            'PENDING' => 'PENDENTE',
            'RECEIVED' => 'PAGO',
            'CONFIRMED' => 'CONFIRMADO',
            'OVERDUE' => 'VENCIDO',
            'REFUNDED' => 'REEMBOLSADO',
            'RECEIVED_IN_CASH' => 'RECEBIDO EM DINHEIRO',
            'REFUND_REQUESTED' => 'SOLICITADO REEMBOLSO',
            'CHARGEBACK_REQUESTED' => 'SOLICITADO CHARGEBACK',
            'CHARGEBACK_DISPUTE' => 'EM DISPUTA',
            'AWAITING_CHARGEBACK_REVERSAL' => 'AGUARDANDO REVERSÃO',
            'DUNNING_REQUESTED' => 'EM COBRANÇA',
            'DUNNING_RECEIVED' => 'COBRANÇA RECEBIDA',
            'AWAITING_RISK_ANALYSIS' => 'AGUARDANDO ANÁLISE'
        ];
    
        return $traducoes[$status] ?? $status;
    }

    /**
     * Atualiza a inscrição com dados do pagamento
     */
    public function atualizarInscricaoComPagamento($atletaId, $eventoId, $cobrancaId, $status1, $valor) {
        $query = "UPDATE inscricao SET 
                    id_cobranca_asaas = :cobranca_id,
                    status_pagamento = :status1,
                    valor_pago = :valor,
                    pago = :pago
                  WHERE id_atleta = :atleta_id AND id_evento = :evento_id";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':cobranca_id', $cobrancaId);
            $stmt->bindValue(':status', $status1);
            $stmt->bindValue(':valor', $valor);
            $stmt->bindValue(':pago', ($status1 === self::STATUS_PAGO) ? 1 : 0);
            $stmt->bindValue(':atleta_id', $atletaId);
            $stmt->bindValue(':evento_id', $eventoId);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Erro ao atualizar inscrição: " . $e->getMessage());
            throw new Exception("Falha ao atualizar inscrição com dados de pagamento");
        }
    }

    /**
     * Busca ou cria um cliente
     */
    /**
     * Busca cliente por CPF/CNPJ com tratamento completo da resposta
     */
    public function buscarClientePorCpfCnpj($cpfCnpj) {
        $cpfLimpo = $this->clearNumber($cpfCnpj);

        if (strlen($cpfLimpo) !== 11 && strlen($cpfLimpo) !== 14) {
            throw new InvalidArgumentException("Documento inválido. Use CPF (11 dígitos) ou CNPJ (14 dígitos)");
        }

        $resposta = $this->sendRequest('GET', '/customers?cpfCnpj=' . $cpfLimpo);

        // Padroniza a resposta para sempre retornar a mesma estrutura
        if (empty($resposta['data'])) {
            return [
                'success' => false,
                'message' => 'Cliente não encontrado',
                'data' => null
            ];
        }

        // Mapeia os campos relevantes
        $cliente = $resposta['data'][0];
        return [
            'success' => true,
            'data' => [
                'id' => $cliente['id'],
                'nome' => $cliente['name'],
                'email' => $cliente['email'],
                'cpfCnpj' => $cliente['cpfCnpj'],
                'telefone' => $cliente['phone'],
                'academia' => $cliente['company'], // Campo específico do seu sistema
                'referencia' => $cliente['externalReference'] // ID no seu BD
            ]
        ];
    }
    /**
 * Busca informações completas de uma cobrança (incluindo dados PIX)
 */
    public function buscarCobrancaCompleta($cobrancaId) {
        // Validação básica do ID
        if (empty($cobrancaId)) {
            throw new InvalidArgumentException("ID da cobrança é obrigatório");
        }

        try {
            // 1. Busca os dados básicos da cobrança
            $cobranca = $this->sendRequest('GET', '/payments/' . $cobrancaId);

            // 2. Busca informações específicas de pagamento (PIX)
            $billingInfo = $this->sendRequest('GET', '/payments/' . $cobrancaId . '/billingInfo');

            // 3. Formata a resposta unificada
            return [
                'success' => true,
                'payment' => [
                    'id' => $cobranca['id'],
                    'status' => $cobranca['status'],
                    'value' => $cobranca['value'],
                    'dueDate' => $cobranca['dueDate'],
                    'description' => $cobranca['description'],
                    'invoiceUrl' => $cobranca['invoiceUrl'],
                    'pix' => $billingInfo['pix'] ?? null,
                    'creditCard' => $billingInfo['creditCard'] ?? null,
                    'bankSlip' => $billingInfo['bankSlip'] ?? null
                ]
            ];

        } catch (Exception $e) {
            error_log("Erro ao buscar cobrança: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'payment' => null
            ];
        }
    }

    /**
     * Método interno para enviar requisições
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
            $errorMsg = $result['errors'][0]['description'] ?? ($result['message'] ?? 'Erro desconhecido');
            throw new Exception("Erro na API Asaas ($httpCode): " . $errorMsg);
        }

        return $result;
    }

    /**
     * Valida campos obrigatórios
     */
    private function validateFields($data, $requiredFields) {
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("O campo '$field' é obrigatório");
            }
        }
    }

    /**
     * Limpa números (remove caracteres não numéricos)
     */
    public function clearNumber($str) {
        return preg_replace('/\D/', '', $str);
    }
}
?>