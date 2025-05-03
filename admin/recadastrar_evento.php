<?php
session_start();
require "../func/is_adm.php";
is_adm();

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    $_SESSION['mensagem'] = "Método de requisição inválido";
    header("Location: /eventos.php");
    exit();
}

require_once "../classes/eventosServices.php";
include "../func/clearWord.php";

// Verificação do ID do evento
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    $_SESSION['mensagem'] = "ID do evento inválido";
    header("Location: /eventos.php");
    exit();
}

$conn = new Conexao();
$evento = new Evento();
$eventoService = new eventosService($conn, $evento);

$id = (int) cleanWords($_POST['id']);
$eventoAntigo = $eventoService->getById($id);

if (!$eventoAntigo || !isset($eventoAntigo->id)) {
    $_SESSION['mensagem'] = "Evento não encontrado";
    header("Location: /eventos.php");
    exit();
}

// Garantir que todas as propriedades necessárias existam
$propriedadesNecessarias = ['imagen', 'doc', 'nome', 'local_evento', 'data_evento', 
                          'descricao', 'data_limite', 'tipo_com', 'tipo_sem', 
                          'preco', 'preco_menor', 'preco_abs'];

foreach ($propriedadesNecessarias as $prop) {
    if (!property_exists($eventoAntigo, $prop)) {
        $eventoAntigo->$prop = null;
    }
}

// Processar dados do formulário com valores padrão
$dadosEvento = [
    'nome' => isset($_POST['nome_evento']) ? cleanWords($_POST['nome_evento']) : ($eventoAntigo->nome ?? ''),
    'local_camp' => isset($_POST['local_camp']) ? cleanWords($_POST['local_camp']) : ($eventoAntigo->local_evento ?? ''),
    'data_camp' => isset($_POST['data_camp']) ? cleanWords($_POST['data_camp']) : ($eventoAntigo->data_evento ?? ''),
    'descricao' => isset($_POST['desc_Evento']) ? cleanWords($_POST['desc_Evento']) : ($eventoAntigo->descricao ?? ''),
    'data_limite' => isset($_POST['data_limite']) ? cleanWords($_POST['data_limite']) : ($eventoAntigo->data_limite ?? ''),
    'tipoCom' => isset($_POST['tipo_com']) ? 1 : ($eventoAntigo->tipo_com ?? 0),
    'tipoSem' => isset($_POST['tipo_sem']) ? 1 : ($eventoAntigo->tipo_sem ?? 0),
    'preco' => isset($_POST['preco']) ? (float) str_replace(',', '.', cleanWords($_POST['preco'])) : ($eventoAntigo->preco ?? 0),
    'preco_menor' => isset($_POST['preco_menor']) ? (float) str_replace(',', '.', cleanWords($_POST['preco_menor'])) : ($eventoAntigo->preco_menor ?? 0),
    'preco_abs' => isset($_POST['preco_abs']) ? (float) str_replace(',', '.', cleanWords($_POST['preco_abs'])) : ($eventoAntigo->preco_abs ?? 0),
    'img' => $eventoAntigo->imagen ?? null,
    'doc' => $eventoAntigo->doc ?? null
];

// Processar uploads (código mantido igual à versão anterior)
// ... [código de upload de imagens e documentos] ...

// Atualizar evento
try {
    foreach ($dadosEvento as $key => $value) {
        $evento->__set($key, $value);
    }
    $evento->__set('id', $id);
    
    $eventoService->editEvento();
    $_SESSION['mensagem'] = "Evento atualizado com sucesso!";
    header("Location: /eventos.php?id=" . $id);
    exit();
} catch (Exception $e) {
    error_log("Erro ao atualizar evento: " . $e->getMessage());
    $_SESSION['mensagem'] = "Erro ao atualizar evento. Por favor, tente novamente.";
    header("Location: /admin/editar_evento.php?id=" . $id);
    exit();
}
?>