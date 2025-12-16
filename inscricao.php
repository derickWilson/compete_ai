<?php
session_start();
if (!isset($_SESSION["logado"]) || !$_SESSION["logado"]) {
    header("Location: index.php");
    exit();
}

try {
    require_once "classes/atletaService.php";
    require_once "classes/eventosServices.php";
    require_once "classes/AssasService.php";
    include "func/clearWord.php";
    require_once __DIR__ . "/config_taxa.php";
    require_once "func/determinar_categoria.php";

} catch (\Throwable $th) {
    print ('[' . $th->getMessage() . ']');
}

$conn = new Conexao();
$at = new Atleta();
$atserv = new atletaService($conn, $at);
$eventoServ = new eventosService($conn, new Evento());
$assasService = new AssasService($conn);

if (isset($_GET["inscricao"])) {
    $eventoId = (int) cleanWords($_GET["inscricao"]);
    $inscricao = $atserv->getInscricao($eventoId, $_SESSION["id"]);
    $dadosEvento = $eventoServ->getById($eventoId);

    if (!$inscricao || !$dadosEvento) {
        $_SESSION['erro'] = "Inscrição não encontrada";
        header("Location: eventos_cadastrados.php");
        exit();
    }

    if(!$inscricao->modalidade){
        $categoriaAuto = determinarCategoriaPeso($_SESSION["peso"], $_SESSION["idade"], $_SESSION["genero"]);
        $categoriaAuto = strtolower(str_replace('_', '-', $categoriaAuto));
    }else{
        $categoriaAuto = $inscricao->modalidade;
    }
    // Verifica status do pagamento
    $statusPagamento = $inscricao->status_pagamento ?? 'PENDING';
    $cobrancaId = $inscricao->id_cobranca_asaas ?? null;
    
    // Se existir cobrança, verifica status real no Asaas
    if ($cobrancaId) {
        try {
            $statusInfo = $assasService->verificarStatusCobranca($cobrancaId);
            $statusPagamento = $statusInfo['status'];
        } catch (Exception $e) {
            // Mantém o status local em caso de erro
            error_log("Erro ao verificar status da cobrança: " . $e->getMessage());
        }
    }

    $pagamentoNaoPendente = (strtoupper($statusPagamento) !== 'PENDING');

    // Verifica se é evento gratuito considerando todos os preços
    $eventoGratuito = ($dadosEvento->preco == 0 && $dadosEvento->preco_menor == 0 &&
        $dadosEvento->preco_abs == 0 && $dadosEvento->preco_sem == 0 &&
        $dadosEvento->preco_sem_menor == 0 && $dadosEvento->preco_sem_abs == 0);

    // Formata os preços para exibição
    $inscricao->preco = number_format($inscricao->preco * TAXA, 2, ',', '.');
    $inscricao->preco_menor = number_format($inscricao->preco_menor * TAXA, 2, ',', '.');
    $inscricao->preco_abs = number_format($inscricao->preco_abs * TAXA, 2, ',', '.');
    $inscricao->preco_sem = number_format($dadosEvento->preco_sem * TAXA, 2, ',', '.');
    $inscricao->preco_sem_menor = number_format($dadosEvento->preco_sem_menor * TAXA, 2, ',', '.');
    $inscricao->preco_sem_abs = number_format($dadosEvento->preco_sem_abs * TAXA, 2, ',', '.');
} else {
    $_SESSION['erro'] = "Selecione um campeonato";
    header("Location: eventos_cadastrados.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Inscrição</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="/estilos/icone.jpeg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        .modalidade-group.disabled {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .status-info {
            background-color: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 5px;
            padding: 10px;
            margin: 10px 0;
            color: #0066cc;
        }
        
        .form-actions {
            margin-top: 20px;
        }
        
        .botao-acao {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .danger {
            background-color: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .link {
            color: #007bff;
            text-decoration: none;
        }
        
        .current-selection {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #007bff;
        }
    </style>
</head>

<body>
    <?php include "menu/add_menu.php"; ?>
    <?php include "include_hamburger.php"; ?>

    <div class='principal'>
        <h3><?php echo htmlspecialchars($inscricao->nome); ?></h3>

        <?php if ($pagamentoNaoPendente): ?>
            <div class="status-info">
                <i class="fas fa-info-circle"></i> 
                <strong>Pagamento Confirmado:</strong> Você pode alterar apenas a categoria de peso. 
                As modalidades não podem ser modificadas após a confirmação do pagamento.
            </div>
            
            <!-- Mostra as modalidades atuais como informação -->
            <div class="current-selection">
                <strong>Modalidades Atuais:</strong><br>
                <?php
                $modalidades = [];
                if ($inscricao->mod_com == 1) $modalidades[] = "Com Kimono";
                if ($inscricao->mod_ab_com == 1) $modalidades[] = "Absoluto Com Kimono";
                if ($inscricao->mod_sem == 1) $modalidades[] = "Sem Kimono";
                if ($inscricao->mod_ab_sem == 1) $modalidades[] = "Absoluto Sem Kimono";
                
                if (empty($modalidades)) {
                    echo "Nenhuma modalidade selecionada";
                } else {
                    echo implode(", ", $modalidades);
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if (!$eventoGratuito): ?>
            <p>Preços:
                <?php
                if ($_SESSION["idade"] > 15) {
                    echo "<br>Com Kimono: " . $inscricao->preco . " R$";
                    echo "<br>Absoluto Com Kimono: " . $inscricao->preco_abs . " R$";
                    echo "<br>Sem Kimono: " . $inscricao->preco_sem . " R$";
                    echo "<br>Absoluto Sem Kimono: " . $inscricao->preco_sem_abs . " R$";
                } else {
                    echo "<br>Com Kimono: " . $inscricao->preco_menor . " R$";
                    echo "<br>Sem Kimono: " . $inscricao->preco_sem_menor . " R$";
                }
                ?>
                <br><small>Desconto de 40% para mais de duas modalidades</small>
            </p>
        <?php else: ?>
            <p>Evento Gratuito</p>
        <?php endif; ?>

        <form action="editar_inscricao.php" method="POST">
            <input type="hidden" name="evento_id" value="<?php echo htmlspecialchars($inscricao->id); ?>">
            
            <?php if (!$pagamentoNaoPendente): ?>
                <!-- MODALIDADES - APENAS SE ESTIVER PENDENTE -->
                <?php if ($inscricao->tipo_com == 1): ?>
                    <div class="modalidade-group">
                        <input type="checkbox" name="com" id="com" <?php echo $inscricao->mod_com == 1 ? 'checked' : ''; ?>>
                        <label for="com"> Categoria Com Kimono</label>

                        <?php if ($_SESSION["idade"] > 15): ?>
                            <br><input type="checkbox" name="abs_com" id="abs_com" <?php echo $inscricao->mod_ab_com == 1 ? 'checked' : ''; ?>>
                            <label for="abs_com"> Absoluto Com Kimono</label>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($inscricao->tipo_sem == 1): ?>
                    <div class="modalidade-group">
                        <input type="checkbox" name="sem" id="sem" <?php echo $inscricao->mod_sem == 1 ? 'checked' : ''; ?>>
                        <label for="sem"> Categoria Sem Kimono</label>

                        <?php if ($_SESSION["idade"] > 15): ?>
                            <br><input type="checkbox" name="abs_sem" id="abs_sem" <?php echo $inscricao->mod_ab_sem == 1 ? 'checked' : ''; ?>>
                            <label for="abs_sem"> Absoluto Sem Kimono</label>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- SE NÃO ESTIVER PENDENTE, MOSTRA APENAS AS MODALIDADES COMO INFORMAÇÃO -->
                <input type="hidden" name="com" value="<?php echo $inscricao->mod_com; ?>">
                <input type="hidden" name="abs_com" value="<?php echo $inscricao->mod_ab_com; ?>">
                <input type="hidden" name="sem" value="<?php echo $inscricao->mod_sem; ?>">
                <input type="hidden" name="abs_sem" value="<?php echo $inscricao->mod_ab_sem; ?>">
            <?php endif; ?>

            <br>
            <label for="modalidade">Categoria de Peso:</label>
            <select name="modalidade" required readonly>
            <option value="galo" <?= $categoriaAuto == 'galo' ? 'selected' : '' ?>>Galo</option>
            <option value="pluma" <?= $categoriaAuto == 'pluma' ? 'selected' : '' ?>>Pluma</option>
            <option value="pena" <?= $categoriaAuto == 'pena' ? 'selected' : '' ?>>Pena</option>
            <option value="leve" <?= $categoriaAuto == 'leve' ? 'selected' : '' ?>>Leve</option>
            <option value="medio" <?= $categoriaAuto == 'medio' ? 'selected' : '' ?>>Médio</option>
            <option value="meio-pesado" <?= $categoriaAuto == 'meio-pesado' ? 'selected' : '' ?>>Meio-Pesado</option>
            <option value="pesado" <?= $categoriaAuto == 'pesado' ? 'selected' : '' ?>>Pesado</option>
            <option value="super-pesado" <?= $categoriaAuto == 'super-pesado' ? 'selected' : '' ?>>Super-Pesado</option>
            <option value="pesadissimo" <?= $categoriaAuto == 'pesadissimo' ? 'selected' : '' ?>>Pesadíssimo</option>
            <?php if ($_SESSION["idade"] > 15) { ?>
            <option value="super-pesadissimo" <?= $categoriaAuto == 'super-pesadissimo' ? 'selected' : '' ?>>
            Super-Pesadíssimo</option>
            <?php } ?>
            </select>

            <div class="form-actions">
                <input type="submit" name="action" value="Salvar Alterações" class="botao-acao">
                
                <?php if (!$pagamentoNaoPendente): ?>
                    <!-- EXCLUSÃO APENAS SE ESTIVER PENDENTE -->
                    <input type="submit" name="action" value="Excluir Inscrição" class="danger"
                        onclick="return confirm('Tem certeza que deseja excluir esta inscrição?')">
                <?php endif; ?>
            </div>
        </form>

        <br>
        <center>Tabela de Pesos</center>
        <center>
            <object data="tabela_de_pesos.pdf" type="application/pdf" width="50%"></object>
        </center>
        <br><a class="link" href="eventos_cadastrados.php">voltar</a>
    </div>
    <?php include "menu/footer.php"; ?>
    
    <?php if (!$pagamentoNaoPendente): ?>
        <!-- SCRIPT APENAS PARA PENDENTES -->
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Grupos de checkboxes que são mutuamente exclusivos
                const gruposExclusivos = [
                    ['com', 'abs_com'],     // Categoria Com Kimono vs Absoluto Com Kimono
                    ['sem', 'abs_sem']      // Categoria Sem Kimono vs Absoluto Sem Kimono
                ];

                // Para cada grupo de exclusividade
                gruposExclusivos.forEach(grupo => {
                    const checkboxes = grupo.map(name => {
                        const checkbox = document.querySelector(`input[name="${name}"]`);
                        return checkbox;
                    }).filter(checkbox => checkbox !== null);

                    // Adiciona evento a cada checkbox do grupo
                    checkboxes.forEach(checkbox => {
                        checkbox.addEventListener('change', function () {
                            if (this.checked) {
                                // Se este foi marcado, desmarca os outros do mesmo grupo
                                checkboxes.forEach(otherCheckbox => {
                                    if (otherCheckbox !== this) {
                                        otherCheckbox.checked = false;
                                    }
                                });
                            }
                        });
                    });
                });

                // Validação no envio do formulário
                const form = document.querySelector('form');
                if (form) {
                    form.addEventListener('submit', function (e) {
                        // Verifica se pelo menos uma modalidade foi selecionada
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
            });
        </script>
    <?php endif; ?>
</body>

</html>