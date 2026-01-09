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

// Função para obter campeonatos inscritos de um aluno
function obterCampeonatosAluno($atletaService, $alunoId)
{
    try {
        $campeonatos = $atletaService->listarCampeonatos($alunoId);
        return $campeonatos ?: [];
    } catch (Exception $e) {
        error_log("Erro ao obter campeonatos do aluno {$alunoId}: " . $e->getMessage());
        return [];
    }
}
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
            position: relative;
            z-index: 1;
        }

        .tabela-alunos table {
            width: 100%;
            border-collapse: collapse;
        }

        .tabela-alunos thead {
            background: linear-gradient(to right, var(--primary-dark), var(--primary));
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .tabela-alunos th {
            color: var(--white);
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            min-width: 120px;
        }

        .tabela-alunos td {
            padding: 16px 15px;
            border-bottom: 1px solid #eaeaea;
            color: var(--dark);
            font-size: 14px;
            position: relative;
            z-index: 1;
            vertical-align: top;
        }

        .tabela-alunos tr:nth-child(even) {
            background-color: #f8fafc;
        }

        /* Informações do aluno - Célula expandida */
        .aluno-info {
            min-width: 200px;
            max-width: 250px;
        }

        .aluno-nome {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 8px;
            font-size: 15px;
            line-height: 1.3;
        }

        .aluno-detalhes {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .aluno-detalhe-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--gray);
            font-size: 12px;
            line-height: 1.2;
        }

        .aluno-detalhe-item i {
            color: var(--primary-light);
            width: 16px;
            text-align: center;
        }

        /* Badge de faixa */
        .badge-faixa {
            background-color: var(--primary-light);
            color: var(--white);
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }

        /* Peso - célula individual */
        .peso-valor {
            font-weight: 600;
            color: var(--primary-dark);
            font-size: 15px;
            display: block;
            text-align: center;
        }

        /* Container de campeonatos */
        .campeonatos-container {
            min-width: 180px;
            max-width: 220px;
            position: relative;
            z-index: 2;
        }

        .campeonatos-lista {
            max-height: 140px;
            overflow-y: auto;
            padding-right: 8px;
            position: relative;
            z-index: 2;
            background: var(--white);
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 8px;
        }

        .campeonato-item {
            background: #f1f5f9;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 8px;
            border-left: 3px solid var(--primary);
            transition: all 0.2s ease;
            position: relative;
            z-index: 3;
        }

        .campeonato-item:hover {
            background: #e2e8f0;
            transform: translateX(2px);
        }

        .campeonato-nome {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 5px;
            font-size: 13px;
            line-height: 1.3;
            word-break: break-word;
        }

        .campeonato-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: var(--gray);
            gap: 5px;
        }

        .campeonato-data {
            color: var(--success);
            font-weight: 500;
            white-space: nowrap;
        }

        .status-pagamento {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 10px;
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
        }

        .status-pago {
            background: #dcfce7;
            color: #166534;
        }

        .status-pendente {
            background: #fef3c7;
            color: #92400e;
        }

        .status-outro {
            background: #e5e7eb;
            color: #374151;
        }

        /* Botões de ação para alunos */
        .acoes-aluno {
            display: flex;
            flex-direction: column;
            gap: 10px;
            position: relative;
            z-index: 2;
            min-width: 140px;
        }

        .btn-acao-faixa {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-weight: 500;
            width: 100%;
            text-align: center;
        }

        .btn-acao-faixa:hover {
            background: linear-gradient(135deg, #2980b9 0%, #1f639b 100%);
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        /* Ações principais */
        .acoes-alunos {
            display: flex;
            justify-content: center;
            gap: 20px;
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

        /* Botões de ação no topo */
        .botoes-topo {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .btn-inscrever-atletas {
            background: var(--accent);
            color: var(--dark);
            padding: 12px 25px;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            border: 2px solid transparent;
        }

        .btn-inscrever-atletas:hover {
            background: transparent;
            border-color: var(--accent);
            color: var(--accent);
            transform: translateY(-2px);
        }

        /* Sem campeonatos */
        .sem-campeonatos {
            color: var(--gray);
            font-style: italic;
            font-size: 13px;
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
        }

        /* Responsividade */
        @media (max-width: 1200px) {
            .tabela-alunos {
                overflow-x: auto;
                display: block;
            }
            
            .tabela-alunos table {
                min-width: 1100px;
            }
        }

        @media (max-width: 768px) {
            .info-academia {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .info-academia h1 {
                font-size: 1.5rem;
            }

            .acoes-alunos {
                flex-direction: column;
                align-items: center;
            }

            .btn-novo-aluno,
            .btn-voltar,
            .btn-inscrever-atletas {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }

            .botoes-topo {
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
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

            .botoes-topo {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>

<body>
    <?php include "menu/add_menu.php"; ?>
    <?php include "include_hamburger.php"; ?>

    <div class="container">
        <div class="principal">
            <!-- Botões de ação no topo -->
            <div class="botoes-topo">
                <a href="/inscrever_academia.php" class="btn-inscrever-atletas">
                    <i class="fas fa-user-plus"></i> Inscrever Academia
                </a>
            </div>

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
                    <p><?php echo htmlspecialchars($_GET['success']); ?></p>
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
                    <p>Você ainda não possui alunos cadastrados na sua academia.</p>
                    <a href="/cadastro_atleta.php" class="btn-novo-aluno">
                        <i class="fas fa-user-plus"></i> Cadastrar Primeiro Aluno
                    </a>
                </div>
            <?php else: ?>
                <div class="tabela-alunos">
                    <table>
                        <thead>
                            <tr>
                                <th><i class="fas fa-user"></i> Aluno</th>
                                <th><i class="fas fa-birthday-cake"></i> Idade</th>
                                <th><i class="fas fa-ribbon"></i> Faixa</th>
                                <th><i class="fas fa-weight"></i> Peso</th>
                                <th><i class="fas fa-trophy"></i> Campeonatos</th>
                                <th><i class="fas fa-cogs"></i> Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alunos as $aluno):
                                $idade = calcularIdade($aluno->data_nascimento);
                                $campeonatos = obterCampeonatosAluno($atletaService, $aluno->id);
                                $totalCampeonatos = count($campeonatos);
                                ?>
                                <tr>
                                    <td class="aluno-info">
                                        <div class="aluno-nome"><?php echo htmlspecialchars($aluno->nome); ?></div>
                                        <div class="aluno-detalhes">
                                            <div class="aluno-detalhe-item">
                                                <i class="fas fa-envelope"></i>
                                                <span><?php echo htmlspecialchars($aluno->email); ?></span>
                                            </div>
                                            <div class="aluno-detalhe-item">
                                                <i class="fas fa-phone"></i>
                                                <span><?php echo htmlspecialchars($aluno->fone); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="text-align: center;">
                                            <span style="font-weight: 600; color: var(--primary-dark); font-size: 15px;">
                                                <?php echo htmlspecialchars($idade); ?> anos
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="text-align: center;">
                                            <span class="badge-faixa"><?php echo htmlspecialchars($aluno->faixa); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="text-align: center;">
                                            <span class="peso-valor"><?php echo htmlspecialchars($aluno->peso); ?> kg</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="campeonatos-container">
                                            <?php if ($totalCampeonatos > 0): ?>
                                                <div class="campeonatos-lista">
                                                    <?php
                                                    // Mostrar apenas os 3 primeiros campeonatos
                                                    $displayLimit = min(3, $totalCampeonatos);
                                                    for ($i = 0; $i < $displayLimit; $i++):
                                                        $camp = $campeonatos[$i];
                                                        $dataFormatada = date('d/m/Y', strtotime($camp->dia));
                                                        $statusClass = strtolower($camp->status_pagamento) === 'received' ? 'pago' :
                                                            (strtolower($camp->status_pagamento) === 'pending' ? 'pendente' : 'outro');
                                                        ?>
                                                        <div class="campeonato-item">
                                                            <div class="campeonato-nome">
                                                                <?php echo htmlspecialchars($camp->campeonato); ?>
                                                            </div>
                                                            <div class="campeonato-info">
                                                                <span class="campeonato-data"><?php echo $dataFormatada; ?></span>
                                                                <span class="status-pagamento status-<?php echo $statusClass; ?>">
                                                                    <?php echo htmlspecialchars($camp->status_pagamento); ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    <?php endfor; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="sem-campeonatos">Nenhum campeonato</div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="acoes-aluno">
                                            <!-- Botão para solicitar troca de faixa -->
                                            <a href="solicitar_troca_faixa.php?aluno_id=<?php echo $aluno->id; ?>" 
                                               class="btn-acao-faixa" 
                                               title="Solicitar troca de faixa">
                                                <i class="fas fa-ribbon"></i> Trocar Faixa
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Botões de ação -->
                <div class="acoes-alunos">
                    <a href="/cadastro_atleta.php" class="btn-novo-aluno">
                        <i class="fas fa-user-plus"></i> Cadastrar Novo Aluno
                    </a>
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
            // Remover mensagens de alerta após alguns segundos
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert-message');
                alerts.forEach(alert => {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                });
            }, 5000);
        });
    </script>
</body>

</html>