<?php
require_once __DIR__ . '/config_asaas.php';
require_once __DIR__ . "/../func/database.php";

class AsaasService {
    private $apiKey;
    private $baseUrl;
    private $timeout = 30;

    private $conn;
    public function __construct(Conexao $conn, $baseUrl = 'https://api.asaas.com/v3') {
        $this->apiKey = ASAAS_TOKEN;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->conn = $conn->conectar();
    }

    // Métodos principais da API Asaas
    public function criarCliente(array $dadosCliente) {
        $required = ['name', 'cpfCnpj', 'email'];
        $this->validateFields($dadosCliente, $required);
        
        return $this->sendRequest('POST', '/customers', $dadosCliente);
    }

    public function buscarCliente($clienteId) {
        return $this->sendRequest('GET', '/customers/' . $clienteId);
    }

    public function buscarClientePorCpfCnpj($cpfCnpj) {
        $cpfLimpo = clearNumber($cpfCnpj);
        return $this->sendRequest('GET', '/customers?cpfCnpj=' . $cpfLimpo);
    }

    public function criarCobranca(array $dadosCobranca) {
        $required = ['customer', 'value', 'dueDate'];
        $this->validateFields($dadosCobranca, $required);
        
        return $this->sendRequest('POST', '/payments', $dadosCobranca);
    }

    public function buscarCobranca($cobrancaId) {
        return $this->sendRequest('GET', '/payments/' . $cobrancaId);
    }

    public function listarCobrancas($filtros = []) {
        $query = !empty($filtros) ? '?' . http_build_query($filtros) : '';
        return $this->sendRequest('GET', '/payments' . $query);
    }

    // Método base para requisições
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

    // Validação de campos obrigatórios
    private function validateFields($data, $requiredFields) {
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("O campo '$field' é obrigatório");
            }
        }
    }

    // Constantes para status
    const STATUS_PENDENTE = 'PENDING';
    const STATUS_PAGO = 'RECEIVED';
    const STATUS_CONFIRMADO = 'CONFIRMED';
    
    // Tradução de status
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
            
            $stmt->execute(); // Executa sem retorno
        } catch (Exception $e) {
            error_log("Erro ao atualizar inscrição: " . $e->getMessage());
            throw new Exception("Falha ao atualizar inscrição com dados de pagamento");
        }
    }
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

    function clearNumber($str) {
        return preg_replace('/\D/', '', $str);
    }
}
?>