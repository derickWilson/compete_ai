<?php
ob_start();
session_start();

try {
    require_once "classes/eventosServices.php";
    require_once "classes/AssasService.php";
    include "func/clearWord.php";
    include "func/calcularIdade.php";
    include "config_taxa.php";
} catch (\Throwable $th) {
    print('['. $th->getMessage() .']');
}

$conn = new Conexao();
$ev = new Evento();
$evserv = new eventosService($conn, $ev);
$tudo = true;    

if (isset($_GET['id'])) {
    $eventoId = (int) cleanWords($_GET['id']);
    $eventoDetails = $evserv->getById($eventoId);
    $tudo = false;
} else {
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
    </style>
</head>
<body>
    <?php include_once "menu/add_menu.php"; ?>
    
    <?php if ($tudo): ?>
        <!-- Lista todos os eventos -->
        <?php foreach ($list as $valor): ?>
            <div class="campeonato">
                <img src="uploads/<?php echo $valor->imagen; ?>" alt="Imagem" class='mini-banner'>
                <a href='eventos.php?id=<?php echo $valor->id ?>' class='clear'><h2>
                    <?php echo htmlspecialchars($valor->nome); ?></h2></a>
                    
                    <?php if (isset($_SESSION['admin']) && $_SESSION['admin']): ?>
                        | <a href='admin/lista_inscritos.php?id=<?php echo $valor->id ?>'>Ver Inscritos</a>
                        | <a href='admin/editar_evento.php?id=<?php echo $valor->id ?>'>Editar Evento</a>
                        <div class="chapa-options">
                            Gerar Chapas: 
                            <a href='admin/baixar_chapa.php?id=<?php echo $valor->id ?>'>CSV</a> | 
                            <a href='admin/baixar_chapa.php?id=<?php echo $valor->id ?>&pdf=1'>PDF</a>
                        </div>
                    <?php endif; ?>
                    
                    <br class='clear'>
            </div>
        <?php endforeach; ?>
        
        <br><a href="index.php">Voltar</a>
        
    <?php else: ?>
        <!-- Detalhes de um único evento -->
        <?php if (isset($eventoDetails)): ?>
            <div class='principal'>
                <h1><?php echo htmlspecialchars($eventoDetails->nome); ?></h1>
                <div class="imagem-container">
                    <img src="uploads/<?php echo $eventoDetails->imagen; ?>" alt="Imagem do Evento">
                </div>
                
                <p>Descrição: <?php echo htmlspecialchars($eventoDetails->descricao); ?></p>
                <p>Data: <?php echo htmlspecialchars($eventoDetails->data_evento); ?></p>
                <p>Local: <?php echo htmlspecialchars($eventoDetails->local_evento); ?></p>
                
                <p>Preço:
                <?php
                $taxa = 1;
                if(!isset($_SESSION["idade"])):
                    $precoComTaxa = $eventoDetails->preco * $taxa;
                    $precoMenorComTaxa = $eventoDetails->preco_menor * $taxa;
                    $precoAbsComTaxa = $eventoDetails->preco_abs * $taxa;
                
                    echo number_format($precoComTaxa, 2, ',', '.') . " R$ (maiores de 15 anos)<br>";
                    echo number_format($precoMenorComTaxa, 2, ',', '.') . " R$ (menores de 15 anos)<br>";
                    echo number_format($precoAbsComTaxa, 2, ',', '.') . " R$ (Absoluto)";
                else:
                    if($_SESSION["idade"] > 15):
                        $precoComTaxa = $eventoDetails->preco * $taxa;
                        $precoAbsComTaxa = $eventoDetails->preco_abs * $taxa;
                    
                        echo number_format($precoComTaxa, 2, ',', '.') . " R$";
                        echo "<br>Absoluto: " . number_format($precoAbsComTaxa, 2, ',', '.') . " R$";
                    else:
                        $precoMenorComTaxa = $eventoDetails->preco_menor * $taxa;
                        echo number_format($precoMenorComTaxa, 2, ',', '.') . " R$";
                    endif;
                endif;
                ?></p>

                <?php if (!empty($eventoDetails->doc)): ?>
                    <p><a href="<?php echo '/docs/' . htmlspecialchars($eventoDetails->doc); ?>" download>Baixar Edital</a></p>
                <?php else: ?>
                    <p><em>Edital não disponível</em></p>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['logado']) && $_SESSION['logado']): ?>
                    <?php if(!$evserv->isInscrito($_SESSION["id"], $eventoId)): ?>
                        <form action="inscreverAtleta.php" method="POST">
                            <input type="hidden" name="evento_id" value="<?php echo htmlspecialchars($eventoDetails->id); ?>">
                            <input type="hidden" name="valor" value="<?php 
                                echo ($_SESSION["idade"] > 15) ? htmlspecialchars($eventoDetails->preco) : htmlspecialchars($eventoDetails->preco_menor); 
                            ?>">
                            
                            <?php if ($eventoDetails->tipo_com == 1): ?>
                                <input type="checkbox" name="com" checked onclick="return false;" style="pointer-events: none;"> Com Kimono
                                <?php if($_SESSION["idade"] > 15): ?>
                                    <input type="checkbox" name="abs_com"> Absoluto Com Kimono
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if ($eventoDetails->tipo_sem == 1): ?>
                                <input type="checkbox" name="sem"> Sem Kimono
                                <?php if($_SESSION["idade"] > 15): ?>
                                    <input type="checkbox" name="abs_sem"> Absoluto Sem Kimono
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <br>
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
                                <?php if($_SESSION["idade"] > 15): ?>
                                    <option value="super-pesadissimo">Super-Pesadíssimo</option>
                                <?php endif; ?>
                            </select>

                            <div class="termos">
                                <input type="checkbox" name="aceite_regulamento" id="aceite_regulamento" required>
                                <label for="aceite_regulamento">Li e aceito o regulamento</label>
                                
                                <br>
                                
                                <input type="checkbox" name="aceite_responsabilidade" id="aceite_responsabilidade" required>
                                <label for="aceite_responsabilidade">Aceito os termos de responsabilidade</label>
                            </div>
                            
                            <input type="submit" value="Inscrever-se">
                        </form>
                    <?php else: ?>
                        <p>Você já está inscrito neste evento.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>Faça <a href="/login.php">login</a> para se inscrever.</p>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['admin']) && $_SESSION['admin']): ?>
                    <div class="chapa-options">
                        <h3>Opções de Administrador</h3>
                        <a href='admin/baixar_chapa.php?id=<?php echo $eventoId ?>'>Baixar Chapas (CSV)</a> | 
                        <a href='admin/baixar_chapa.php?id=<?php echo $eventoId ?>&pdf=1'>Baixar Chapas (PDF)</a>
                    </div>
                <?php endif; ?>
                
                <div class="pdf-container">
                    <object data="tabela_de_pesos.pdf" type="application/pdf" width="100%" height="100%">
                        <p>Seu navegador não suporta PDFs. <a href="tabela_de_pesos.pdf">Baixe o arquivo</a>.</p>
                    </object>
                </div>
                
                <br>
                <a href="eventos.php" class="link">Voltar</a>
                <?php if(isset($_SESSION['admin']) && $_SESSION["admin"] == 1): ?>
                    || <a href='/admin/editar_evento.php?id=<?php echo $eventoId ?>'>Editar Evento</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>Evento não encontrado.</p>
            <a href="eventos.php">Voltar</a>
        <?php endif; ?>
    <?php endif; ?>

    <?php include "menu/footer.php"; ?>
</body>
</html>