<?php
define($URL,"https://api.asaas.com/");
define($TOKEN, "");
//criar cliente
require_once('vendor/autoload.php');

$client = new \GuzzleHttp\Client();

$response = $client->request('POST', 'https://api-sandbox.asaas.com/v3/customers', [
  'body' => '{"name":"'.$nome.'",
  "cpfCnpj":"222222",
  "email":'.$email.',
  "phone":"5555555",
  "externalReference":"2",
  "notificationDisabled":false,
  "company":"academia"}',
  'headers' => [
    'accept' => 'application/json',
    'access_token' => $TOKEN,
    'content-type' => 'application/json',
  ],
]);

echo $response->getBody();

//criar cobrança
$curl = curl_init();

curl_setopt_array($curl, [
  CURLOPT_URL => "https://api-sandbox.asaas.com/v3/payments",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => json_encode([
    'billingType' => 'PIX',
    'customer' => 'cus_1',
    'value' => 10,
    'externalReference' => 'campeonato_1',
    'description' => 'campeonado: blablabla'
  ]),
  CURLOPT_HTTPHEADER => [
    "accept: application/json",
    "access_token: toke_BLABLABLA",
    "content-type: application/json"
  ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  echo $response;
}
?>