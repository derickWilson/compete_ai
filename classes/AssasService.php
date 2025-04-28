<?php
require_once(__DIR__ . '/../config/asaas_config.php');

class AsaasService {
    private string $apiUrl;
    private string $token;

    /**
     * Construtor da classe AsaasService
     * 
     * @param string $apiUrl URL da API do Asaas
     * @param string $token Token de acesso da API
     */
    public function __construct(string $apiUrl = ASAAS_API_URL, string $token = ASAAS_TOKEN) {
        $this->apiUrl = rtrim($apiUrl, '/') . '/v3/'; // Garante formato correto
        $this->token = $token;
    }

    /**
     * Envia requisição para a API Asaas
     * 
     * @param string $endpoint Endpoint da API
     * @param string $metodo Método HTTP (GET, POST, PUT, DELETE)
     * @param array|null $dados Dados a serem enviados
     * @return array Resposta da API
     * @throws Exception Em caso de erro na requisição
     */
    public function enviarRequisicao(string $endpoint, string $metodo = 'GET', ?array $dados = null): array {
        $curl = curl_init();
        $url = $this->apiUrl . ltrim($endpoint, '/');

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
                "access_token: " . $this->token,
                "content-type: application/json"
            ],
        ];

        if ($dados) {
            $opcoes[CURLOPT_POSTFIELDS] = json_encode($dados);
        }

        curl_setopt_array($curl, $opcoes);

        $resposta = curl_exec($curl);
        $erro = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($erro) {
            throw new Exception("Erro na requisição: " . $erro);
        }

        $respostaDecodificada = json_decode($resposta, true) ?? [];

        // Verifica códigos de erro HTTP
        if ($httpCode >= 400) {
            $mensagemErro = $respostaDecodificada['errors'][0]['description'] ?? 'Erro desconhecido';
            throw new Exception("Erro API Asaas ($httpCode): " . $mensagemErro);
        }

        return $respostaDecodificada;
    }

    /**
     * Cria um novo cliente no Asaas
     * 
     * @param array $dados Dados do cliente
     * @return array Resposta da API com dados do cliente criado
     */
    public function criarCliente(array $dados): array {
        $camposObrigatorios = ['name', 'cpfCnpj'];
        $this->validarCampos($dados, $camposObrigatorios);

        return $this->enviarRequisicao("customers", "POST", $dados);
    }

    /**
     * Cria uma nova cobrança
     * 
     * @param array $dados Dados da cobrança
     * @return array Resposta da API com dados da cobrança criada
     */
    public function criarCobranca(array $dados): array {
        $camposObrigatorios = ['customer', 'billingType', 'value', 'dueDate'];
        $this->validarCampos($dados, $camposObrigatorios);

        return $this->enviarRequisicao("payments", "POST", $dados);
    }

    /**
     * Lista clientes com filtros opcionais
     * 
     * @param array $filtros Filtros para a consulta
     * @return array Lista de clientes
     */
    public function listarClientes(array $filtros = []): array {
        $query = http_build_query($filtros);
        return $this->enviarRequisicao("customers?" . $query);
    }

    /**
     * Lista cobranças com filtros opcionais
     * 
     * @param array $filtros Filtros para a consulta
     * @return array Lista de cobranças
     */
    public function listarCobrancas(array $filtros = []): array {
        $query = http_build_query($filtros);
        return $this->enviarRequisicao("payments?" . $query);
    }

    /**
     * Atualiza uma cobrança existente
     * 
     * @param string $id ID da cobrança
     * @param array $dados Dados para atualização
     * @return array Resposta da API
     */
    public function atualizarCobranca(string $id, array $dados): array {
        return $this->enviarRequisicao("payments/{$id}", "PUT", $dados);
    }

    /**
     * Busca dados de um cliente específico
     * 
     * @param string $id ID do cliente
     * @return array Dados do cliente
     */
    public function buscarCliente(string $id): array {
        return $this->enviarRequisicao("customers/{$id}");
    }

    /**
     * Busca dados de uma cobrança específica
     * 
     * @param string $id ID da cobrança
     * @return array Dados da cobrança
     */
    public function buscarCobranca(string $id): array {
        return $this->enviarRequisicao("payments/{$id}");
    }

    /**
     * Remove um cliente (soft delete)
     * 
     * @param string $id ID do cliente
     * @return array Resposta da API
     */
    public function removerCliente(string $id): array {
        return $this->enviarRequisicao("customers/{$id}", "DELETE");
    }

    /**
     * Remove uma cobrança (apenas se não estiver paga)
     * 
     * @param string $id ID da cobrança
     * @return array Resposta da API
     */
    public function removerCobranca(string $id): array {
        return $this->enviarRequisicao("payments/{$id}", "DELETE");
    }

    /**
     * Valida campos obrigatórios
     * 
     * @param array $dados Dados a serem validados
     * @param array $camposObrigatorios Campos obrigatórios
     * @throws InvalidArgumentException Se algum campo obrigatório estiver faltando
     */
    private function validarCampos(array $dados, array $camposObrigatorios): void {
        foreach ($camposObrigatorios as $campo) {
            if (!isset($dados[$campo]) ){
                throw new InvalidArgumentException("Campo obrigatório faltando: {$campo}");
            }
        }
    }

    /**
     * Define a URL da API
     * 
     * @param string $url Nova URL da API
     */
    public function setApiUrl(string $url): void {
        $this->apiUrl = rtrim($url, '/') . '/v3/';
    }

    /**
     * Define o token de acesso
     * 
     * @param string $token Novo token de acesso
     */
    public function setToken(string $token): void {
        $this->token = $token;
    }
        /**
     * Atualiza um cliente existente no Asaas
     * @param string $customerId ID do cliente no Asaas
     * @param array $dados Dados do cliente para atualização
     * @return array Resposta da API
     */
    public function atualizarCliente($customerId, array $dados) {
        return $this->enviarRequisicao("customers/{$customerId}", "POST", $dados);
    }
}