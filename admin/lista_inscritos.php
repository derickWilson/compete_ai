<?php
session_start();
require "../func/is_adm.php";
is_adm();
require_once __DIR__ . "/../classes/eventosServices.php";
require_once __DIR__ . "/../classes/AssasService.php";
require_once __DIR__ . "/../func/calcularIdade.php";
require_once __DIR__ . "/../func/clearWord.php";
require_once __DIR__ . "/../func/database.php";
require_once __DIR__ . "/../func/determinar_categoria.php";

// Verifica se o ID do evento foi especificado
if (!isset($_GET['id'])) {
    $_SESSION['erro'] = "ID do evento não especificado";
    header("Location: eventos.php");
    exit();
}

$idEvento = cleanWords($_GET['id']);

try {
    $conn = new Conexao();
    $evento = new Evento();
    $ev = new eventosService($conn, $evento);
    $asaasService = new AssasService($conn);
    
    // 0. OBTER LISTA DE EVENTOS PARA NAVEGAÇÃO (usando listAll() existente)
    $eventosAtivos = $ev->listAll(); // ← USANDO O MÉTODO EXISTENTE
    
    // Encontrar a posição do evento atual na lista
    $eventoAtualIndex = -1;
    $eventoAnterior = null;
    $eventoProximo = null;
    $eventoAtualInfo = null;
    
    foreach ($eventosAtivos as $index => $eventoItem) {
        if ($eventoItem->id == $idEvento) {
            $eventoAtualIndex = $index;
            $eventoAtualInfo = $eventoItem;
            
            // Evento anterior
            if ($index > 0) {
                $eventoAnterior = $eventosAtivos[$index - 1];
            }
            
            // Próximo evento
            if ($index < count($eventosAtivos) - 1) {
                $eventoProximo = $eventosAtivos[$index + 1];
            }
            break;
        }
    }
    
    // Se o evento atual não está na lista de ativos, buscar informações dele
    if (!$eventoAtualInfo) {
        try {
            $eventoAtualInfo = $ev->getById($idEvento);
            // Adicionar à lista para navegação
            array_unshift($eventosAtivos, $eventoAtualInfo);
            $eventoAtualIndex = 0;
        } catch (Exception $e) {
            $_SESSION['erro'] = "Evento não encontrado";
            header("Location: eventos.php");
            exit();
        }
    }

    // 1. PROCESSAR AÇÕES ADMINISTRATIVAS
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $idAtleta = (int) cleanWords($_POST['id_atleta']);
        $action = cleanWords($_POST['action']);

        try {
            if ($action === 'marcar_pago') {
                $valor = (float) str_replace(',', '.', cleanWords($_POST['valor']));

                $asaasService->atualizarInscricaoComPagamento(
                    $idAtleta,
                    $idEvento,
                    null, // Sem ID de cobrança Asaas (pagamento manual)
                    AssasService::STATUS_PAGO,
                    $valor
                );
                $_SESSION['mensagem'] = "Inscrição marcada como paga com sucesso!";

            } elseif ($action === 'marcar_isento') {
                // Buscar dados da inscrição para ver se tem cobrança no Asaas
                $inscricoes = $ev->getInscritos($idEvento);
                $inscricao = null;
                foreach ($inscricoes as $i) {
                    if ($i->id == $idAtleta) {
                        $inscricao = $i;
                        break;
                    }
                }

                // Se existir cobrança no Asaas, deletar
                if ($inscricao && $inscricao->id_cobranca_asaas) {
                    $resultado = $asaasService->deletarCobranca($inscricao->id_cobranca_asaas);
                    if (!$resultado['success']) {
                        error_log("Falha ao deletar cobrança: " . ($resultado['message'] ?? ''));
                        // Continua mesmo se falhar em deletar a cobrança
                    }
                }

                // Atualizar no banco de dados
                $asaasService->atualizarInscricaoComPagamento(
                    $idAtleta,
                    $idEvento,
                    null, // Remove referência à cobrança
                    AssasService::STATUS_ISENTO,
                    0 // Valor zero para isenção
                );
                $_SESSION['mensagem'] = "Inscrição marcada como isenta com sucesso!";
            } elseif ($action === 'atualizar_categoria') {
                $novaCategoria = cleanWords($_POST['categoria']);

                try {
                    // Validação básica
                    $categoriasPermitidas = [
                        'galo',
                        'pluma',
                        'pena',
                        'leve',
                        'medio',
                        'meio-pesado',
                        'pesado',
                        'super-pesado',
                        'pesadissimo',
                        'super-pesadissimo'
                    ];

                    if (!in_array($novaCategoria, $categoriasPermitidas)) {
                        throw new Exception("Categoria inválida");
                    }

                    $conn = new Conexao();
                    $pdo = $conn->conectar();

                    // Atualiza a categoria no banco de dados
                    $stmt = $pdo->prepare("UPDATE inscricao SET modalidade = :categoria WHERE id_atleta = :id_atleta AND id_evento = :id_evento");
                    $stmt->execute([
                        ':categoria' => $novaCategoria,
                        ':id_atleta' => $idAtleta,
                        ':id_evento' => $idEvento
                    ]);

                    // Verifica se atualizou
                    if ($stmt->rowCount() > 0) {
                        $_SESSION['mensagem'] = "Categoria do atleta atualizada com sucesso!";

                        // Atualiza também a categoria de idade se necessário
                        try {
                            // Obtém dados do atleta
                            $stmtAtleta = $pdo->prepare("SELECT data_nascimento FROM atleta WHERE id = :id_atleta");
                            $stmtAtleta->execute([':id_atleta' => $idAtleta]);
                            $atleta = $stmtAtleta->fetch(PDO::FETCH_OBJ);

                            if ($atleta) {
                                $idade = calcularIdade($atleta->data_nascimento);
                                $categoria_idade = determinarFaixaEtaria($idade);

                                // Atualiza categoria de idade
                                $stmtIdade = $pdo->prepare("UPDATE inscricao SET categoria_idade = :categoria_idade WHERE id_atleta = :id_atleta AND id_evento = :id_evento");
                                $stmtIdade->execute([
                                    ':categoria_idade' => $categoria_idade,
                                    ':id_atleta' => $idAtleta,
                                    ':id_evento' => $idEvento
                                ]);
                            }
                        } catch (Exception $e) {
                            // Não interrompe o fluxo principal se falhar a atualização da categoria de idade
                            error_log("Aviso: Não foi possível atualizar categoria de idade: " . $e->getMessage());
                        }
                    } else {
                        $_SESSION['erro'] = "Nenhuma alteração realizada. Verifique se a categoria é diferente da atual.";
                    }

                } catch (Exception $e) {
                    $_SESSION['erro'] = "Erro ao atualizar categoria: " . $e->getMessage();
                }

                header("Location: lista_inscritos.php?id=" . $idEvento);
                exit();
            }

        } catch (Exception $e) {
            $_SESSION['erro'] = "Erro ao processar ação: " . $e->getMessage();
        }

        header("Location: lista_inscritos.php?id=" . $idEvento);
        exit();
    }

    // 2. ATUALIZAR STATUS DE PAGAMENTOS PENDENTES
    $inscritos = $ev->getInscritos($idEvento);
    foreach ($inscritos as $inscrito) {
        if ($inscrito->id_cobranca_asaas && $inscrito->status_pagamento === 'PENDING') {
            try {
                $statusInfo = $asaasService->verificarStatusCobranca($inscrito->id_cobranca_asaas);

                if (in_array($statusInfo['status'], ['RECEIVED', 'CONFIRMED'])) {
                    $asaasService->atualizarInscricaoComPagamento(
                        $inscrito->id,
                        $idEvento,
                        $inscrito->id_cobranca_asaas,
                        $statusInfo['status'],
                        $inscrito->valor_pago
                    );
                    $inscrito->status_pagamento = $statusInfo['status'];
                }
            } catch (Exception $e) {
                error_log("Erro ao verificar status da cobrança {$inscrito->id_cobranca_asaas}: " . $e->getMessage());
            }
        }
    }

    // Recarregar lista após atualizações
    $inscritos = $ev->getInscritos($idEvento);

} catch (Exception $e) {
    $_SESSION['erro'] = "Erro ao obter inscritos: " . $e->getMessage();
    header("Location: eventos.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/style.css">
    <link rel="icon" href="/estilos/icone.jpeg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Lista de Inscritos</title>
    <style>
        .status-pago {
            color: var(--success);
            font-weight: bold;
        }

        .status-pendente {
            color: var(--warning);
            font-weight: bold;
        }

        .status-confirmado {
            color: var(--primary);
            font-weight: bold;
        }

        .status-isento {
            color: #9c27b0;
            font-weight: bold;
        }

        .status-outros {
            color: var(--gray);
        }

        .refresh-btn {
            margin: 15px 0;
            padding: 10px 20px;
            background-color: var(--primary);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .refresh-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .action-form {
            display: inline-block;
            margin: 5px;
        }

        .action-btn {
            padding: 6px 12px;
            margin: 0 2px;
            cursor: pointer;
            border-radius: 4px;
            border: 1px solid #ddd;
            font-size: 13px;
            transition: var(--transition);
        }

        .pago-btn {
            background-color: var(--success);
            color: white;
            border: none;
        }

        .isento-btn {
            background-color: #9c27b0;
            color: white;
            border: none;
        }

        .valor-input {
            width: 80px;
            padding: 5px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: right;
            font-size: 13px;
            margin-right: 5px;
        }

        .mensagem {
            padding: 12px 20px;
            margin: 15px 0;
            border-radius: var(--border-radius);
            text-align: center;
            font-weight: 500;
        }

        .mensagem.sucesso {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .mensagem.erro {
            background-color: #f8d7da;
            color: #a94442;
            border: 1px solid #f5c6cb;
        }

        /* Estilos da tabela */
        .tabela-wrapper {
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            margin: 20px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
        }

        th,
        td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
            color: var(--dark);
        }

        th {
            background-color: var(--primary-dark);
            color: var(--white);
            font-weight: 600;
            position: sticky;
            top: 0;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        /* Estilos da navegação entre eventos */
        .navegacao-eventos {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            border-radius: var(--border-radius);
            padding: 15px 20px;
            margin: 20px 0;
            box-shadow: var(--box-shadow);
            color: var(--white);
        }

        .nav-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: rgba(255, 255, 255, 0.2);
            color: var(--white);
            text-decoration: none;
            border-radius: 5px;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.3);
            font-weight: 500;
            white-space: nowrap;
        }

        .nav-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            text-decoration: none;
        }

        .nav-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-btn.disabled:hover {
            transform: none;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .evento-info {
            text-align: center;
            flex-grow: 1;
            padding: 0 20px;
        }

        .evento-info h3 {
            margin: 0;
            color: var(--white);
            font-size: 1.2rem;
        }

        .evento-info .contador {
            font-size: 13px;
            opacity: 0.9;
            margin-top: 5px;
        }

        .dropdown-eventos {
            position: relative;
            margin: 15px 0;
            text-align: center;
        }

        .dropdown-btn {
            padding: 10px 20px;
            background-color: var(--secondary);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            transition: var(--transition);
        }

        .dropdown-btn:hover {
            background-color: #c53030;
            transform: translateY(-2px);
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: var(--white);
            min-width: 300px;
            max-width: 90vw;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            z-index: 1000;
            border-radius: var(--border-radius);
            max-height: 400px;
            overflow-y: auto;
            left: 50%;
            transform: translateX(-50%);
            margin-top: 10px;
        }

        .dropdown-content a {
            color: var(--dark);
            padding: 12px 15px;
            text-decoration: none;
            display: block;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s;
        }

        .dropdown-content a:hover {
            background-color: var(--primary-light);
            color: var(--white);
        }

        .dropdown-content a.ativo {
            background-color: var(--primary);
            color: var(--white);
            font-weight: bold;
        }

        .dropdown-content a .evento-data {
            float: right;
            font-size: 12px;
            color: var(--gray);
        }

        .dropdown-content a.ativo .evento-data {
            color: rgba(255, 255, 255, 0.8);
        }

        .dropdown-content a:last-child {
            background-color: #f8f9fa;
            text-align: center;
            font-weight: 500;
        }

        .dropdown-eventos:hover .dropdown-content {
            display: block;
        }

        .absoluto-badge {
            background-color: var(--accent);
            color: var(--dark);
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            margin-top: 5px;
            display: inline-block;
            font-weight: bold;
        }

        .categoria-select {
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
            background: white;
            cursor: pointer;
            transition: var(--transition);
        }

        .categoria-select:focus {
            border-color: var(--primary);
            outline: none;
        }

        /* Ações na tabela */
        .acoes-celula {
            min-width: 200px;
        }

        /* Botões de ação */
        .botoes-acao {
            display: flex;
            gap: 15px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .botao-baixar {
            background-color: var(--success);
            color: var(--white);
            padding: 10px 20px;
            border-radius: var(--border-radius);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: var(--transition);
        }

        .botao-baixar:hover {
            background-color: #2e7d32;
            transform: translateY(-2px);
            text-decoration: none;
        }

        .botao-voltar {
            background-color: var(--primary);
            color: var(--white);
            padding: 10px 20px;
            border-radius: var(--border-radius);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: var(--transition);
        }

        .botao-voltar:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            text-decoration: none;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .navegacao-eventos {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }

            .nav-botoes {
                display: flex;
                width: 100%;
                justify-content: space-between;
            }

            .evento-info {
                order: -1;
                margin-bottom: 10px;
            }

            .dropdown-content {
                min-width: 90vw;
                left: 5vw;
                transform: translateX(0);
            }

            table {
                display: block;
                overflow-x: auto;
            }

            th, td {
                padding: 8px 10px;
                font-size: 13px;
            }

            .acoes-celula {
                min-width: 150px;
            }

            .categoria-select {
                font-size: 12px;
                padding: 4px 8px;
            }

            .valor-input {
                width: 70px;
                font-size: 12px;
            }

            .action-btn {
                padding: 4px 8px;
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {
            .nav-btn {
                padding: 8px 12px;
                font-size: 13px;
            }

            .evento-info h3 {
                font-size: 1rem;
            }

            .dropdown-btn {
                padding: 8px 15px;
                font-size: 14px;
            }

            th, td {
                padding: 6px 8px;
                font-size: 12px;
            }

            .botoes-acao {
                flex-direction: column;
                align-items: center;
            }

            .botao-baixar, .botao-voltar {
                width: 100%;
                text-align: center;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <?php include "../menu/menu_admin.php"; ?>
    <?php include "../include_hamburger.php"; ?>
    <div class="container">
        
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="mensagem sucesso"><?= htmlspecialchars($_SESSION['mensagem']) ?></div>
            <?php unset($_SESSION['mensagem']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['erro'])): ?>
            <div class="mensagem erro"><?= htmlspecialchars($_SESSION['erro']) ?></div>
            <?php unset($_SESSION['erro']); ?>
        <?php endif; ?>

        <!-- NAVEGAÇÃO ENTRE EVENTOS -->
        <div class="navegacao-eventos">
            <div class="nav-botoes">
                <?php if ($eventoAnterior): ?>
                    <a href="lista_inscritos.php?id=<?= $eventoAnterior->id ?>" class="nav-btn">
                        <i class="fas fa-chevron-left"></i>
                        <span>Anterior</span>
                    </a>
                <?php else: ?>
                    <span class="nav-btn disabled">
                        <i class="fas fa-chevron-left"></i>
                        <span>Anterior</span>
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="evento-info">
                <h3><?= htmlspecialchars($eventoAtualInfo->nome ?? 'Evento') ?></h3>
                <?php if ($eventoAtualIndex !== -1 && count($eventosAtivos) > 1): ?>
                    <div class="contador">
                        Evento <?= ($eventoAtualIndex + 1) ?> de <?= count($eventosAtivos) ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="nav-botoes">
                <?php if ($eventoProximo): ?>
                    <a href="lista_inscritos.php?id=<?= $eventoProximo->id ?>" class="nav-btn">
                        <span>Próximo</span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <span class="nav-btn disabled">
                        <span>Próximo</span>
                        <i class="fas fa-chevron-right"></i>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- DROPDOWN COM TODOS EVENTOS -->
        <?php if (count($eventosAtivos) > 1): ?>
        <div class="dropdown-eventos">
            <button class="dropdown-btn">
                <i class="fas fa-list"></i>
                <span>Selecionar Outro Evento</span>
                <i class="fas fa-caret-down"></i>
            </button>
            <div class="dropdown-content">
                <?php foreach ($eventosAtivos as $index => $eventoItem): ?>
                    <a href="lista_inscritos.php?id=<?= $eventoItem->id ?>" 
                       class="<?= $eventoItem->id == $idEvento ? 'ativo' : '' ?>">
                        <strong><?= htmlspecialchars($eventoItem->nome) ?></strong>
                        <?php if (isset($eventoItem->normal) && $eventoItem->normal == 'sim'): ?>
                            <span style="color: var(--accent); font-size: 11px; margin-left: 5px;">
                                <i class="fas fa-star"></i> Normal
                            </span>
                        <?php endif; ?>
                        <?php if (isset($eventoItem->data_evento)): ?>
                            <span class="evento-data">
                                <?= date('d/m/Y', strtotime($eventoItem->data_evento)) ?>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
                <a href="/eventos.php">
                    <i class="fas fa-calendar-alt"></i> Ver todos os eventos
                </a>
            </div>
        </div>
        <?php endif; ?>

        <h1 class="section-title">Inscritos no Evento</h1>

        <?php if ($inscritos && !empty($inscritos)): ?>
            <button onclick="location.reload()" class="refresh-btn">
                <i class="fas fa-sync-alt"></i> Atualizar Status de Pagamentos
            </button>
            
            <div class="tabela-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Atleta</th>
                            <th>Idade</th>
                            <th>Faixa</th>
                            <th>Peso</th>
                            <th>Modalidade</th>
                            <th>Academia</th>
                            <th>Status</th>
                            <th>Valor</th>
                            <th class="acoes-celula">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inscritos as $inscrito) {
                            $statusClass = 'status-outros';
                            $statusText = $inscrito->status_pagamento;

                            switch ($inscrito->status_pagamento) {
                                case 'RECEIVED':
                                    $statusClass = 'status-pago';
                                    $statusText = 'Pago';
                                    break;
                                case 'PENDING':
                                    $statusClass = 'status-pendente';
                                    $statusText = 'Pendente';
                                    break;
                                case 'CONFIRMED':
                                    $statusClass = 'status-confirmado';
                                    $statusText = 'Confirmado';
                                    break;
                                case 'ISENTO':
                                    $statusClass = 'status-isento';
                                    $statusText = 'Isento';
                                    break;
                            }

                            $valorExibicao = ($inscrito->valor_pago > 0)
                                ? 'R$ ' . number_format($inscrito->valor_pago, 2, ',', '.')
                                : '-';
                            ?>
                            <tr>
                                <td><?= $inscrito->id ?></td>
                                <td><?= htmlspecialchars($inscrito->inscrito) ?></td>
                                <td><?= calcularIdade($inscrito->data_nascimento) ?></td>
                                <td><?= htmlspecialchars($inscrito->faixa) ?></td>
                                <td><?= htmlspecialchars($inscrito->peso) ?> kg</td>
                                <td>
                                    <form method="POST" class="action-form categoria-form">
                                        <input type="hidden" name="id_atleta" value="<?= $inscrito->id ?>">
                                        <input type="hidden" name="action" value="atualizar_categoria">
                                        <select name="categoria" class="categoria-select" onchange="this.form.submit()">
                                            <option value="galo" <?= $inscrito->modalidade == 'galo' ? 'selected' : '' ?>>Galo</option>
                                            <option value="pluma" <?= $inscrito->modalidade == 'pluma' ? 'selected' : '' ?>>Pluma</option>
                                            <option value="pena" <?= $inscrito->modalidade == 'pena' ? 'selected' : '' ?>>Pena</option>
                                            <option value="leve" <?= $inscrito->modalidade == 'leve' ? 'selected' : '' ?>>Leve</option>
                                            <option value="medio" <?= $inscrito->modalidade == 'medio' ? 'selected' : '' ?>>Médio</option>
                                            <option value="meio-pesado" <?= $inscrito->modalidade == 'meio-pesado' ? 'selected' : '' ?>>Meio-Pesado</option>
                                            <option value="pesado" <?= $inscrito->modalidade == 'pesado' ? 'selected' : '' ?>>Pesado</option>
                                            <option value="super-pesado" <?= $inscrito->modalidade == 'super-pesado' ? 'selected' : '' ?>>Super-Pesado</option>
                                            <option value="pesadissimo" <?= $inscrito->modalidade == 'pesadissimo' ? 'selected' : '' ?>>Pesadíssimo</option>
                                            <?php if (calcularIdade($inscrito->data_nascimento) > 15): ?>
                                                <option value="super-pesadissimo" <?= $inscrito->modalidade == 'super-pesadissimo' ? 'selected' : '' ?>>Super-Pesadíssimo</option>
                                            <?php endif; ?>
                                        </select>
                                        <?php if ($inscrito->mod_ab_com == 1 || $inscrito->mod_ab_sem == 1): ?>
                                            <div class="absoluto-badge">
                                                <small><i class="fas fa-trophy"></i> Absoluto</small>
                                            </div>
                                        <?php endif; ?>
                                    </form>
                                </td>
                                <td><?= htmlspecialchars($inscrito->academia) ?></td>
                                <td class="<?= $statusClass ?>"><?= $statusText ?></td>
                                <td><?= $valorExibicao ?></td>
                                <td>
                                    <?php if ($inscrito->id_cobranca_asaas): ?>
                                        <a href="/pagamento.php?cobranca_id=<?= urlencode($inscrito->id_cobranca_asaas) ?>&view=1"
                                            title="Ver detalhes do pagamento" style="margin-right: 8px;">
                                            <i class="fas fa-eye"></i> Detalhes
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($inscrito->status_pagamento === 'PENDING') { ?>
                                        <form class="action-form" method="POST"
                                            onsubmit="return confirm('Confirmar marcação como PAGO?')" style="margin-bottom: 5px;">
                                            <input type="hidden" name="id_atleta" value="<?= $inscrito->id ?>">
                                            <input type="hidden" name="action" value="marcar_pago">
                                            <input type="number" name="valor" class="valor-input" step="0.01" min="0"
                                                value="<?= number_format($inscrito->valor_pago ?? 0, 2, '.', '') ?>" required>
                                            <button type="submit" class="action-btn pago-btn" title="Marcar como pago">
                                                <i class="fas fa-check"></i> Pago
                                            </button>
                                        </form>

                                        <form class="action-form" method="POST"
                                            onsubmit="return confirm('Confirmar isenção? A cobrança será cancelada.')">
                                            <input type="hidden" name="id_atleta" value="<?= $inscrito->id ?>">
                                            <input type="hidden" name="action" value="marcar_isento">
                                            <button type="submit" class="action-btn isento-btn" title="Marcar como isento">
                                                <i class="fas fa-gift"></i> Isento
                                            </button>
                                        </form>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <div class="botoes-acao">
                <form action="baixar_chapa.php" method="GET" style="display: inline-block;">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($idEvento) ?>">
                    <button type="submit" class="botao-baixar">
                        <i class="fas fa-download"></i> Baixar Planilha
                    </button>
                </form>

                <a href="eventos.php" class="botao-voltar">
                    <i class="fas fa-arrow-left"></i> Voltar para Eventos
                </a>
            </div>

        <?php else: ?>
            <div class="principal" style="text-align: center; padding: 40px;">
                <h3>Nenhum inscrito encontrado para este evento.</h3>
                <div style="margin-top: 30px;">
                    <a href="eventos.php" class="botao-voltar">
                        <i class="fas fa-arrow-left"></i> Voltar para Eventos
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <?php include "../menu/footer.php"; ?>

    <script>
        // Atualiza automaticamente a página a cada 2 minutos
        setTimeout(function () {
            location.reload();
        }, 120000);
        
        // Fechar dropdown ao clicar fora
        document.addEventListener('click', function(event) {
            var dropdowns = document.querySelectorAll('.dropdown-eventos');
            dropdowns.forEach(function(dropdown) {
                if (!dropdown.contains(event.target)) {
                    var content = dropdown.querySelector('.dropdown-content');
                    if (content) content.style.display = 'none';
                }
            });
        });
        
        // Manter dropdown aberto ao clicar nele
        document.querySelectorAll('.dropdown-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var content = this.closest('.dropdown-eventos').querySelector('.dropdown-content');
                if (content) {
                    content.style.display = content.style.display === 'block' ? 'none' : 'block';
                }
            });
        });
        
        // Confirmação para ações
        document.querySelectorAll('.categoria-select').forEach(function(select) {
            select.addEventListener('change', function() {
                if (!confirm('Tem certeza que deseja alterar a categoria deste atleta?')) {
                    this.form.reset();
                }
            });
        });
    </script>
</body>

</html>