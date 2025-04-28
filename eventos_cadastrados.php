<?php
session_start();

// Verificação de sessão mais completa
if (!isset($_SESSION["logado"], $_SESSION["id"], $_SESSION["nome"])) {
    header("Location: index.php");
    exit();
}

require_once "classes/atletaService.php";
require_once "classes/conexao.php";

try {
    // Obtenha os inscritos com tratamento de erros
    $conn = new Conexao();
    $atleta = new Atleta();
    $ev = new atletaService($conn, $atleta);
    
    $inscritos = $ev->listarCampeonatos($_SESSION["id"]);
    
    // Verifica se há inscrições
    if (empty($inscritos)) {
        $mensagem = "Nenhum campeonato encontrado";
    }
} catch (Exception $e) {
    $_SESSION['erro'] = "Erro ao obter inscrições: " . $e->getMessage();
    header("Location: erro.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Campeonatos Inscritos</title>
    <link rel="stylesheet" href="assets/css/estilo.css">
    <style>
        .tabela-inscricoes {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 0.9em;
        }
        .tabela-inscricoes th, .tabela-inscricoes td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .tabela-inscricoes th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .tabela-inscricoes tr:hover {
            background-color: #f5f5f5;
        }
        .selecionado {
            color: #28a745;
            font-weight: bold;
        }
        .btn-editar {
            color: #fff;
            background-color: #007bff;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
        }
        .btn-editar:hover {
            background-color: #0069d9;
        }
        .sem-registros {
            padding: 20px;
            text-align: center;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include "menu/add_menu.php"; ?>
    
    <div class="container">
        <h2>Meus Campeonatos Inscritos</h2>
        
        <?php if (isset($mensagem)): ?>
            <div class="sem-registros">
                <p><?php echo $mensagem; ?></p>
                <a href="eventos.php" class="btn-editar">Inscreva-se em um campeonato</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="tabela-inscricoes">
                    <thead>
                        <tr>
                            <th>Nº Inscrição</th>
                            <th>Campeonato</th>
                            <th>Local</th>
                            <th>Data</th>
                            <th>Modalidade</th>
                            <th>Com Kimono</th>
                            <th>Sem Kimono</th>
                            <th>Absoluto c/ Kimono</th>
                            <th>Absoluto s/ Kimono</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inscritos as $inscrito): ?>
                            <?php if (!isset($inscrito->idC, $inscrito->campeonato, $inscrito->lugar, $inscrito->dia)) continue; ?>
                            <tr>
                                <td>INSC-<?php echo $_SESSION["id"] . '-' . $inscrito->idC; ?></td>
                                <td>
                                    <a href="eventos.php?id=<?php echo (int)$inscrito->idC; ?>">
                                        <?php echo htmlspecialchars($inscrito->campeonato); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($inscrito->lugar); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($inscrito->dia)); ?></td>
                                <td><?php echo htmlspecialchars($inscrito->modalidade); ?></td>
                                <td><?php echo $inscrito->mcom ? '<span class="selecionado">X</span>' : ''; ?></td>
                                <td><?php echo $inscrito->msem ? '<span class="selecionado">X</span>' : ''; ?></td>
                                <td><?php echo $inscrito->macom ? '<span class="selecionado">X</span>' : ''; ?></td>
                                <td><?php echo $inscrito->masem ? '<span class="selecionado">X</span>' : ''; ?></td>
                                <td>
                                    <a href="inscricao.php?inscricao=<?php echo (int)$inscrito->idC; ?>&atleta=<?php echo (int)$_SESSION['id']; ?>" 
                                       class="btn-editar">
                                        Editar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php include "menu/footer.php"; ?>
</body>
</html>