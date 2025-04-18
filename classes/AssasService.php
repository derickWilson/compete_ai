<?php
require_once(__DIR__ . '/../config_asaas.php');
use GuzzleHttp\Client;

class AsaasService {
    private $client;

    public function __construct() {
        $this->client = new Client([
            'base_uri' => ASAAS_API_URL,
            'headers' => [
                'accept' => 'application/json',
                'access_token' => ASAAS_TOKEN,
                'content-type' => 'application/json'
            ]
        ]);
    }

    public function criarCliente($dados) {
        try {
            $res = $this->client->post('customers', [
                'json' => $dados
            ]);
            return json_decode($res->getBody(), true);
        } catch (Exception $e) {
            return ['erro' => $e->getMessage()];
        }
    }

    public function criarCobranca($dados) {
        try {
            $res = $this->client->post('payments', [
                'json' => $dados
            ]);
            return json_decode($res->getBody(), true);
        } catch (Exception $e) {
            return ['erro' => $e->getMessage()];
        }
    }
}