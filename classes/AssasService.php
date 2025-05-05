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
    const STATUS_GRATUITO = 'GRATUITO';


    // Token
    private const ASAAS_TOKEN = '';
//    private const ASAAS_TOKEN = '';


    public function __construct(Conexao $conn, $baseUrl = 'https://api.asaas.com/v3') {
        $this->apiKey = self::ASAAS_TOKEN;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->conn = $conn->conectar();
    }
    public function buscarOuCriarCliente($dadosAtleta) {
        // Validação dos dados mínimos necessários
        $camposObrigatorios = ['nome', 'cpf', 'email', 'fone'];
        foreach ($camposObrigatorios as $campo) {
            if (empty($dadosAtleta[$campo])) {
                throw new Exception("Campo obrigatório '$campo' não informado");
            }
        }
    
        // Formata CPF (apenas números)
        $cpf = preg_replace('/[^0-9]/', '', $dadosAtleta['cpf']);
        if (strlen($cpf) != 11) {
            throw new Exception("CPF inválido");
        }
    
        try {
            // Tenta buscar o cliente existente
            $cliente = $this->buscarClientePorCpf($cpf);
            
            // Verifica se os dados básicos coincidem
            $this->verificarDadosCliente($cliente, $dadosAtleta);
            
            return $cliente['id'];
            
        } catch (Exception $e) {
            // Se o erro for 404 (não encontrado), cria novo cliente
            if ($e->getCode() == 404 || strpos($e->getMessage(), 'Not Found') !== false) {
                file_put_contents('asaas_debug.log', 
                    "\nCriando novo cliente para CPF: $cpf",
                    FILE_APPEND
                );
                
                return $this->criarNovoCliente($dadosAtleta);
            }
            
            // Log de erro e relança exceção
            file_put_contents('asaas_debug.log', 
                "\nERRO ao buscar cliente - CPF: $cpf" .
                "\nMensagem: " . $e->getMessage(),
                FILE_APPEND
            );
            throw $e;
        }
    }
    
    private function buscarClientePorCpf($cpf) {
        $url = $this->baseUrl . '/customers?cpfCnpj=' . urlencode($cpf);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'access_token: ' . $this->apiKey,
                'Content-Type: application/json',
                'User-Agent: FPJJI'
            ],
            CURLOPT_FAILONERROR => false,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Erro na requisição CURL: ' . $error);
        }
        
        $body = json_decode($response, true);
        
        if ($httpCode == 200 && !empty($body['data'])) {
            return $body['data'][0];
        }
        
        if ($httpCode == 404 || empty($body['data'])) {
            throw new Exception('Cliente não encontrado', 404);
        }
        
        if (!empty($body['errors'])) {
            throw new Exception($body['errors'][0]['description'], $httpCode);
        }
        
        throw new Exception('Erro desconhecido na API Asaas', $httpCode);
    }
    
    private function verificarDadosCliente($clienteAsaas, $dadosLocais) {
        $dadosDivergentes = [];
        
        $nomeAsaas = strtolower(trim($clienteAsaas['name']));
        $nomeLocal = strtolower(trim($dadosLocais['nome']));
        $emailAsaas = strtolower(trim($clienteAsaas['email']));
        $emailLocal = strtolower(trim($dadosLocais['email']));
        
        if ($nomeAsaas != $nomeLocal) {
            $dadosDivergentes[] = 'nome';
        }
        if ($emailAsaas != $emailLocal) {
            $dadosDivergentes[] = 'email';
        }
        
        if (!empty($dadosDivergentes)) {
            file_put_contents('asaas_debug.log', 
                "\nAVISO: Dados divergentes para cliente existente - ID: {$clienteAsaas['id']}" .
                "\nCampos divergentes: " . implode(', ', $dadosDivergentes),
                FILE_APPEND
            );
        }
    }
    
    private function criarNovoCliente($dadosAtleta) {
        $url = $this->baseUrl . '/customers';
        
        $payload = [
            'name' => substr($dadosAtleta['nome'], 0, 80),
            'cpfCnpj' => preg_replace('/[^0-9]/', '', $dadosAtleta['cpf']),
            'email' => filter_var($dadosAtleta['email'], FILTER_SANITIZE_EMAIL),
            'mobilePhone' => preg_replace('/[^0-9]/', '', $dadosAtleta['fone']),
            'externalReference' => 'AT_' . $dadosAtleta['id'],
            'notificationDisabled' => false
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'access_token: ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: FPJJI'
            ],
            CURLOPT_FAILONERROR => false,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Erro na conexão: ' . $error);
        }
        
        $body = json_decode($response, true);
        
        if ($httpCode == 200 && !empty($body['id'])) {
            return $body['id'];
        }
        
        if (!empty($body['errors'])) {
            throw new Exception('Erro na API Asaas: ' . implode(', ', $body['errors']), $httpCode);
        }
        
        throw new Exception('Erro ao criar cliente. HTTP Code: ' . $httpCode, $httpCode);
    }
    
    public function criarCobranca(array $dadosCobranca) {
        $required = ['customer', 'value', 'dueDate', 'description'];
        $this->validateFields($dadosCobranca, $required);
    
        $payload = [
            'customer' => $dadosCobranca['customer'],
            'billingType' => 'PIX',
            'value' => number_format((float)$dadosCobranca['value'], 2, '.', ''),
            'dueDate' => date('Y-m-d', strtotime($dadosCobranca['dueDate'])),
            'description' => $dadosCobranca['description'],
            'externalReference' => $dadosCobranca['externalReference'] ?? null
        ];
    
        $payload = array_filter($payload, function($value) {
            return $value !== null;
        });
    
        $resposta = $this->sendRequest('POST', '/payments', $payload);
    
        return [
            'success' => true,
            'payment' => [
                'id' => $resposta['id'],
                'status' => $resposta['status'],
                'value' => $resposta['value'],
                'netValue' => $resposta['netValue'],
                'dueDate' => $resposta['dueDate'],
                'invoiceUrl' => $resposta['invoiceUrl']
            ]
        ];
    }
    
    public function atualizarInscricaoComPagamento($atletaId, $eventoId, $cobrancaId, $status, $valor) {
        try {
            // Converte o valor para garantir formato numérico
            $valorNumerico = (float) number_format($valor, 2, '.', '');
            
            $query = "UPDATE inscricao SET 
                        id_cobranca_asaas = :cobranca_id,
                        valor_pago = :valor,
                        status_pagamento = :status
                      WHERE id_atleta = :atleta_id AND id_evento = :evento_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':cobranca_id', $cobrancaId, PDO::PARAM_STR);
            $stmt->bindValue(':valor', $valorNumerico, PDO::PARAM_STR);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(':atleta_id', $atletaId, PDO::PARAM_INT);
            $stmt->bindValue(':evento_id', $eventoId, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Erro ao atualizar inscrição: " . $errorInfo[2]);
            }
            
            // Verificação para confirmar que os dados foram atualizados
            $verifica = $this->conn->prepare(
                "SELECT valor_pago FROM inscricao 
                 WHERE id_atleta = :atleta_id AND id_evento = :evento_id"
            );
            $verifica->bindValue(':atleta_id', $atletaId, PDO::PARAM_INT);
            $verifica->bindValue(':evento_id', $eventoId, PDO::PARAM_INT);
            $verifica->execute();
            
            $resultado = $verifica->fetch(PDO::FETCH_ASSOC);
            
            if ($resultado['valor_pago'] === null) {
                throw new Exception("O valor pago não foi armazenado corretamente");
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("ERRO ATUALIZAÇÃO: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function verificarStatusCobranca($cobrancaId) {
        try {
            // Validação do ID da cobrança
            if (empty($cobrancaId)) {
                throw new InvalidArgumentException("ID da cobrança não pode ser vazio");
            }
    
            // Faz a requisição à API Asaas
            $response = $this->sendRequest('GET', '/payments/' . $cobrancaId);
    
            // Mapeamento de status para português
            $statusMap = [
                'PENDING' => 'PENDENTE',
                'RECEIVED' => 'PAGO',
                'CONFIRMED' => 'CONFIRMADO',
                'OVERDUE' => 'VENCIDO',
                'REFUNDED' => 'REEMBOLSADO',
                'RECEIVED_IN_CASH' => 'RECEBIDO EM DINHEIRO',
                'REFUND_REQUESTED' => 'SOLICITADO REEMBOLSO',
                'CHARGEBACK_REQUESTED' => 'SOLICITADO ESTORNO',
                'CHARGEBACK_DISPUTE' => 'EM DISPUTA',
                'AWAITING_CHARGEBACK_REVERSAL' => 'AGUARDANDO REVERSÃO',
                'DUNNING_REQUESTED' => 'EM COBRANÇA',
                'DUNNING_RECEIVED' => 'COBRANÇA RECEBIDA',
                'AWAITING_RISK_ANALYSIS' => 'AGUARDANDO ANÁLISE'
            ];
    
            // Verifica se o status existe no mapeamento
            $status = $response['status'] ?? 'UNKNOWN';
            $statusTraduzido = $statusMap[$status] ?? $status;
    
            return [
                'status' => $status,
                'traduzido' => $statusTraduzido,
                'detalhes' => $response
            ];
    
        } catch (Exception $e) {
            error_log("Erro ao verificar status da cobrança: " . $e->getMessage());
            throw new Exception("Não foi possível verificar o status da cobrança: " . $e->getMessage());
        }
    }

    public function buscarCobrancaCompleta($cobrancaId) {
        try {
            $response = $this->sendRequest('GET', '/payments/' . $cobrancaId);
            
            // Se for PIX, busca informações adicionais
            if ($response['billingType'] === 'PIX' && $response['status'] === 'PENDING') {
                $pixInfo = $this->buscarQrCodePix($cobrancaId);
                $response['pix'] = $pixInfo;
            }
            
            return [
                'success' => true,
                'payment' => $response
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function buscarQrCodePix($cobrancaId) {
        $response = $this->sendRequest('GET', '/payments/' . $cobrancaId . '/pixQrCode');
        return [
            'encodedImage' => $response['encodedImage'],
            'payload' => $response['payload'],
            'expirationDate' => $response['expirationDate']
        ];
    }

    public function deletarCobranca($cobrancaId) {
        try {
            $response = $this->sendRequest('DELETE', '/payments/' . $cobrancaId);
            
            return [
                'deleted' => true,
                'response' => $response
            ];
        } catch (Exception $e) {
            if (strpos($e->getMessage(), '404') !== false) {
                // Cobrança já não existe
                return ['deleted' => true];
            }
            
            error_log("Erro ao deletar cobrança $cobrancaId: " . $e->getMessage());
            throw new Exception("Não foi possível deletar a cobrança: " . $e->getMessage());
        }
    }
/**
 * Edita uma cobrança existente no Asaas
 * 
 * @param string $cobrancaId ID da cobrança no Asaas (ex: "pay_b6bib9x9v8jdgmmf")
 * @param array $dadosAtualizados Array com os campos a serem atualizados:
 *   - 'value' => (float) Novo valor da cobrança
 *   - 'dueDate' => (string) Nova data de vencimento (formato YYYY-MM-DD)
 *   - 'description' => (string) Nova descrição (opcional)
 * @return array Retorna a resposta da API com os dados atualizados
 * @throws Exception Em caso de erro na requisição
 */
public function editarCobranca($cobrancaId, $dadosAtualizados) {
    try {
        // Validação dos parâmetros
        if (empty($cobrancaId)) {
            throw new InvalidArgumentException("ID da cobrança não pode ser vazio");
        }
        
        if (!isset($dadosAtualizados['value']) && !isset($dadosAtualizados['dueDate'])) {
            throw new InvalidArgumentException("Pelo menos um dos campos (value ou dueDate) deve ser fornecido");
        }
        
        // Preparar payload com apenas os campos fornecidos
        $payload = [
            'billingType' => 'PIX' // Mantém sempre PIX conforme exemplos
        ];
        
        if (isset($dadosAtualizados['value'])) {
            $payload['value'] = (float) $dadosAtualizados['value'];
        }
        
        if (isset($dadosAtualizados['dueDate'])) {
            $payload['dueDate'] = $dadosAtualizados['dueDate'];
        }
        
        if (isset($dadosAtualizados['description'])) {
            $payload['description'] = substr($dadosAtualizados['description'], 0, 255);
        }
        
        // Fazer a requisição PUT para atualizar a cobrança
        $response = $this->sendRequest('PUT', '/payments/' . $cobrancaId, $payload);
        
        // Retornar dados formatados
        return [
            'success' => true,
            'payment' => [
                'id' => $response['id'],
                'status' => $response['status'],
                'value' => $response['value'],
                'dueDate' => $response['dueDate'],
                'description' => $response['description'] ?? null,
                'invoiceUrl' => $response['invoiceUrl']
            ]
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao editar cobrança: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'code' => $e->getCode()
        ];
    }
}
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
                "content-type: application/json",
                "User-Agent: FPJJI"
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
    
    private function validateFields($data, $requiredFields) {
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("O campo '$field' é obrigatório");
            }
        }
    }
    
    public function clearNumber($str) {
        return preg_replace('/\D/', '', $str);
    }
}
?>
