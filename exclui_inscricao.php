<?php
session_start();
if (!isset($_SESSION["logado"]) || !$_SESSION["logado"]) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET["id"])) {
    header("Location: eventos_cadastrados.php");
    exit();
}

require_once "classes/atletaService.php";
require_once "classes/AsaasService.php";
require_once "func/clearWord.php";
require_once "func/database.php";

try {
    $conn = new Conexao();
    $at = new Atleta();
    $atserv = new atletaService($conn, $at);
    $asaas = new AsaasService($conn);
} catch (\Throwable $th) {
    die('Erro ao iniciar serviços: ' . $th->getMessage());
}

// Pega dados do atleta e evento
$evento = cleanWords($_GET["id"]);
$atleta = $_SESSION["id"];

// Busca cobrança vinculada (se houver)
$inscricao = $atserv->getInscricao($evento, $atleta); // Você precisa ter esse método!
if ($inscricao && !empty($inscricao->id_cobranca_asaas)) {
    try {
        $asaasId = $inscricao->id_cobranca_asaas;

        // Envia DELETE para o Asaas
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.asaas.com/v3/payments/" . $asaasId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "DELETE",
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "access_token: " . ASAAS_TOKEN // Certifique-se de definir o token corretamente
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            error_log("Erro ao deletar cobrança no Asaas: $err");
        } else {
            $res = json_decode($response, true);
            if (isset($res['deleted']) && $res['deleted'] === true) {
                // Registro removido do Asaas com sucesso
            } else {
                error_log("Falha ao remover cobrança Asaas: " . $response);
            }
        }
    } catch (Exception $e) {
        error_log("Erro ao deletar cobrança: " . $e->getMessage());
    }
}

// Exclui inscrição do banco
$atserv->excluirInscricao($evento, $atleta);

// Redireciona de volta
header("Location: eventos_cadastrados.php");
exit();
?>
