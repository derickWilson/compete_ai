<?php
class AsaasService {
    private $apiKey;
    private $baseUrl;

    public function __construct($apiKey, $baseUrl = 'https://api-sandbox.asaas.com/v3') {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl;
    }

    private function sendRequest($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $curl = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
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
        curl_close($curl);
        
        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }
        
        return json_decode($response, true);
    }

    public function criarCliente(array $dadosCliente) {
        return $this->sendRequest('POST', '/customers', $dadosCliente);
    }

    public function buscarClientePorCpfCnpj($cpfCnpj) {
        return $this->sendRequest('GET', '/customers?cpfCnpj=' . $cpfCnpj);
    }

    public function criarCobranca(array $dadosCobranca) {
        return $this->sendRequest('POST', '/payments', $dadosCobranca);
    }

    public function buscarCobrancasPorCliente($customerId) {
        return $this->sendRequest('GET', '/payments?customer=' . $customerId);
    }
}