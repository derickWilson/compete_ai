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
    require_once __DIR__ . '/config_taxa.php';
} catch (\Throwable $th) {
    print ('[' . $th->getMessage() . ']');
}

// Inicializa conexão e serviços
$conn = new Conexao();
$ev = new Evento();
$evserv = new eventosService($conn, $ev);
$tudo = true;

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
    $eventoDetails = $evserv->getById($eventoId);
    if (!$eventoDetails) {
        die("Evento não encontrado ou erro na consulta");
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
    </style>
</head>

<body>
    <?php include "menu/add_menu.php"; ?>

    <?php if ($tudo) { ?>
        <!-- Lista todos os eventos -->
        <?php foreach ($list as $valor) { ?>
            <div class="campeonato <?php echo ($valor->normal) ? 'evento-normal' : ''; ?>">
                <img src="uploads/<?php echo $valor->imagen; ?>" alt="Imagem" class='mini-banner'>
                <a href='eventos.php?id=<?php echo $valor->id ?>' class='clear'>
                    <h2>
                        <?php echo htmlspecialchars($valor->nome); ?>
                        <?php if ($normal = $eventoDetails->normal ?? false) { ?>
                            <span class="badge-normal">(Evento Normal)</span>
                        <?php } ?>
                    </h2>
                </a>

                <?php if (isset($_SESSION['admin']) && $_SESSION['admin']) { ?>
                    | <a href='admin/lista_inscritos.php?id=<?php echo $valor->id ?>'>Ver Inscritos</a>
                    | <a href='admin/editar_evento.php?id=<?php echo $valor->id ?>'>Editar Evento</a>
                    <div class="chapa-options">
                        Gerar Chapas:
                        <a href='admin/baixar_chapa.php?id=<?php echo $valor->id ?>'>CSV</a> |
                        <a href='admin/baixar_chapa.php?id=<?php echo $valor->id ?>&pdf=1'>PDF</a>
                    </div>
                <?php } ?>

                <br class='clear'>
            </div>
        <?php } ?>

        <br><a href="index.php">Voltar</a>

    <?php } else { ?>
        <!-- Detalhes de um único evento -->
        <?php if (isset($eventoDetails)) { ?>
            <div class='principal <?php echo ($eventoDetails->normal) ? 'evento-normal' : ''; ?>'>
                <h1><?php echo htmlspecialchars($eventoDetails->nome); ?>
                    <?php if ($eventoDetails->normal) { ?>
                        <span class="badge-normal">(Evento Normal)</span>
                    <?php } ?>
                </h1>

                <!-- Seção de imagem do evento -->
                <div class="imagem-container">
                    <img src="uploads/<?php echo $eventoDetails->imagen; ?>" alt="Imagem do Evento">
                </div>

                <!-- Informações básicas do evento -->
                <p>Descrição: <?php echo htmlspecialchars($eventoDetails->descricao); ?></p>
                <p>Data: <?php echo htmlspecialchars($eventoDetails->data_evento); ?></p>
                <p>Local: <?php echo htmlspecialchars($eventoDetails->local_camp); ?></p>

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
                            echo "<p>Preço geral: <strong>" . number_format($eventoDetails->preco * TAXA, 2, ',', '.') . " R$</strong> (maiores de 15 anos)</p>";
                            echo "<p>Preço para menores: <strong>" . number_format($eventoDetails->preco_menor * TAXA, 2, ',', '.') . " R$</strong> (menores de 15 anos)</p>";
                            echo "<p>Preço absoluto: <strong>" . number_format($eventoDetails->preco_abs * TAXA, 2, ',', '.') . " R$</strong></p>";
                        } else {
                            // Usuário logado - mostra preço conforme idade
                            if ($_SESSION["idade"] > 15) {
                                echo "<p>Preço: <strong>" . number_format($eventoDetails->preco * TAXA, 2, ',', '.') . " R$</strong></p>";
                                echo "<p>Preço absoluto: <strong>" . number_format($eventoDetails->preco_abs * TAXA, 2, ',', '.') . " R$</strong></p>";
                            } else {
                                echo "<p>Preço: <strong>" . number_format($eventoDetails->preco_menor * TAXA, 2, ',', '.') . " R$</strong></p>";
                            }
                        }
                    }
                    ?>
                </div>

                <!-- Link para download do edital -->
                <?php if (!empty($eventoDetails->doc)) { ?>
                    <p><a href="<?php echo '/docs/' . htmlspecialchars($eventoDetails->doc); ?>" download>Baixar Edital</a></p>
                <?php } else { ?>
                    <p><em>Edital não disponível</em></p>
                <?php } ?>

                <!-- Formulário de inscrição -->
                <?php if (isset($_SESSION['logado']) && $_SESSION['logado']) { ?>
                    <?php if (!$evserv->isInscrito($_SESSION["id"], $eventoId)) { ?>
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
                                    <input type="checkbox" name="com" checked onclick="return false;" style="pointer-events: none;"> Com Kimono
                                    <?php if ($_SESSION["idade"] > 15) { ?>
                                        <input type="checkbox" name="abs_com"> Absoluto Com Kimono
                                    <?php } ?>
                                <?php } ?>

                                <?php if ($eventoDetails->tipo_sem == 1) { ?>
                                    <input type="checkbox" name="sem"> Sem Kimono
                                    <?php if ($_SESSION["idade"] > 15) { ?>
                                        <input type="checkbox" name="abs_sem"> Absoluto Sem Kimono
                                    <?php } ?>
                                <?php } ?>
                            <?php } ?>

                            <br>
                            <?php if (!$eventoDetails->normal) {//mostrar modalidade se o evento não é normal
                                                    ?>
                                <select name="modalidade" required>
                                    <option value="">Selecione a modalidade</option>
                                    <option value="galo">Galo</option>
                                    <option value="pluma">Pluma</option>
                                    <option value="pena">Pena</option>
                                    <option value="leve">Leve</option>
                                    <option value="medio">Médio</option>
                                    <option value="meio-pesado">Meio-Pesado</option>
                                    <option value="pesado">Pesado</option>
                                    <option value="super-pesado">Super-Pesado</option>
                                    <option value="pesadissimo">Pesadíssimo</option>
                                    <?php if ($_SESSION["idade"] > 15) { ?>
                                        <option value="super-pesadissimo">Super-Pesadíssimo</option>
                                    <?php } ?>
                                </select>

                                <div class="termos">
                                    <input type="checkbox" name="aceite_regulamento" id="aceite_regulamento" required>
                                    <label for="aceite_regulamento">Li e aceito o regulamento</label>

                                    <br>

                                    <input type="checkbox" name="aceite_responsabilidade" id="aceite_responsabilidade" required>
                                    <label for="aceite_responsabilidade">Aceito os termos de responsabilidade</label>
                                </div>

                                <?php
                                                }//mostrar modalidade se o evento não é normal
                                                ?>
                            <input type="submit" value="Inscrever-se">
                        </form>
                    <?php } else { ?>
                        <p>Você já está inscrito neste evento.</p>
                    <?php } ?>
                <?php } else { ?>
                    <p>Faça <a href="/login.php">login</a> para se inscrever.</p>
                <?php } ?>

                <!-- Opções de administrador -->
                <?php if (isset($_SESSION['admin']) && $_SESSION['admin']) { ?>
                    <div class="chapa-options">
                        <h3>Opções de Administrador</h3>
                        <a href='admin/baixar_chapa.php?id=<?php echo $eventoId ?>'>Baixar Chapas (CSV)</a> |
                        <a href='admin/baixar_chapa.php?id=<?php echo $eventoId ?>&pdf=1'>Baixar Chapas (PDF)</a>
                    </div>
                <?php } ?>

                <!-- Visualizador de PDF -->
                <?php
                if (!$eventoDetails->normal) {
                    ?>
                    <div class="pdf-container">
                        <object data="tabela_de_pesos.pdf" type="application/pdf" width="100%" height="100%">
                            <p>Seu navegador não suporta PDFs. <a href="tabela_de_pesos.pdf">Baixe o arquivo</a>.</p>
                            i/object>
                    </div>
                    <?php
                }
                ?>

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
</body>

</html>