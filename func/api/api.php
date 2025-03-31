<?php
//criar cliente

require_once('vendor/autoload.php');

$client = new \GuzzleHttp\Client();

$response = $client->request('POST', 'https://api-sandbox.asaas.com/v3/customers', [
  'body' => '{"name":"derick",
  "cpfCnpj":"222222",
  "email":"q@q.com",
  "phone":"5555555",
  "externalReference":"2",
  "notificationDisabled":false,
  "company":"academia"}',
  'headers' => [
    'accept' => 'application/json',
    'access_token' => 'teste',
    'content-type' => 'application/json',
  ],
]);

echo $response->getBody();
?>