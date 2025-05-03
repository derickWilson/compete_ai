<?php
// Inicia a sessão para acessar variáveis de sessão
session_start();

// Verifica se o usuário está logado, caso contrário redireciona para a página inicial
if (!isset($_SESSION["logado"])) {
    header("Location: index.php");
    exit();
}

// Inclui os arquivos necessários
require_once "classes/atletaService.php";
require_once "classes/AssasService.php";
require_once "func/database.php";

// Inicializa variáveis
$inscritos = []; // Array para armazenar os eventos inscritos
$erro = null;    // Variável para armazenar mensagens de erro

try {
    // Cria uma nova conexão com o banco de dados
    $conn = new Conexao();
    
    // Instancia os objetos necessários
    $atleta = new Atleta();
    $atletaService = new atletaService($conn, $atleta);
    $asaasService = new AssasService($conn);
    
    // Busca os campeonatos em que o atleta está inscrito
    $resultado = $atletaService->listarCampeonatos($_SESSION["id"]);
    
    // Garante que $inscritos será sempre um array, mesmo se a consulta falhar
    $inscritos = is_array($resultado) ? $resultado : [];
    
} catch (Exception $e) {
    // Em caso de erro, armazena a mensagem e registra no log
    $erro = "Erro ao obter inscrições: " . $e->getMessage();
    error_log($erro);
    $_SESSION['erro'] = $erro;
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <!-- Metadados básicos -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Campeonatos Inscritos</title>
    
    <!-- Inclusão de estilos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="/estilos/icone.jpeg">
    
    <!-- Estilos específicos da página -->
    <style>
        /* ... (os estilos CSS permanecem os mesmos) ... */
    </style>
</head>
<body>
<?php 
// Inclui o menu superior
include "menu/add_menu.php"; 
?>

<div class="container">
    <h1 class="page-title">Meus Campeonatos Inscritos</h1>
    
    <?php 
    // Exibe mensagem de sucesso se existir na sessão
    if (isset($_SESSION['sucesso'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= $_SESSION['sucesso']; unset($_SESSION['sucesso']); ?>
        </div>
    <?php endif; ?>
    
    <?php 
    // Exibe mensagem de erro se existir na sessão ou na variável $erro
    if (isset($_SESSION['erro'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['erro']; unset($_SESSION['erro']); ?>
        </div>
    <?php elseif ($erro): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= $erro ?>
        </div>
    <?php endif; ?>
    
    <?php 
    // Verifica se há inscrições para exibir
    if (empty($inscritos)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Você não está inscrito em nenhum campeonato no momento.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="inscricoes-table">
                <thead>
                    <tr>
                        <th>Nº Inscrição</th>
                        <th>Campeonato</th>
                        <th>Local</th>
                        <th>Data</th>
                        <th>Modalidade</th>
                        <th>C/ Quimono</th>
                        <th>S/ Quimono</th>
                        <th>Absoluto c/</th>
                        <th>Absoluto s/</th>
                        <th>Status Pagamento</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Loop através de cada inscrição
                    foreach ($inscritos as $inscrito): 
                        // Verifica se o item é um objeto válido
                        if (!is_object($inscrito)) continue;
                        
                        // Obtém o status do pagamento e ID da cobrança
                        $statusPagamento = $inscrito->status_pagamento ?? 'PENDENTE';
                        $cobrancaId = $inscrito->id_cobranca_asaas ?? null;
                        
                        // Formata o valor pago para exibição
                        $valorPago = isset($inscrito->valor_pago) ? 'R$ ' . number_format($inscrito->valor_pago, 2, ',', '.') : '--';
                        
                        // Se existir ID de cobrança, verifica o status atual na API Asaas
                        if ($cobrancaId) {
                            try {
                                $statusInfo = $asaasService->verificarStatusCobranca($cobrancaId);
                                $statusPagamento = $statusInfo['traduzido'];
                            } catch (Exception $e) {
                                $statusPagamento = 'ERRO';
                                error_log("Erro ao verificar status cobrança: " . $e->getMessage());
                            }
                        }
                        
                        // Determina a classe CSS e ícone com base no status
                        $statusClass = 'status-' . strtolower(str_replace(' ', '-', $statusPagamento));
                        $statusIcon = '';
                        
                        // Mapeia os status para classes e ícones correspondentes
                        switch (strtoupper($statusPagamento)) {
                            case 'PAGO':
                            case 'RECEIVED':
                                $statusClass = 'status-pago';
                                $statusIcon = 'fa-check-circle';
                                break;
                            case 'CONFIRMADO':
                            case 'CONFIRMED':
                                $statusClass = 'status-confirmado';
                                $statusIcon = 'fa-check-double';
                                break;
                            case 'PENDENTE':
                            case 'PENDING':
                                $statusClass = 'status-pendente';
                                $statusIcon = 'fa-clock';
                                break;
                            case 'VENCIDO':
                            case 'OVERDUE':
                                $statusClass = 'status-vencido';
                                $statusIcon = 'fa-exclamation-circle';
                                break;
                            case 'ERRO':
                                $statusClass = 'status-erro';
                                $statusIcon = 'fa-times-circle';
                                break;
                            default:
                                $statusClass = 'status-pendente';
                                $statusIcon = 'fa-question-circle';
                        }
                    ?>
                    <tr>
                        <!-- Exibe os dados da inscrição -->
                        <td><?= $_SESSION["id"] . ($inscrito->idC ?? '') ?></td>
                        <td>
                            <a href="eventos.php?id=<?= (int)($inscrito->idC ?? 0) ?>">
                                <?= htmlspecialchars($inscrito->campeonato ?? '') ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($inscrito->lugar ?? '') ?></td>
                        <td><?= isset($inscrito->dia) ? date('d/m/Y', strtotime($inscrito->dia)) : '' ?></td>
                        <td><?= htmlspecialchars($inscrito->modalidade ?? '') ?></td>
                        <td class="text-center"><?= !empty($inscrito->mcom) ? "X" : "" ?></td>
                        <td class="text-center"><?= !empty($inscrito->msem) ? "X" : "" ?></td>
                        <td class="text-center"><?= !empty($inscrito->macom) ? "X" : "" ?></td>
                        <td class="text-center"><?= !empty($inscrito->masem) ? "X" : "" ?></td>
                        <td>
                            <span class="status <?= $statusClass ?>">
                                <i class="fas <?= $statusIcon ?>"></i> <?= $statusPagamento ?>
                            </span>
                            <span class="valor-pago"><?= $valorPago ?></span>
                        </td>
                        <td class="acoes-cell">
                            <!-- Botões de ação -->
                            <a href="pagamento.php?cobranca_id=<?php echo htmlspecialchars($inscrito->assas) ;?>" 
                               class="btn <?= $cobrancaId ? 'btn-visualizar' : 'btn-pagar' ?>">
                                <i class="fas <?= $cobrancaId ? 'fa-eye' : 'fa-money-bill-wave' ?>"></i>
                                <?= $cobrancaId ? 'Ver Pagamento' : 'Pagar' ?>
                            </a>
                            <a href="inscricao.php?inscricao=<?= $inscrito->idC ?>" class="btn btn-editar" title="Editar inscrição">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php 
// Inclui o rodapé
include "menu/footer.php"; 
?>
</body>
</html>