<?php
require_once(__DIR__ . '/../config/asaas_config.php');

class AsaasService {

    private $apiUrl;
    private $token;

    // Construtor que inicializa a URL da API e o token da chave de acesso
    public function __construct($apiUrl = ASAAS_API_URL, $token = ASAAS_TOKEN) {
        $this->apiUrl = $apiUrl; // URL da API do Asaas
        $this->token = $token; // Token da API do Asaas
    }

    // Função para enviar a requisição para a API
    private function enviarRequisicao($endpoint, $metodo = 'GET', $dados = null) {
        $curl = curl_init();

        $url = $this->apiUrl . $endpoint; // Montando a URL da requisição

        $opcoes = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $metodo,
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "access_token: " . $this->token, // Adicionando o token no cabeçalho
                "content-type: application/json"
            ],
        ];

        // Adiciona os dados se houverem (POST, PUT)
        if ($dados) {
            $opcoes[CURLOPT_POSTFIELDS] = json_encode($dados);
        }

        curl_setopt_array($curl, $opcoes);

        $resposta = curl_exec($curl); // Executa a requisição
        $erro = curl_error($curl); // Verifica se houve erro

        curl_close($curl);

        if ($erro) {
            return ['erro' => $erro]; // Retorna o erro caso tenha ocorrido
        }

        return json_decode($resposta, true); // Retorna a resposta em formato JSON
    }

    // Função para criar um novo cliente
    public function criarCliente($dados) {
        return $this->enviarRequisicao("customers", "POST", $dados);
    }

    // Função para criar uma nova cobrança
    public function criarCobranca($dados) {
        return $this->enviarRequisicao("payments", "POST", $dados);
    }

    // Função para listar os clientes, com a possibilidade de adicionar filtros
    public function listarClientes($filtros = []) {
        $query = http_build_query($filtros); // Converte o array de filtros em query string
        return $this->enviarRequisicao("customers?" . $query);
    }

    // Função para listar as cobranças, com a possibilidade de adicionar filtros
    public function listarCobrancas($filtros = []) {
        $query = http_build_query($filtros); // Converte o array de filtros em query string
        return $this->enviarRequisicao("payments?" . $query);
    }

    // Função para atualizar uma cobrança existente
    public function atualizarCobranca($id, $dados) {
        return $this->enviarRequisicao("payments/{$id}", "PUT", $dados);
    }

    // Função para buscar os dados de um único cliente
    public function buscarCliente($id) {
        return $this->enviarRequisicao("customers/{$id}");
    }

    // Métodos para atualizar a URL e o Token caso necessário
    public function setApiUrl($url) {
        $this->apiUrl = $url;
    }

    public function setToken($token) {
        $this->token = $token;
    }
}
?>