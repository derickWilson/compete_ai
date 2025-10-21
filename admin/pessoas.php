<?php
require "../func/is_adm.php";
is_adm();
//checar se é admin

//incluir arquivo
require_once "../classes/atletaService.php";

try {
    $con = new Conexao();
    $at = new Atleta();
    $attServ = new atletaService($con, $at);
    $lista = $attServ->listAll();
} catch (Exception $e) {
    echo "<div class='erro'>Erro: " . $e->getMessage() . "</div>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/style.css">
    <title>Gerenciar Atletas - Sistema FPJJI</title>
    <style>
        /* ===== ESTILOS ESPECÍFICOS PARA PÁGINA DE ATLETAS ===== */
        .principal {
            padding: 20px;
            margin: 20px;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            min-height: 70vh;
        }

        .titulo-pagina {
            text-align: center;
            color: var(--primary-dark);
            margin-bottom: 25px;
            font-size: 1.8rem;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--accent);
            font-weight: 700;
        }

        /* ===== TABELA ESTILIZADA ===== */
        .tabela-container {
            overflow-x: auto;
            margin: 20px 0;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
        }

        th {
            background: var(--primary);
            color: var(--white);
            padding: 16px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 14px 12px;
            border-bottom: 1px solid #eaeaea;
            color: var(--dark);
            font-size: 14px;
        }

        tr:nth-child(even) {
            background-color: #f8fafc;
        }

        tr:hover {
            background-color: #f1f5f9;
            transition: background-color 0.2s ease;
        }

        /* ===== STATUS ===== */
        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            display: inline-block;
            min-width: 70px;
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

        /* ===== BOTÕES ===== */
        .btn-acao {
            padding: 8px 16px;
            background: var(--primary);
            color: var(--white);
            text-decoration: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-block;
            text-align: center;
        }

        .btn-acao:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(26, 54, 93, 0.2);
        }

        /* Estilo para donos de academia */
        tr.dono-academia {
            background-color: #e8f5e8 !important;
            border-left: 4px solid #28a745;
        }

        tr.dono-academia:hover {
            background-color: #d4edda !important;
        }

        tr.dono-academia td {
            font-weight: 600;
        }

        /* ===== RESPONSIVIDADE ===== */
        @media (max-width: 768px) {
            .principal {
                padding: 15px;
                margin: 15px;
            }

            .titulo-pagina {
                font-size: 1.5rem;
                margin-bottom: 20px;
            }

            th,
            td {
                padding: 12px 8px;
                font-size: 13px;
            }

            th {
                font-size: 12px;
            }

            .status {
                padding: 4px 8px;
                font-size: 11px;
                min-width: 60px;
            }

            .btn-acao {
                padding: 6px 12px;
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {
            .tabela-container {
                margin: 15px -10px;
                width: calc(100% + 20px);
            }

            table {
                font-size: 12px;
            }

            th,
            td {
                padding: 10px 6px;
            }
        }

        /* ===== ESTADOS ESPECIAIS ===== */
        .sem-registros {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray);
            font-style: italic;
            background: var(--light);
            border-radius: 10px;
            margin: 20px 0;
        }

        .loading {
            text-align: center;
            padding: 30px;
            color: var(--gray);
        }

        /* Melhorar contraste para acessibilidade */
        .contraste {
            color: var(--dark) !important;
        }

        .destaque {
            font-weight: 600;
            color: var(--primary-dark);
        }
    </style>
</head>

<body>
    <?php include "../menu/add_menu.php"; ?>

    <div class="principal">
        <h1 class="titulo-pagina">👥 Gerenciamento de Atletas</h1>

        <div class="tabela-container">
            <?php if (empty($lista)): ?>
                <div class="sem-registros">
                    <p>Nenhum atleta cadastrado no sistema.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Faixa</th>
                            <th>Academia</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lista as $value) { ?>
                            <tr class="<?php echo $value->responsavel == 1 ? 'dono-academia' : ''; ?>">
                                <td class="destaque"><?php echo htmlspecialchars($value->nome); ?></td>
                                <td><?php echo htmlspecialchars($value->faixa); ?></td>
                                <td><?php echo htmlspecialchars($value->academia); ?></td>
                                <td>
                                    <span
                                        class="status <?php echo $value->validado == 1 ? 'status-validado' : 'status-pendente'; ?>">
                                        <?php echo $value->validado == 1 ? 'Validado' : 'Pendente'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="controle.php?user=<?php echo htmlspecialchars($value->id, ENT_QUOTES, 'UTF-8'); ?>"
                                        class="btn-acao">
                                        Ver Detalhes
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <?php include "../menu/footer.php"; ?>

    <script>
        // Melhorar experiência em dispositivos móveis
        document.addEventListener('DOMContentLoaded', function () {
            // Adicionar tooltips para informações truncadas em mobile
            const cells = document.querySelectorAll('td');
            cells.forEach(cell => {
                if (cell.scrollWidth > cell.offsetWidth) {
                    cell.title = cell.textContent;
                }
            });

            // Feedback visual para cliques
            const links = document.querySelectorAll('.btn-acao');
            links.forEach(link => {
                link.addEventListener('click', function (e) {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });
        });
    </script>
</body>

</html>