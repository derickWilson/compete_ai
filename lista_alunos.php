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
    die("<div class='erro'>Erro ao conectar: " . $e->getMessage() . "</div>");
}

// Obter ID da academia do responsável
$academiaId = $_SESSION["academia_id"];
$nomeAcademia = $_SESSION["academia"] ?? 'Não informada';
$nomeResponsavel = $_SESSION['nome'] ?? 'Não informado';

// Obter alunos da academia
$alunos = $atletaService->listarAlunosAcademia($academiaId);
$totalAlunos = count($alunos);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Alunos - Painel do Responsável</title>
    <link rel="stylesheet" href="/style.css">
    <link rel="icon" href="/estilos/icone.jpeg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Estilos específicos para a página de alunos */
        .info-academia {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            color: var(--white);
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            box-shadow: var(--box-shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .info-academia h1 {
            margin-bottom: 10px;
            font-size: 1.8rem;
        }

        .info-academia p {
            margin-bottom: 5px;
            font-size: 1rem;
            opacity: 0.9;
        }

        .info-academia .badge-academia {
            background: var(--accent);
            color: var(--dark);
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .contador-alunos {
            background: var(--white);
            color: var(--primary-dark);
            padding: 15px;
            border-radius: var(--border-radius);
            text-align: center;
            margin: 20px 0;
            box-shadow: var(--box-shadow);
            border-left: 5px solid var(--accent);
        }

        .contador-alunos .numero {
            font-size: 2.5rem;
            font-weight: 700;
            display: block;
            color: var(--primary);
        }

        .contador-alunos .label {
            font-size: 1rem;
            color: var(--gray);
            margin-top: 5px;
        }

        .tabela-alunos {
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
        }

        .tabela-alunos table {
            width: 100%;
            border-collapse: collapse;
        }

        .tabela-alunos thead {
            background: linear-gradient(to right, var(--primary-dark), var(--primary));
        }

        .tabela-alunos th {
            color: var(--white);
            padding: 16px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .tabela-alunos td {
            padding: 14px 12px;
            border-bottom: 1px solid #eaeaea;
            color: var(--dark);
            font-size: 14px;
        }

        .tabela-alunos tr:nth-child(even) {
            background-color: #f8fafc;
        }

        /* Status do aluno */
        .status-aluno {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            display: inline-block;
            min-width: 80px;
        }

        .status-validado {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .status-pendente {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Badge de faixa */
        .badge-faixa {
            background-color: var(--primary-light);
            color: var(--white);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        /* Ações */
        .acoes-alunos {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn-novo-aluno {
            background: var(--success);
            color: var(--white);
            padding: 12px 25px;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .btn-novo-aluno:hover {
            background: #2f855a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-voltar {
            background: var(--primary);
            color: var(--white);
            padding: 12px 25px;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .btn-voltar:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Estado vazio */
        .estado-vazio {
            text-align: center;
            padding: 60px 20px;
            background: var(--white);
            border-radius: var(--border-radius);
            margin: 20px 0;
            box-shadow: var(--box-shadow);
        }

        .estado-vazio i {
            font-size: 64px;
            color: var(--gray);
            margin-bottom: 20px;
            display: block;
        }

        .estado-vazio h3 {
            color: var(--dark);
            margin-bottom: 10px;
            font-size: 1.5rem;
        }

        .estado-vazio p {
            color: var(--gray);
            max-width: 500px;
            margin: 0 auto 25px;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .info-academia {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .info-academia h1 {
                font-size: 1.5rem;
            }

            .tabela-alunos {
                overflow-x: auto;
            }

            .tabela-alunos table {
                min-width: 700px;
            }

            .acoes-alunos {
                flex-direction: column;
                align-items: center;
            }

            .btn-novo-aluno,
            .btn-voltar {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .contador-alunos .numero {
                font-size: 2rem;
            }

            .estado-vazio {
                padding: 40px 15px;
            }

            .estado-vazio i {
                font-size: 48px;
            }

            .estado-vazio h3 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>

<body>
    <?php include "menu/add_menu.php"; ?>
    <?php include "include_hamburger.php"; ?>

    <div class="container">
        <div class="principal">
            <!-- Informações da academia -->
            <div class="info-academia">
                <div>
                    <h1><i class="fas fa-users"></i> Meus Alunos</h1>
                    <p><strong>Academia:</strong> <?php echo htmlspecialchars($nomeAcademia); ?></p>
                    <p><strong>Responsável:</strong> <?php echo htmlspecialchars($nomeResponsavel); ?></p>
                </div>
                <div class="badge-academia">
                    <i class="fas fa-university"></i> ACADEMIA RESPONSÁVEL
                </div>
            </div>

            <!-- Mensagens de sucesso/erro -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert-message success">
                    <i class="fas fa-check-circle"></i>
                    <h3>Operação realizada com sucesso!</h3>
                    <p>A ação foi concluída sem problemas.</p>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert-message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <h3>Ocorreu um erro!</h3>
                    <p><?php echo htmlspecialchars($_GET['error']); ?></p>
                </div>
            <?php endif; ?>

            <!-- Contador de alunos -->
            <div class="contador-alunos">
                <span class="numero"><?php echo $totalAlunos; ?></span>
                <span class="label">Total de Alunos</span>
            </div>

            <!-- Lista de alunos -->
            <?php if (empty($alunos)): ?>
                <div class="estado-vazio">
                    <i class="fas fa-user-graduate"></i>
                    <h3>Nenhum aluno cadastrado</h3>
                    <p>Você ainda não possui alunos Validados.</p>
                </div>
            <?php else: ?>
                <div class="tabela-alunos">
                    <table>
                        <thead>
                            <tr>
                                <th><i class="fas fa-user"></i> Nome</th>
                                <th><i class="fas fa-birthday-cake"></i> Idade</th>
                                <th><i class="fas fa-ribbon"></i> Faixa</th>
                                <th><i class="fas fa-weight"></i> Peso</th>
                                <th><i class="fas fa-address-book"></i> Contato</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alunos as $aluno):
                                $idade = calcularIdade($aluno->data_nascimento);
                                $dataFiliacao = new DateTime($aluno->data_filiacao);
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($aluno->nome); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($idade); ?> anos</td>
                                    <td><span class="badge-faixa"><?php echo htmlspecialchars($aluno->faixa); ?></span></td>
                                    <td><?php echo htmlspecialchars($aluno->peso); ?> kg</td>
                                    <td>
                                        <div class="contato-info">
                                            <small><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($aluno->email); ?></small><br>
                                            <small><i class="fas fa-phone"></i> <?php echo htmlspecialchars($aluno->fone); ?></small>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Botões de ação -->
                <div class="acoes-alunos">
                    <a href="/pagina_pessoal.php" class="btn-voltar">
                        <i class="fas fa-arrow-left"></i> Voltar ao Painel Principal
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include "menu/footer.php"; ?>

    <script>
        // Melhorar experiência em dispositivos móveis
        document.addEventListener('DOMContentLoaded', function() {
            // Adicionar tooltips para informações truncadas
            const cells = document.querySelectorAll('.tabela-alunos td');
            cells.forEach(cell => {
                if (cell.scrollWidth > cell.offsetWidth) {
                    cell.title = cell.textContent;
                }
            });

            // Confirmar logout
            document.querySelectorAll('a[href*="logout"]').forEach(link => {
                link.addEventListener('click', function(e) {
                    if (!confirm('Tem certeza que deseja sair?')) {
                        e.preventDefault();
                    }
                });
            });
        });

        // Remover mensagens de alerta após alguns segundos
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert-message');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>

</html>