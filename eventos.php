<?php
    // Incluindo arquivos necessários
    ob_start(); // INICIA O BUFFER DE SAÍDA
    session_start();/*
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);*/

    try {
        require_once "classes/eventosServices.php";
        include "func/clearWord.php";
        include "config_taxa.php";
    } catch (\Throwable $th) {
        print('['. $th->getMessage() .']');
    }
        $conn = new Conexao();
        $ev = new Evento();
        $evserv = new eventosService($conn, $ev);
        $tudo = true;    
    if (isset($_GET['id'])) {
        // Usado para listar os detalhes de um único eventom
        $eventoId = (int) cleanWords($_GET['id']);
        $eventoDetails = $evserv->getById($eventoId);
        $tudo = false;
    } else {//se não esta em um evento especifico,  lista todos
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
    </head>
    <body>
        <?php
        include_once "menu/add_menu.php";
        ?>
        <?php
            // Listar todos os eventos
            if ($tudo) {
                foreach ($list as $valor) { ?>
                <div class="campeonato">
                <img src="uploads/<?php echo $valor->imagen; ?>" alt="Imagem" class='mini-banner'>
                <a href='eventos.php?id=<?php echo $valor->id ?>' class='clear'><h2>
                    <?php echo htmlspecialchars($valor->nome); ?></h2></a>
                    <?php if (isset($_SESSION['admin']) && $_SESSION['admin']) { ?>
                        | <a href='admin/lista_inscritos.php?id=<?php echo $valor->id ?>'>Ver Inscritos</a>
                        | <a href='admin/editar_evento.php?id=<?php echo $valor->id ?>'>Editar Evento</a>
                        | <a href='admin/baixar_chapa.php?id=<?php echo $valor->id ?>'>Montar chapa</a>
                    <?php } ?>
                    <br class='clear'>
                </div>
        <?php } ?>
                <br><a href="index.php">Voltar</a>
            <?php
            } else {// detalhes de apenas um campeonato
                if (isset($eventoDetails)) {
            ?>
            <div class='principal'>
                <h1><?php echo htmlspecialchars($eventoDetails->nome); ?></h1>
                    <div class="imagem-container">
                        <img src="uploads/<?php echo $eventoDetails->imagen; ?>" alt="Imagem do Evento">
                    </div>
                    
                    <p>Descrição: <?php echo htmlspecialchars($eventoDetails->descricao); ?></p>
                    <p>Data do Campeonato: <?php echo htmlspecialchars($eventoDetails->data_evento); ?></p>
                    <p>Local do Campeonato: <?php echo htmlspecialchars($eventoDetails->local_evento); ?></p>
                    <p>Preço
                    <?php
                    $taxa = 1;
                    if(!isset($_SESSION["idade"])){
                        $precoComTaxa = $eventoDetails->preco * $taxa;
                        $precoMenorComTaxa = $eventoDetails->preco_menor * $taxa;
                        $precoAbsComTaxa = $eventoDetails->preco_abs * $taxa;
                    
                        echo number_format($precoComTaxa, 2, ',', '.') . " R$ para maiores de 15 anos<br>";
                        echo number_format($precoMenorComTaxa, 2, ',', '.') . " R$ para menores de 15 anos<br>";
                        echo number_format($precoAbsComTaxa, 2, ',', '.') . " R$ para Absoluto";
                    } else {
                        if($_SESSION["idade"] > 15){
                            $precoComTaxa = $eventoDetails->preco * $taxa;
                            $precoAbsComTaxa = $eventoDetails->preco_abs * $taxa;
                        
                            echo number_format($precoComTaxa, 2, ',', '.') . " R$";
                            echo "<br>Preço Absoluto " . number_format($precoAbsComTaxa, 2, ',', '.') . " R$";
                        } else {
                            $precoMenorComTaxa = $eventoDetails->preco_menor * $taxa;
                            echo number_format($precoMenorComTaxa, 2, ',', '.') . " R$";
                        }
                    }
                    ?></p>

                        <?php if (!empty($eventoDetails->doc)) { ?>
                            <p><a href="<?php echo '/doc/' . htmlspecialchars($eventoDetails->doc); ?>" download>Baixe o Edital do Evento</a></p>
                        <?php } else { ?>
                            <p><em>Edital não disponível para este evento.</em></p>
                        <?php } ?>
                    <?php
                    if (isset($_SESSION['logado']) && $_SESSION['logado']) {
                        if($evserv->isInscrito($_SESSION["id"], $eventoId)){
                            ?>
                            <form action="inscreverAtleta.php" method="POST">
                                <input type="hidden" name="evento_id" value="<?php echo htmlspecialchars($eventoDetails->id); ?>">
                                <input type="hidden" name="valor" value="<?php echo htmlspecialchars($eventoDetails->preco); ?>">
                                <?php
                            // Caso o tipo de campeonato seja com quimono
                            if ($eventoDetails->tipo_com == 1) {
                                echo '<input type="checkbox" name="com" checked onclick="return false;" style="pointer-events: none;"> Categoria ';

                                if($_SESSION["idade"] > 15){
                                    echo '<input type="checkbox" name="abs_com"> Categoria + Absoluto';
                                }
                            }

                            // Caso o tipo de campeonato seja sem quimono
                            if ($eventoDetails->tipo_sem == 1) {
                                echo '<input type="checkbox" name="sem"> Categoria Sem Quimono ';
                                if($_SESSION["idade"] > 15){
                                    echo '<input type="checkbox" name="abs_sem"> Categoria Sem Quimono + Absoluto';
                                }
                            }?>
                                <br>modalidade<select name="modalidade">
                                <option value="galo">Galo</option>
                                <option value="pluma">Pluma</option>
                                <option value="pena">Pena</option>
                                <option value="leve">Leve</option>
                                <option value="medio">Médio</option>
                                <option value="meio-pesado">Meio-Pesado</option>
                                <option value="pesado">Pesado</option>
                                <option value="super-pesado">Super-Pesado</option>
                                <option value="pesadissimo">Pesadíssimo</option>
                                <?php if($_SESSION["idade"] > 15) echo "<option value='super-pesadissimo'>Super-Pesadíssimo</option>";?>
                                </select>

                    <!-- Dentro do formulário de inscrição, antes do botão submit -->
                    <div class="termos">
                        <input type="checkbox" name="aceite_regulamento" id="aceite_regulamento" required>
                        <label for="aceite_regulamento">Afirmo que li e aceito o regulamento do campeonato conforme exibido na página do campeonato</label>
                                            
                        <br>
                                            
                        <input type="checkbox" name="aceite_responsabilidade" id="aceite_responsabilidade" required>
                        <label for="aceite_responsabilidade">Declaro que estou de acordo com os termos de responsabilidade da competição</label>
                    </div>
                                            
                    <input type="submit" value="Inscrever-se">
                            </form>
                    <?php
                    }else{
                        echo "ja está inscrito";
                    }
                    }else{
                    ?>
                        <p>Você deve estar logado para se inscrever.</p>
                    <?php
                    }
                } else {
                    ?>
                    <p>Evento não encontrado.</p>
                    <a href="eventos.php">Voltar</a>
                <?php
                }
                ?>
        <br><center>Tabela de Pesos</center>
        <div class="pdf-container">
            <object data="tabela_de_pesos.pdf" type="application/pdf" width="100%" height="100%">
                <p>Seu navegador não suporta PDFs. <a href="tabela_de_pesos.pdf">Baixe o arquivo</a>.</p>
            </object>
        </div>
        <br><a href="eventos.php" class="link">Voltar</a>||
        <?php
        if(isset($_SESSION['admin']) && $_SESSION["admin"] == 1) {
            echo "<a href='/admin/editar_evento.php?id=" . $eventoId . "'>Editar</a>";
        }
            } // Fim da condição de um único evento
        ?>
    </div>
<?php
include "menu/footer.php";
?>
</body>
</html>