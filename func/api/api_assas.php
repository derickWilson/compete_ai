<?php
function buscarClienteAsaas($cpf) {
    $TOKEN = 'SEU_TOKEN_AQUI';
    $url = "https://api-sandbox.asaas.com/v3/customers?cpfCnpj=$cpf";
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "accept: application/json",
            "access_token: $TOKEN"
        ]
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    $res = json_decode($response, true);
    return $res["data"][0]["id"] ?? false;
}

function criarClienteAsaas($nome, $cpf, $email, $fone, $referencia, $grupo = null) {
    $TOKEN = 'SEU_TOKEN_AQUI';
    $url = "https://api-sandbox.asaas.com/v3/customers";
    $body = [
        "name" => $nome,
        "cpfCnpj" => $cpf,
        "email" => $email,
        "phone" => $fone,
        "externalReference" => $referencia
    ];
    if ($grupo) $body["groupName"] = $grupo;
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => [
            "accept: application/json",
            "access_token: $TOKEN",
            "content-type: application/json"
        ]
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    $res = json_decode($response, true);
    return $res["id"] ?? false;
}

function criarCobrancaAsaas($customerId, $valor, $descricao, $referencia, $vencimento = null) {
    $TOKEN = 'SEU_TOKEN_AQUI';
    $url = "https://api-sandbox.asaas.com/v3/payments";
    if (!$vencimento) $vencimento = date('Y-m-d', strtotime('+5 days'));
    $body = [
        "customer" => $customerId,
        "billingType" => "PIX",
        "value" => $valor,
        "dueDate" => $vencimento,
        "description" => $descricao,
        "externalReference" => $referencia
    ];
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => [
            "accept: application/json",
            "access_token: $TOKEN",
            "content-type: application/json"
        ]
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true);
}
?>