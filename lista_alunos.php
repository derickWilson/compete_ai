<?php
session_start();
require_once __DIR__ . "/func/database.php";
require_once __DIR__ . "/classes/atletaClass.php";
require_once __DIR__ . "/classes/atletaService.php";
require_once __DIR__ . "/func/calcularIdade.php";
require_once __DIR__ . "/func/security.php";

// Verificar se o usuário está logado e é responsável
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || $_SESSION['responsavel'] != 1) {
    header('Location: /login.php');
    exit();
}

// Instanciar conexão e serviço
try {
    $conn = new Conexao();
    $atleta = new Atleta();
    $atletaService = new atletaService($conn, $atleta);
} catch (Exception $e) {
    die("Erro ao conectar: " . $e->getMessage());
}

// Obter ID da academia do responsável
$academiaId = $_SESSION["academia_id"];

// Obter alunos da academia
$alunos = $atletaService->listarAlunosAcademia($academiaId);

// Obter nome da academia
$nomeAcademia = $_SESSION["academia"];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Alunos - Painel do Responsável</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .header {
            background: linear-gradient(135deg, #2520a0 0%, #322ec0 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .table-container {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 0 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        .status-validado {
            background-color: #d4edda;
            color: #155724;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pendente {
            background-color: #fff3cd;
            color: #856404;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-faixa {
            background-color: #322ec0;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .table th {
            background-color: #2520a0;
            color: white;
            border: none;
        }
        .table td {
            vertical-align: middle;
        }
        .btn-voltar {
            background-color: #2520a0;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }
        .btn-voltar:hover {
            background-color: #322ec0;
            color: white;
        }
        .aluno-count {
            background-color: #e9ecef;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 50px;
            margin-bottom: 20px;
            color: #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Cabeçalho -->
        <div class="header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">🥋 Meus Alunos</h1>
                    <p class="mb-1"><strong>Academia:</strong> <?php echo htmlspecialchars($nomeAcademia); ?></p>
                    <p class="mb-1"><strong>Responsável:</strong> <?php echo htmlspecialchars($_SESSION['nome'] ?? 'Não informado'); ?></p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="/pagina_pessoal.php" class="btn btn-light btn-sm me-2">← Voltar ao Painel</a>
                    <a href="/logout.php" class="btn btn-outline-light btn-sm">Sair</a>
                </div>
            </div>
        </div>

        <!-- Mensagens de erro/sucesso -->
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($erro); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Operação realizada com sucesso!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Contador de alunos -->
        <div class="aluno-count">
            Total de alunos: <?php echo count($alunos); ?>
        </div>

        <!-- Tabela de Alunos -->
        <div class="table-container">
            <?php if (empty($alunos)): ?>
                <div class="empty-state">
                    <i>👤</i>
                    <h4>Nenhum aluno cadastrado</h4>
                    <p>Você ainda não possui alunos matriculados em sua academia.</p>
                    <a href="/cadastro_atleta.php" class="btn btn-primary mt-3">Cadastrar Novo Aluno</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Idade</th>
                                <th>Faixa</th>
                                <th>Peso</th>
                                <th>Contato</th>
                                <th>Status</th>
                                <th>Desde</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alunos as $aluno): 
                                $idade = calcularIdade($aluno->data_nascimento);
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($aluno->nome); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($idade); ?> anos</td>
                                    <td><span class="badge-faixa"><?php echo htmlspecialchars($aluno->faixa); ?></span></td>
                                    <td><?php echo htmlspecialchars($aluno->peso); ?> kg</td>
                                    <td>
                                        <small>
                                            <?php echo htmlspecialchars($aluno->email); ?><br>
                                            <?php echo htmlspecialchars($aluno->fone); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="status-validado">
                                            <?php echo $aluno->validado == 1 ? 'Validado' : 'Pendente'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                            $dataFiliacao = new DateTime($aluno->data_filiacao);
                                            echo $dataFiliacao->format('d/m/Y');
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Botões de ação -->
        <div class="d-flex justify-content-between">
            <a href="/pagina_pessoal.php" class="btn-voltar">
                ← Voltar ao Painel Principal
            </a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Adicionar confirmação antes de sair
        document.querySelectorAll('a[href="/logout.php"]').forEach(link => {
            link.addEventListener('click', function(e) {
                if (!confirm('Tem certeza que deseja sair?')) {
                    e.preventDefault();
                }
            });
        });

        // Melhorar a experiência mobile
        if (window.innerWidth < 768) {
            document.querySelectorAll('.table td').forEach(cell => {
                if (cell.scrollWidth > cell.offsetWidth) {
                    cell.title = cell.textContent;
                }
            });
        }
    </script>
</body>
</html>