<?php
session_start();
require_once __DIR__ . "/func/database.php";
require_once __DIR__ . "/classes/atletaClass.php";
require_once __DIR__ . "/classes/atletaService.php";
require_once __DIR__ . "/classes/eventosServices.php";
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
    $eventoServ = new eventosService($conn, new Evento());
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

        /* Lista de campeonatos */
        .campeonatos-lista {
            max-height: 120px;
            overflow-y: auto;
            padding-right: 5px;
        }

        .campeonato-item {
            background: #f1f5f9;
            border-radius: 6px;
            padding: 8px 10px;
            margin-bottom: 6px;
            border-left: 3px solid var(--primary);
            transition: all 0.2s ease;
        }

        .campeonato-item:hover {
            background: #e2e8f0;
            transform: translateX(2px);
        }

        .campeonato-nome {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 3px;
            font-size: 13px;
        }

        .campeonato-info {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: var(--gray);
        }

        .campeonato-data {
            color: var(--success);
            font-weight: 500;
        }

        .status-pagamento {
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 600;
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

        .contador-campeonatos {
            background: var(--accent);
            color: var(--dark);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 5px;
        }

        /* Modal para ver mais campeonatos */
        .modal-campeonatos {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: var(--white);
            margin: 5% auto;
            padding: 25px;
            border-radius: var(--border-radius);
            width: 80%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 15px;
        }

        .modal-header h3 {
            color: var(--primary-dark);
            margin: 0;
        }

        .close-modal {
            color: var(--gray);
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s;
        }

        .close-modal:hover {
            color: var(--danger);
        }

        .modal-lista-campeonatos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }

        .modal-campeonato-item {
            background: #f8fafc;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }

        .modal-campeonato-item:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .sem-campeonatos {
            text-align: center;
            color: var(--gray);
            padding: 20px;
            font-style: italic;
        }

        /* Botão para ver mais */
        .btn-ver-mais {
            background: var(--primary-light);
            color: var(--white);
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 8px;
        }

        .btn-ver-mais:hover {
            background: var(--primary);
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
                min-width: 900px;
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

            .modal-content {
                width: 95%;
                margin: 10% auto;
            }

            .modal-lista-campeonatos {
                grid-template-columns: 1fr;
            }

            .botoes-topo {
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

            <!-- Modal para ver mais campeonatos -->
            <div id="modalCampeonatos" class="modal-campeonatos">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3><i class="fas fa-trophy"></i> Campeonatos Inscritos</h3>
                        <span class="close-modal">&times;</span>
                    </div>
                    <div id="modalCampeonatosContent">
                        <!-- Conteúdo será preenchido via JavaScript -->
                    </div>
                </div>
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
                                <th><i class="fas fa-trophy"></i> Campeonatos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alunos as $aluno):
                                $idade = calcularIdade($aluno->data_nascimento);
                                $campeonatos = obterCampeonatosAluno($atletaService, $aluno->id);
                                $totalCampeonatos = count($campeonatos);
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($aluno->nome); ?></strong><br>
                                        <small><i class="fas fa-envelope"></i>
                                            <?php echo htmlspecialchars($aluno->email); ?></small><br>
                                        <small><i class="fas fa-phone"></i>
                                            <?php echo htmlspecialchars($aluno->fone); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($idade); ?> anos</td>
                                    <td><span class="badge-faixa"><?php echo htmlspecialchars($aluno->faixa); ?></span></td>
                                    <td><?php echo htmlspecialchars($aluno->peso); ?> kg</td>
                                    <td>
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
                                            <span class="sem-campeonatos">Nenhum campeonato</span>
                                        <?php endif; ?>
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
        // Modal para ver campeonatos
        const modal = document.getElementById('modalCampeonatos');
        const closeBtn = document.querySelector('.close-modal');

        function abrirModalCampeonatos(alunoId, alunoNome) {
            // Fazer requisição AJAX para obter campeonatos do aluno
            fetch(`/api/campeonatos_aluno.php?aluno_id=${alunoId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let html = `<h4>Aluno: ${alunoNome}</h4>`;

                        if (data.campeonatos.length > 0) {
                            html += '<div class="modal-lista-campeonatos">';
                            data.campeonatos.forEach(camp => {
                                const dataFormatada = new Date(camp.dia).toLocaleDateString('pt-BR');
                                const statusClass = camp.status_pagamento.toLowerCase() === 'received' ? 'pago' :
                                    (camp.status_pagamento.toLowerCase() === 'pending' ? 'pendente' : 'outro');

                                html += `
                                    <div class="modal-campeonato-item">
                                        <div class="campeonato-nome">${camp.campeonato}</div>
                                        <div class="campeonato-info">
                                            <div><i class="fas fa-calendar"></i> ${dataFormatada}</div>
                                            <div><i class="fas fa-map-marker-alt"></i> ${camp.lugar}</div>
                                        </div>
                                        <div class="campeonato-info">
                                            <div>Modalidade: ${camp.modalidade}</div>
                                            <div>
                                                <span class="status-pagamento status-${statusClass}">
                                                    ${camp.status_pagamento}
                                                </span>
                                            </div>
                                        </div>
                                        ${camp.valor_pago > 0 ? `<div><small>Valor: R$ ${camp.valor_pago.toFixed(2)}</small></div>` : ''}
                                    </div>
                                `;
                            });
                            html += '</div>';
                        } else {
                            html = '<p class="sem-campeonatos">Nenhum campeonato encontrado</p>';
                        }

                        document.getElementById('modalCampeonatosContent').innerHTML = html;
                        modal.style.display = 'block';
                    } else {
                        alert('Erro ao carregar campeonatos: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao carregar campeonatos');
                });
        }

        // Fechar modal
        closeBtn.onclick = function () {
            modal.style.display = 'none';
        }

        // Fechar modal ao clicar fora
        window.onclick = function (event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Melhorar experiência em dispositivos móveis
        document.addEventListener('DOMContentLoaded', function () {
            // Adicionar tooltips para informações truncadas
            const cells = document.querySelectorAll('.tabela-alunos td');
            cells.forEach(cell => {
                if (cell.scrollWidth > cell.offsetWidth) {
                    cell.title = cell.textContent;
                }
            });

            // Confirmar logout
            document.querySelectorAll('a[href*="logout"]').forEach(link => {
                link.addEventListener('click', function (e) {
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