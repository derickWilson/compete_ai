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
/**
 * Calcula o tempo de filiação de um atleta
 * 
 * @param string $dataFiliacao Data de filiação no formato Y-m-d
 * @return string Tempo formatado (ex: "2 anos, 3 meses, 15 dias" ou "6 meses, 10 dias")
 */
function calcularTempoFiliacao($dataFiliacao)
{
    if (empty($dataFiliacao) || $dataFiliacao == '0000-00-00') {
        return 'Não informado';
    }
    
    $dataFiliacao = new DateTime($dataFiliacao);
    $hoje = new DateTime();
    
    if ($dataFiliacao > $hoje) {
        return 'Data inválida';
    }
    
    $diferenca = $dataFiliacao->diff($hoje);
    
    $anos = $diferenca->y;
    $meses = $diferenca->m;
    $dias = $diferenca->d;
    
    $resultado = [];
    
    if ($anos > 0) {
        $resultado[] = $anos . ($anos == 1 ? ' ano' : ' anos');
    }
    
    if ($meses > 0) {
        $resultado[] = $meses . ($meses == 1 ? ' mês' : ' meses');
    }
    
    if ($dias > 0 || empty($resultado)) {
        $resultado[] = $dias . ($dias == 1 ? ' dia' : ' dias');
    }
    
    return implode(', ', $resultado);
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

        /* ===== TEMPO DE FILIAÇÃO ===== */
        .tempo-filiacao {
            font-size: 13px;
            color: var(--gray);
            background-color: #f8f9fa;
            padding: 4px 8px;
            border-radius: 4px;
            border-left: 3px solid var(--primary);
            white-space: nowrap;
        }

        .tempo-novo {
            background-color: #e3f2fd;
            border-left-color: #2196f3;
            color: #1565c0;
            font-weight: 500;
        }

        .tempo-antigo {
            background-color: #e8f5e9;
            border-left-color: #4caf50;
            color: #2e7d32;
            font-weight: 500;
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
            
            .tempo-filiacao {
                font-size: 11px;
                padding: 3px 6px;
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
    <?php include "../include_hamburger.php"; ?>

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
                            <th>Tempo Filiado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lista as $value) { 
                            // Calcular tempo de filiação
                            $dataFiliacao = $value->data_filiacao ?? null;
                            $hoje = new DateTime();
                            $dataFiliacaoObj = new DateTime($dataFiliacao);
                            $diferenca = $dataFiliacaoObj->diff($hoje);
                            
                            $anos = $diferenca->y;
                            $meses = $diferenca->m;
                            $dias = $diferenca->d;
                            
                            // Formatar o tempo
                            $tempoFormatado = '';
                            if ($anos > 0) {
                                $tempoFormatado .= $anos . ($anos == 1 ? ' ano' : ' anos');
                                if ($meses > 0 || $dias > 0) {
                                    $tempoFormatado .= ', ';
                                }
                            }
                            
                            if ($meses > 0) {
                                $tempoFormatado .= $meses . ($meses == 1 ? ' mês' : ' meses');
                                if ($dias > 0) {
                                    $tempoFormatado .= ', ';
                                }
                            }
                            
                            if ($dias > 0 || ($anos == 0 && $meses == 0)) {
                                $tempoFormatado .= $dias . ($dias == 1 ? ' dia' : ' dias');
                            }
                            
                            // Determinar classe CSS baseada no tempo
                            $classeTempo = 'tempo-filiacao';
                            if ($anos > 0) {
                                $classeTempo .= ' tempo-antigo';
                            } elseif ($meses < 3) {
                                $classeTempo .= ' tempo-novo';
                            }
                        ?>
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
                                    <?php if ($dataFiliacao): ?>
                                        <span class="<?php echo $classeTempo; ?>" 
                                              title="Filiado desde: <?php echo date('d/m/Y', strtotime($dataFiliacao)); ?>">
                                            <?php echo $tempoFormatado; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="tempo-filiacao">Não informado</span>
                                    <?php endif; ?>
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
            
            // Adicionar tooltip de data de filiação
            const tempoSpans = document.querySelectorAll('.tempo-filiacao');
            tempoSpans.forEach(span => {
                span.addEventListener('mouseenter', function() {
                    // Tooltip já está no atributo title
                });
            });
        });
    </script>
</body>

</html>