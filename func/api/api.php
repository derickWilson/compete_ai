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
?>