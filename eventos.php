<?php
// Inicia o buffer de saída e sessão
ob_start();
session_start();

// Carrega as dependências necessárias
try {
    require_once "classes/eventosServices.php";
    require_once "classes/AssasService.php";
    include "func/clearWord.php";
    require_once "func/calcularIdade.php";
    require_once "func/determinar_categoria.php";
    require_once __DIR__ . '/config_taxa.php';
    require_once __DIR__ . "/emails/notificar_evento.php";
    require_once __DIR__ . "/func/estatisticasInscricoes.php";
} catch (\Throwable $th) {
    print ('[' . $th->getMessage() . ']');
}

// Inicializa conexão e serviços
$conn = new Conexao();
$ev = new Evento();
$evserv = new eventosService($conn, $ev);
$tudo = true;

//notificar todos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'notificar_geral') {
    if (isset($_SESSION['admin']) && $_SESSION['admin'] == 1) {
        if (isset($_GET['id'])) {
            $idEvento = (int) cleanWords($_GET['id']);
            notificar_geral($idEvento);
        }
    }
}

if (isset($_SESSION['admin']) && $_SESSION['admin'] == 1) {
    // Verifica quando foi a última limpeza (evita executar em toda requisição)
    $ultimaLimpeza = $_SESSION['ultima_limpeza_eventos'] ?? 0;
    $intervaloLimpeza = 86400; // 24 horas em segundos

    if (time() - $ultimaLimpeza > $intervaloLimpeza) {
        try {
            // Executa a limpeza
            $resultado = $evserv->verificarELimparEventosExpirados();

            // Armazena resultado na sessão para exibição
            $_SESSION['limpeza_resultado'] = $resultado;

            // Atualiza o timestamp da última limpeza
            $_SESSION['ultima_limpeza_eventos'] = time();

        } catch (Exception $e) {
            error_log("Erro na limpeza automática: " . $e->getMessage());
        }
    }
}

// Verifica se foi solicitado um evento específico
if (isset($_GET['id'])) {
    $eventoId = (int) cleanWords($_GET['id']);
    try {
        $eventoDetails = $evserv->getById($eventoId);
        if (!$eventoDetails) {
            throw new Exception("Evento não encontrado");
        }
    } catch (Exception $e) {
        echo "<div class='error'>Erro: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<a href='eventos.php'>Voltar para lista de eventos</a>";
        include "menu/footer.php";
        exit();
    }
    //determinar categoria
    if (isset($_SESSION['logado']) && $_SESSION['logado']) {
        $categoriaAuto = determinarCategoriaPeso($_SESSION["peso"], $_SESSION["idade"], $_SESSION["genero"]);
        $categoriaAuto = strtolower(str_replace('_', '-', $categoriaAuto));
        $faixaEtaria = determinarFaixaEtaria($_SESSION["idade"]);
    }
    $tudo = false;
} else {
    // Se não foi especificado um ID, lista todos os eventos
    $list = $evserv->listAll();
}
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="/estilos/icone.jpeg">
    <title>Eventos</title>
    <style>
        /* Estilos CSS para a página */
        .pdf-container {
            height: 600px;
            margin: 20px 0;
        }

        .chapa-options {
            margin: 15px 0;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 5px;
        }

        .evento-normal {
            border-left: 5px solid #4CAF50;
            padding-left: 10px;
        }

        .badge-normal {
            background-color: #4CAF50;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8em;
        }

        /*estilo da tabela*/
        table,
        th,
        td {
            border: 1px solid black;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 6px;
        }

        .tabelas {
            max-width: 20%;
        }

        .tabela1 {
            float: 'left';
        }

        .tabela2 {
            float: 'left';
            margin-left: 2px;
        }
    </style>
</head>

<body>
    <?php include "menu/add_menu.php"; ?>

    <?php if ($tudo) { ?>
        <div class="container">
            <h2 class="section-title" style="color: white; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">Todos os Eventos</h2>

            <div class="eventos-grid">
                <!-- Lista todos os eventos -->
                <?php foreach ($list as $valor) { ?>
                    <div class="evento-card <?php echo ($valor->normal) ? 'evento-normal' : ''; ?>">
                        <div class="evento-imagem">
                            <img src="uploads/<?php echo $valor->imagen; ?>"
                                alt="<?php echo htmlspecialchars($valor->nome); ?>">
                        </div>
                        <div class="evento-conteudo">
                            <h3 class="evento-titulo"><?php echo htmlspecialchars($valor->nome); ?></h3>
                            <span class="evento-tipo">
                                <?php echo ($valor->normal) ? 'Evento Normal' : 'Campeonato'; ?>
                            </span>

                            <a href='eventos.php?id=<?php echo $valor->id ?>' class="botao-inscrever">
                                <i class="fas fa-info-circle"></i> Ver Detalhes
                            </a>

                            <?php if (isset($_SESSION['admin']) && $_SESSION['admin']) { ?>
                                <div class="admin-options">
                                    <a href='admin/lista_inscritos.php?id=<?php echo $valor->id ?>'>Ver Inscritos</a>
                                    <a href='admin/editar_evento.php?id=<?php echo $valor->id ?>'>Editar</a>
                                    <a href='admin/baixar_chapa.php?id=<?php echo $valor->id ?>'>Gerar chapa PDF</a>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <div class="voltar-container">
                <a href="index.php" class="botao-voltar">
                    <i class="fas fa-arrow-left"></i> Voltar para a Página Inicial
                </a>
            </div>
        </div>
    <?php } else { ?>
        <!-- Detalhes de um único evento -->
        <?php if (isset($eventoDetails)) { ?>
            <div class='principal <?php echo ($eventoDetails->normal) ? 'evento-normal' : ''; ?>'>
                <h1><?php echo htmlspecialchars($eventoDetails->nome); ?>
                    <?php if (isset($eventoDetails) && isset($_SESSION['admin']) && $_SESSION['admin'] == 1) { ?>
                        <form class="action-form" method="POST" onsubmit="return confirm('Notificar Todos')">
                            <input type="hidden" name="action" value="notificar_geral">
                            <button type="submit" class="action-btn pago-btn" title="Notificar Todos">
                                Notificar Todos
                            </button>
                        </form>
                    <?php } ?>
                    <?php if ($eventoDetails->normal) { ?>
                        <span class="badge-normal">(Evento Normal)</span>
                    <?php } ?>
                </h1>

                <!-- Seção de imagem do evento -->
                <div class="imagem-container">
                    <img src="uploads/<?php echo $eventoDetails->imagen; ?>" alt="Imagem do Evento"
                        style="max-width: 100%; height: auto; display: block; margin: 0 auto;">
                </div>

                <!-- Informações básicas do evento -->
                <div class="info-evento-simples">
                    <h3>Informações do Evento</h3>

                    <div class="info-linha">
                        <strong>📅 Data:</strong>
                        <span><?php echo date('d/m/Y', strtotime($eventoDetails->data_evento)); ?></span>
                    </div>

                    <div class="info-linha">
                        <strong>📍 Local:</strong>
                        <span><?php echo htmlspecialchars($eventoDetails->local_camp); ?></span>
                    </div>

                    <div class="info-linha">
                        <strong>📝 Descrição:<br></strong>
                        <span><?php echo htmlspecialchars($eventoDetails->descricao); ?></span>
                    </div>
                </div>
                <!-- Seção de Estatísticas de Inscrições -->
                <?php if (isset($_SESSION['logado']) && $_SESSION['logado']) {
                    // Obter estatísticas usando a função unificada
                    $estatisticas = obterEstatisticasInscricoes(
                        $evserv,
                        $eventoId,
                        $_SESSION["idade"],
                        $_SESSION["faixa"],
                        $categoriaAuto,
                        $faixaEtaria
                    );

                    // Verificar se há erro
                    if (isset($estatisticas['erro'])) {
                        echo '<div class="error">Erro ao carregar estatísticas: ' . htmlspecialchars($estatisticas['erro']) . '</div>';
                    } else {
                        echo '<div>';
                        echo '<h3>📊 Estatísticas de Inscrições Na Sua Categoria</h3>';
                        echo renderizarEstatisticas($estatisticas);
                        echo '</div>';
                    }
                } ?>

                <!-- Seção de preços -->
                <div class="precos-container">
                    <h3>Valores</h3>
                    <?php
                    if ($eventoDetails->normal) {
                        // Exibição para Evento Normal
                        $precoNormal = $eventoDetails->normal_preco * TAXA;
                        echo "<p>Preço único: <strong>" . number_format($precoNormal, 2, ',', '.') . " R$</strong></p>";
                    } else {
                        // Exibição para Evento com Classificação
                        if (!isset($_SESSION["idade"])) {
                            // Usuário não logado - mostra todos os preços
            
                            // Preços COM Kimono
                            echo "<h4>COM Kimono:</h4>";
                            echo "<p>Maiores de 15: <strong>" . number_format($eventoDetails->preco * TAXA, 2, ',', '.') . " R$</strong></p>";
                            echo "<p>Menores de 15: <strong>" . number_format($eventoDetails->preco_menor * TAXA, 2, ',', '.') . " R$</strong></p>";
                            echo "<p>Absoluto: <strong>" . number_format($eventoDetails->preco_abs * TAXA, 2, ',', '.') . " R$</strong></p>";

                            // Preços SEM Kimono (se o evento tiver essa modalidade)
                            if ($eventoDetails->tipo_sem == 1) {
                                echo "<h4>SEM Kimono:</h4>";
                                echo "<p>Maiores de 15: <strong>" . number_format($eventoDetails->preco_sem * TAXA, 2, ',', '.') . " R$</strong></p>";
                                echo "<p>Menores de 15: <strong>" . number_format($eventoDetails->preco_sem_menor * TAXA, 2, ',', '.') . " R$</strong></p>";
                                echo "<p>Absoluto: <strong>" . number_format($eventoDetails->preco_sem_abs * TAXA, 2, ',', '.') . " R$</strong></p>";
                            }
                        } else {
                            // Usuário logado - mostra preço conforme idade
                            if ($_SESSION["idade"] > 15) {
                                echo "<h4>COM Kimono:</h4>";
                                echo "<p>Preço: <strong>" . number_format($eventoDetails->preco * TAXA, 2, ',', '.') . " R$</strong></p>";
                                echo "<p>Absoluto: <strong>" . number_format($eventoDetails->preco_abs * TAXA, 2, ',', '.') . " R$</strong></p>";

                                // Preços SEM Kimono (se o evento tiver essa modalidade)
                                if ($eventoDetails->tipo_sem == 1) {
                                    echo "<h4>SEM Kimono:</h4>";
                                    echo "<p>Preço: <strong>" . number_format($eventoDetails->preco_sem * TAXA, 2, ',', '.') . " R$</strong></p>";
                                    echo "<p>Absoluto: <strong>" . number_format($eventoDetails->preco_sem_abs * TAXA, 2, ',', '.') . " R$</strong></p>";
                                }
                            } else {
                                echo "<h4>COM Kimono:</h4>";
                                echo "<p>Preço: <strong>" . number_format($eventoDetails->preco_menor * TAXA, 2, ',', '.') . " R$</strong></p>";

                                // Preços SEM Kimono (se o evento tiver essa modalidade)
                                if ($eventoDetails->tipo_sem == 1) {
                                    echo "<h4>SEM Kimono:</h4>";
                                    echo "<p>Preço: <strong>" . number_format($eventoDetails->preco_sem_menor * TAXA, 2, ',', '.') . " R$</strong></p>";
                                }
                            }
                        }
                    }
                    ?>
                </div>

                <!-- Link para download do edital -->
                <?php if (!empty($eventoDetails->doc)) { ?>
                    <p><a href="<?php echo '/docs/' . htmlspecialchars($eventoDetails->doc); ?>" download>Baixar Edital</a>
                    </p>
                <?php } else { ?>
                    <p><em>Edital não disponível</em></p>
                <?php } ?>
                <!-- Link para download do chaveamento -->
                <?php if (!empty($eventoDetails->chaveamento)) { ?>
                    <p><a href="<?php echo '/docs/' . htmlspecialchars($eventoDetails->chaveamento); ?>" download>Baixar
                            Chaveamento</a></p>
                <?php } else { ?>
                    <p><em>Chaveamento não disponível</em></p>
                <?php } ?>
                <!-- Formulário de inscrição -->
                <?php
                // Primeiro verifica se as inscrições estão abertas ou encerradas
                $limite = new DateTime($eventoDetails->data_limite);
                $limite->modify('+1 day');
                $hoje = new DateTime();
                $inscricoesEncerradas = ($hoje >= $limite);

                if ($inscricoesEncerradas) {
                    // INSCRIÇÕES ENCERRADAS - data limite já passou
                    echo '<div class="aviso error" style="text-align: center; padding: 15px; margin: 10px 0;">';
                    echo '📅 <strong>Inscrições encerradas</strong><br>';
                    echo 'O prazo para inscrições terminou em ' . date('d/m/Y', strtotime($eventoDetails->data_limite));
                    echo '</div>';

                } else {
                    // INSCRIÇÕES ABERTAS - data limite ainda não chegou
        
                    if (isset($_SESSION['logado']) && $_SESSION['logado']) {
                        // USUÁRIO LOGADO
        
                        if (!$evserv->isInscrito($_SESSION["id"], $eventoId)) {
                            // USUÁRIO NÃO INSCRITO - mostra formulário
                            ?>
                            <form action="inscreverAtleta.php" method="POST">
                                <input type="hidden" name="evento_id" value="<?php echo htmlspecialchars($eventoDetails->id); ?>">

                                <?php if ($eventoDetails->normal) { ?>
                                    <input type="hidden" name="valor" value="<?php echo htmlspecialchars($eventoDetails->normal_preco); ?>">
                                    <p>Este é um evento normal sem distinção por idade ou modalidade.</p>
                                <?php } else { ?>
                                    <input type="hidden" name="valor" value="<?php
                                    echo ($_SESSION["idade"] > 15) ? htmlspecialchars($eventoDetails->preco) : htmlspecialchars($eventoDetails->preco_menor);
                                    ?>">

                                    <?php if ($eventoDetails->tipo_com == 1) { ?>
                                        <input type="checkbox" name="com"> Categoria (Com Kimono)
                                        <?php if ($_SESSION["idade"] > 15) { ?>
                                            <br><input type="checkbox" name="abs_com"> Categoria + Absoluto (Com Kimono)
                                        <?php } ?>
                                    <?php } ?>

                                    <?php if ($eventoDetails->tipo_sem == 1) { ?>
                                        <input type="checkbox" name="sem"> Categoria (Sem Kimono)
                                        <?php if ($_SESSION["idade"] > 15) { ?>
                                            <br><input type="checkbox" name="abs_sem"> Categoria + Absoluto (Sem Kimono)
                                        <?php } ?>
                                    <?php } ?>
                                <?php } ?>

                                <br>
                                <?php if (!$eventoDetails->normal) {
                                    ?>
                                    <select name="modalidade" required readonly>
                                        <option value="galo" <?= $categoriaAuto == 'galo' ? 'selected' : '' ?>>Galo</option>
                                        <option value="pluma" <?= $categoriaAuto == 'pluma' ? 'selected' : '' ?>>Pluma</option>
                                        <option value="pena" <?= $categoriaAuto == 'pena' ? 'selected' : '' ?>>Pena</option>
                                        <option value="leve" <?= $categoriaAuto == 'leve' ? 'selected' : '' ?>>Leve</option>
                                        <option value="medio" <?= $categoriaAuto == 'medio' ? 'selected' : '' ?>>Médio</option>
                                        <option value="meio-pesado" <?= $categoriaAuto == 'meio-pesado' ? 'selected' : '' ?>>Meio-Pesado
                                        </option>
                                        <option value="pesado" <?= $categoriaAuto == 'pesado' ? 'selected' : '' ?>>Pesado</option>
                                        <option value="super-pesado" <?= $categoriaAuto == 'super-pesado' ? 'selected' : '' ?>>Super-Pesado
                                        </option>
                                        <option value="pesadissimo" <?= $categoriaAuto == 'pesadissimo' ? 'selected' : '' ?>>Pesadíssimo
                                        </option>
                                        <?php if ($_SESSION["idade"] > 15) { ?>
                                            <option value="super-pesadissimo" <?= $categoriaAuto == 'super-pesadissimo' ? 'selected' : '' ?>>
                                                Super-Pesadíssimo</option>
                                        <?php } ?>
                                    </select>


                                    <div class="termos">
                                        <input type="checkbox" name="aceite_regulamento" id="aceite_regulamento" required>
                                        <label for="aceite_regulamento">Li e aceito o regulamento</label>

                                        <br>

                                        <input type="checkbox" name="aceite_responsabilidade" id="aceite_responsabilidade" required>
                                        <label for="aceite_responsabilidade">Aceito os termos de responsabilidade</label>
                                    </div>
                                <?php } ?>

                                <input type="submit" value="Inscrever-se" class="botao-inscrever">
                            </form>

                            <!-- Seção de Estatísticas Gerais do Evento -->
                            <?php if (isset($eventoDetails) && (isset($_SESSION['admin']) || isset($_SESSION['logado']))) { ?>
                                <div class="estatisticas-gerais">
                                    <h3>📊 Estatísticas Gerais do Evento</h3>

                                    <?php
                                    try {
                                        // Obter todos os inscritos no evento
                                        $inscritos = $evserv->getInscritos($eventoId);

                                        if (empty($inscritos)) {
                                            echo '<p class="aviso info">Nenhum inscrito encontrado para este evento.</p>';
                                        } else {
                                            // Inicializar arrays para estatísticas
                                            $estatisticas = [
                                                'total_inscritos' => 0,
                                                'por_faixa' => [],
                                                'por_faixa_etaria' => [],
                                                'por_categoria_peso' => [],
                                                'por_genero' => [],
                                                'por_modalidade' => [
                                                    'com_kimono' => 0,
                                                    'absoluto_com' => 0
                                                ],
                                                'por_academia' => []
                                            ];

                                            // Processar cada inscrito
                                            foreach ($inscritos as $inscrito) {
                                                $estatisticas['total_inscritos']++;

                                                // Estatísticas por faixa
                                                $faixa = $inscrito->faixa ?? 'Não informada';
                                                $estatisticas['por_faixa'][$faixa] = ($estatisticas['por_faixa'][$faixa] ?? 0) + 1;

                                                // Calcular faixa etária
                                                $idade = calcularIdade($inscrito->data_nascimento);
                                                $faixa_etaria = determinarFaixaEtaria($idade);
                                                $estatisticas['por_faixa_etaria'][$faixa_etaria] = ($estatisticas['por_faixa_etaria'][$faixa_etaria] ?? 0) + 1;

                                                // Estatísticas por categoria de peso
                                                if (!empty($inscrito->modalidade)) {
                                                    $estatisticas['por_categoria_peso'][$inscrito->modalidade] = ($estatisticas['por_categoria_peso'][$inscrito->modalidade] ?? 0) + 1;
                                                }

                                                // Estatísticas por gênero
                                                $genero = $inscrito->genero ?? 'Não informado';
                                                $estatisticas['por_genero'][$genero] = ($estatisticas['por_genero'][$genero] ?? 0) + 1;

                                                // Estatísticas por modalidade - APENAS COM KIMONO
                                                if ($inscrito->mod_com)
                                                    $estatisticas['por_modalidade']['com_kimono']++;
                                                if ($inscrito->mod_ab_com)
                                                    $estatisticas['por_modalidade']['absoluto_com']++;

                                                // Estatísticas por academia
                                                $academia = $inscrito->academia ?? 'Não informada';
                                                $estatisticas['por_academia'][$academia] = ($estatisticas['por_academia'][$academia] ?? 0) + 1;
                                            }

                                            // Função auxiliar para exibir tabelas
                                            function exibirTabelaEstatisticas($titulo, $dados, $coluna1 = 'Item', $coluna2 = 'Quantidade')
                                            {
                                                if (empty($dados))
                                                    return '';

                                                $html = '<div class="tabela-estatistica">';
                                                $html .= '<h4>' . htmlspecialchars($titulo) . '</h4>';
                                                $html .= '<table>';
                                                $html .= '<tr><th>' . htmlspecialchars($coluna1) . '</th><th>' . htmlspecialchars($coluna2) . '</th></tr>';

                                                foreach ($dados as $item => $quantidade) {
                                                    $html .= '<tr>';
                                                    $html .= '<td>' . htmlspecialchars($item) . '</td>';
                                                    $html .= '<td>' . htmlspecialchars($quantidade) . '</td>';
                                                    $html .= '</tr>';
                                                }

                                                $html .= '</table></div>';
                                                return $html;
                                            }
                                            ?>
                
                                                <!-- Resumo Geral -->
                                                <div class="resumo-geral">
                                                    <h4>📈 Resumo Geral</h4>
                                                    <div class="resumo-cards">
                                                        <div class="resumo-card">
                                                            <span class="numero"><?php echo $estatisticas['total_inscritos']; ?></span>
                                                            <span class="label">Total de Inscritos</span>
                                                        </div>
                                                        <div class="resumo-card">
                                                            <span class="numero"><?php echo $estatisticas['por_modalidade']['com_kimono']; ?></span>
                                                            <span class="label">Com Kimono</span>
                                                        </div>
                                                        <div class="resumo-card">
                                                            <span class="numero"><?php echo $estatisticas['por_modalidade']['absoluto_com']; ?></span>
                                                            <span class="label">Absoluto</span>
                                                        </div>
                                                        <div class="resumo-card">
                                                            <span class="numero"><?php echo count($estatisticas['por_academia']); ?></span>
                                                            <span class="label">Academias</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Grid de Estatísticas Detalhadas -->
                                                <div class="estatisticas-grid">
                                                    <?php
                                                    // Exibir todas as tabelas de estatísticas
                                                    echo exibirTabelaEstatisticas('🎯 Por Faixa', $estatisticas['por_faixa'], 'Faixa', 'Atletas');
                                                    echo exibirTabelaEstatisticas('👥 Por Faixa Etária', $estatisticas['por_faixa_etaria'], 'Faixa Etária', 'Atletas');
                                                    echo exibirTabelaEstatisticas('⚖️ Por Categoria de Peso', $estatisticas['por_categoria_peso'], 'Categoria', 'Atletas');
                                                    echo exibirTabelaEstatisticas('🚻 Por Gênero', $estatisticas['por_genero'], 'Gênero', 'Atletas');

                                                    // Estatísticas de modalidade formatadas - APENAS COM KIMONO
                                                    $modalidades_formatadas = [
                                                        'Com Kimono' => $estatisticas['por_modalidade']['com_kimono'],
                                                        'Absoluto Com Kimono' => $estatisticas['por_modalidade']['absoluto_com']
                                                    ];
                                                    echo exibirTabelaEstatisticas('🥋 Por Modalidade', $modalidades_formatadas, 'Modalidade', 'Atletas');
                                                    ?>
                                                </div>

                                                <!-- Top Academias -->
                                                <?php if (!empty($estatisticas['por_academia'])) { ?>
                                                        <div class="top-academias">
                                                            <h4>🏆 Top Academias</h4>
                                                            <div class="ranking-academias">
                                                                <?php
                                                                // Ordenar academias por quantidade (decrescente)
                                                                arsort($estatisticas['por_academia']);
                                                                $contador = 0;

                                                                foreach ($estatisticas['por_academia'] as $academia => $quantidade) {
                                                                    if ($contador >= 10)
                                                                        break; // Mostrar apenas top 10
                                                                    $contador++;

                                                                    echo '<div class="academia-item">';
                                                                    echo '<span class="posicao">#' . $contador . '</span>';
                                                                    echo '<span class="nome-academia">' . htmlspecialchars($academia) . '</span>';
                                                                    echo '<span class="quantidade-academia">' . $quantidade . ' atletas</span>';
                                                                    echo '</div>';
                                                                }
                                                                ?>
                                                            </div>
                                                        </div>
                                                <?php } ?>
                
                                        <?php } ?>
                                <?php } catch (Exception $e) { ?>
                                        <p class="aviso error">Erro ao carregar estatísticas: <?php echo htmlspecialchars($e->getMessage()); ?></p>
                                <?php } ?>
                            </div>

                            <style>
                                .estatisticas-gerais {
                                    margin: 30px 0;
                                    padding: 20px;
                                    background: #f9f9f9;
                                    border-radius: 10px;
                                    border: 1px solid #ddd;
                                }

                                .resumo-cards {
                                    display: grid;
                                    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                                    gap: 15px;
                                    margin: 20px 0;
                                }

                                .resumo-card {
                                    background: white;
                                    padding: 20px;
                                    border-radius: 8px;
                                    text-align: center;
                                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                                    border-left: 4px solid #4CAF50;
                                }

                                .resumo-card .numero {
                                    display: block;
                                    font-size: 2em;
                                    font-weight: bold;
                                    color: #333;
                                }

                                .resumo-card .label {
                                    font-size: 0.9em;
                                    color: #666;
                                }

                                .estatisticas-grid {
                                    display: grid;
                                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                                    gap: 20px;
                                    margin: 20px 0;
                                }

                                .tabela-estatistica {
                                    background: white;
                                    padding: 15px;
                                    border-radius: 8px;
                                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                                }

                                .tabela-estatistica table {
                                    width: 100%;
                                    border-collapse: collapse;
                                }

                                .tabela-estatistica th,
                                .tabela-estatistica td {
                                    padding: 8px 12px;
                                    text-align: left;
                                    border-bottom: 1px solid #ddd;
                                }

                                .tabela-estatistica th {
                                    background-color: #f5f5f5;
                                    font-weight: bold;
                                }

                                .top-academias {
                                    margin-top: 30px;
                                }

                                .ranking-academias {
                                    display: grid;
                                    gap: 10px;
                                    margin-top: 15px;
                                }

                                .academia-item {
                                    display: flex;
                                    justify-content: space-between;
                                    align-items: center;
                                    padding: 10px 15px;
                                    background: white;
                                    border-radius: 6px;
                                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                                }

                                .posicao {
                                    font-weight: bold;
                                    color: #4CAF50;
                                    min-width: 40px;
                                }

                                .nome-academia {
                                    flex-grow: 1;
                                    margin: 0 15px;
                                }

                                .quantidade-academia {
                                    font-weight: bold;
                                    color: #333;
                                }

                                @media (max-width: 768px) {
                                    .resumo-cards {
                                        grid-template-columns: repeat(2, 1fr);
                                    }
            
                                    .estatisticas-grid {
                                        grid-template-columns: 1fr;
                                    }
                                }
                            </style>
                    <?php } ?>
                                                <?php
                        } else {
                            // USUÁRIO JÁ INSCRITO
                            echo '<p class="aviso info">Você já está inscrito neste evento.</p>';
                        }

                    } else {
                        // USUÁRIO NÃO LOGADO
                        echo '<p class="aviso info">Faça <a href="/login.php">login</a> para se inscrever.</p>';
                    }
                }
                ?>

                        <!-- Opções de administrador -->
                        <?php if (isset($_SESSION['admin']) && $_SESSION['admin']) { ?>
                                <div class="chapa-options">
                                    <h3>Opções de Administrador</h3>
                                    <a href='admin/baixar_chapa.php?id=<?php echo $eventoId ?>'>Baixar Chapas (PDF)</a> |
                                </div>
                        <?php } ?>
                        <br>
                        <a href="eventos.php" class="link">Voltar</a>
                        <?php if (isset($_SESSION['admin']) && $_SESSION["admin"] == 1) { ?>
                                || <a href='/admin/editar_evento.php?id=<?php echo $eventoId ?>'>Editar Evento</a>
                        <?php } ?>
                    </div>
            <?php } else { ?>
                    <p>Evento não encontrado.</p>
                    <a href="eventos.php">Voltar</a>
            <?php } ?>
    <?php } ?>

    <?php include "menu/footer.php"; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Grupos de checkboxes que são mutuamente exclusivos
            const gruposExclusivos = [
                ['com', 'abs_com'],     // Categoria Com Kimono vs Absoluto Com Kimono
                ['sem', 'abs_sem']      // Categoria Sem Kimono vs Absoluto Sem Kimono
            ];

            // Para cada grupo de exclusividade
            gruposExclusivos.forEach(grupo => {
                const checkboxes = grupo.map(name => document.querySelector(`input[name="${name}"]`));

                // Adiciona evento a cada checkbox do grupo
                checkboxes.forEach(checkbox => {
                    if (checkbox) {
                        checkbox.addEventListener('change', function () {
                            if (this.checked) {
                                // Se este foi marcado, desmarca os outros do mesmo grupo
                                checkboxes.forEach(otherCheckbox => {
                                    if (otherCheckbox !== this && otherCheckbox) {
                                        otherCheckbox.checked = false;
                                    }
                                });
                            }
                        });
                    }
                });
            });

            // Validação no envio do formulário - verifica se pelo menos uma modalidade foi selecionada
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function (e) {
                    // Verifica se pelo menos uma modalidade principal foi selecionada
                    const comSelecionado = document.querySelector('input[name="com"]:checked');
                    const semSelecionado = document.querySelector('input[name="sem"]:checked');
                    const absComSelecionado = document.querySelector('input[name="abs_com"]:checked');
                    const absSemSelecionado = document.querySelector('input[name="abs_sem"]:checked');

                    // Se nenhuma modalidade foi selecionada
                    if (!comSelecionado && !semSelecionado && !absComSelecionado && !absSemSelecionado) {
                        e.preventDefault();
                        alert('Por favor, selecione pelo menos uma modalidade');
                        return false;
                    }

                    return true;
                });
            }

            // Validação adicional: se selecionar absoluto, verifica se é maior de 15 anos
            const checkboxesAbsoluto = document.querySelectorAll('input[name="abs_com"], input[name="abs_sem"]');
            checkboxesAbsoluto.forEach(checkbox => {
                if (checkbox) {
                    checkbox.addEventListener('change', function () {
                        if (this.checked) {
                            // Verifica se a idade está disponível na sessão (via PHP)
                            const idade = <?php echo isset($_SESSION['idade']) ? $_SESSION['idade'] : 0; ?>;
                            if (idade <= 15) {
                                alert('Absoluto disponível apenas para maiores de 15 anos');
                                this.checked = false;
                            }
                        }
                    });
                }
            });
        });
    </script>
</body>

</html>