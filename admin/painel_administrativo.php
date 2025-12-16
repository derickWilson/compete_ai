<?php
session_start();
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
require "../func/is_adm.php";
is_adm();
include_once "../classes/atletaClass.php";
include_once "../classes/atletaService.php";
try {
    $con = new Conexao();
    $at = new Atleta();
    $attServ = new atletaService($con, $at);
    $lista = $attServ->listInvalido();
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/style.css">
    <link rel="icon" href="/estilos/icone.jpeg">
    <title>Painel Administrativo</title>
    <style>
        /* Estilo para donos de academia no painel administrativo */
        tr.dono-academia {
            background-color: #e8f5e8 !important;
            border-left: 4px solid #28a745;
        }

        tr.dono-academia:hover {
            background-color: #d4edda !important;
        }
    </style>
</head>

<body>
    <?php include "../menu/add_menu.php"; ?>
    <?php include "../include_hamburger.php"; ?>
    <div class="container">
        <div class="principal">
            <h2 class="section-title" style="color: var(--primary-dark); text-shadow: none;">Painel Administrativo</h2>
            <p class="aviso info">Aqui você pode gerenciar os atletas que aguardam validação.</p>

            <?php if (!empty($lista)): ?>
                <div class="table-responsive">
                    <table class="tabela-admin">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Faixa</th>
                                <th>Academia</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <!-- No corpo da tabela -->
                        <tbody>
                            <?php foreach ($lista as $value) { ?>
                                <tr class="<?php echo $value->responsavel == 1 ? 'dono-academia' : ''; ?>">
                                    <td><?php echo htmlspecialchars($value->nome); ?></td>
                                    <td><?php echo htmlspecialchars($value->email ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge-faixa"><?php echo htmlspecialchars($value->faixa); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($value->academia); ?></td>
                                    <td>
                                        <span class="status status-pendente">
                                            <i class="fas fa-clock"></i> Pendente
                                        </span>
                                    </td>
                                    <td>
                                        <a href="controle.php?user=<?php echo htmlspecialchars($value->id); ?>"
                                            class="botao-acao pequeno" title="Gerenciar usuário">
                                            <i class="fas fa-cog"></i> Gerenciar
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <div class="estatisticas">
                    <div class="estatistica-item">
                        <i class="fas fa-users"></i>
                        <span class="numero"><?php echo count($lista); ?></span>
                        <span class="label">Atletas pendentes</span>
                    </div>
                </div>

            <?php else: ?>
                <div class="nenhum-item">
                    <i class="fas fa-check-circle" style="font-size: 48px; color: var(--success);"></i>
                    <h3>Todos os atletas estão validados!</h3>
                    <p>Não há usuários aguardando validação no momento.</p>
                </div>
            <?php endif; ?>

            <div class="acoes-adicionais">
                <a href="../admin/" class="botao-voltar">
                    <i class="fas fa-arrow-left"></i> Voltar ao Menu Admin
                </a>
                <a href="../admin/lista_completa.php" class="botao-acao">
                    <i class="fas fa-list"></i> Ver Todos os Usuários
                </a>
            </div>
        </div>
    </div>

    <?php include "../menu/footer.php"; ?>
</body>