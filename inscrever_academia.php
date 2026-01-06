<?php
session_start();

// Verificar se o usuário está logado e é responsável
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || $_SESSION['responsavel'] != 1) {
    header('Location: /login.php');
    exit();
}

require_once __DIR__ . "/func/database.php";
require_once __DIR__ . "/classes/atletaClass.php";
require_once __DIR__ . "/classes/atletaService.php";
require_once __DIR__ . "/classes/eventosServices.php";
require_once __DIR__ . "/func/calcularIdade.php";
require_once __DIR__ . "/func/determinar_categoria.php";

// Instanciar serviços
try {
    $conn = new Conexao();
    $atletaService = new atletaService($conn, new Atleta());
    $eventoServ = new eventosService($conn, new Evento());
} catch (Exception $e) {
    die("<div class='erro'>Erro ao conectar: " . $e->getMessage() . "</div>");
}

// Obter ID da academia do responsável
$academiaId = $_SESSION["academia_id"];

// Obter alunos da academia
$alunos = $atletaService->listarAlunosAcademia($academiaId);

// Obter eventos disponíveis
$eventos = $eventoServ->listAll();

// Processar inscrição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inscrever'])) {
    if (empty($_POST['alunos'])) {
        $_SESSION['erro'] = "Selecione pelo menos um aluno";
        header("Location: inscrever_academia.php");
        exit();
    }
    
    if (empty($_POST['evento_id'])) {
        $_SESSION['erro'] = "Selecione um campeonato";
        header("Location: inscrever_academia.php");
        exit();
    }
    
    $eventoId = (int)$_POST['evento_id'];
    $alunosSelecionados = $_POST['alunos'];
    
    // Obter detalhes do evento
    $eventoDetails = $eventoServ->getById($eventoId);
    
    // Verificar se o evento está disponível
    $disponibilidade = $eventoServ->verificarDisponibilidadeEvento($eventoId);
    if (!$disponibilidade['disponivel']) {
        $_SESSION['erro'] = $disponibilidade['mensagem'];
        header("Location: inscrever_academia.php");
        exit();
    }
    
    // Verificar se há taxa definida
    if (file_exists(__DIR__ . "/config_taxa.php")) {
        require_once __DIR__ . "/config_taxa.php";
    } else {
        define('TAXA', 1.0); // Taxa padrão 1.0 (sem taxa)
    }
    
    // Arrays para resultados
    $sucessos = [];
    $falhas = [];
    
    // Iniciar transação para consistência
    try {
        $connObj = new Conexao();
        $pdo = $connObj->conectar();
        $pdo->beginTransaction();
        
        foreach ($alunosSelecionados as $alunoId) {
            // Verificar se o aluno já está inscrito
            if ($eventoServ->isInscrito($alunoId, $eventoId)) {
                $falhas[] = "Aluno ID $alunoId já está inscrito neste campeonato";
                continue;
            }
            
            // Obter dados do aluno
            $aluno = $atletaService->getById($alunoId);
            if (!$aluno) {
                $falhas[] = "Aluno ID $alunoId não encontrado";
                continue;
            }
            
            // Calcular idade do aluno
            $idadeAluno = calcularIdade($aluno->data_nascimento);
            
            // Determinar categoria automaticamente
            $categoriaAuto = determinarCategoriaPeso($aluno->peso, $idadeAluno, $aluno->genero);
            
            // Determinar faixa etária
            $categoria_idade = determinarFaixaEtaria($idadeAluno);
            
            // Determinar modalidades (baseado no tipo de evento)
            if ($eventoDetails->normal) {
                // Evento normal - sem modalidades específicas
                $mod_com = 0;
                $mod_sem = 0;
                $mod_ab_com = 0;
                $mod_ab_sem = 0;
                $modalidade = "normal";
            } else {
                // Responsável só inscreve na CATEGORIA, NÃO no ABSOLUTO
                $mod_com = ($eventoDetails->tipo_com == 1 && !empty($aluno->faixa)) ? 1 : 0;
                $mod_sem = ($eventoDetails->tipo_sem == 1 && !empty($aluno->faixa)) ? 1 : 0;
                $mod_ab_com = 0; // Responsável NÃO inscreve no absoluto
                $mod_ab_sem = 0; // Responsável NÃO inscreve no absoluto
                $modalidade = $categoriaAuto;
            }
            
            // Verificar se pelo menos uma modalidade foi selecionada
            if (!$eventoDetails->normal && !($mod_com || $mod_sem)) {
                $falhas[] = "Aluno {$aluno->nome} não tem modalidades habilitadas (verifique faixa e tipo de evento)";
                continue;
            }
            
            try {
                // Inscrever o aluno
                $inscricaoSucesso = $eventoServ->inscrever(
                    $alunoId,
                    $eventoId,
                    $mod_com,
                    $mod_ab_com,
                    $mod_sem,
                    $mod_ab_sem,
                    $modalidade,
                    $categoria_idade,
                    1, // Aceite regulamento
                    1  // Aceite responsabilidade
                );
                
                if ($inscricaoSucesso) {
                    // Calcular valor da inscrição
                    $valor = 0;
                    
                    if ($eventoDetails->normal) {
                        // Evento normal
                        $valor = $eventoDetails->normal_preco;
                    } else {
                        // Evento tradicional - cálculo correto
                        if ($idadeAluno <= 15) {
                            if ($mod_com) $valor += $eventoDetails->preco_menor;
                            if ($mod_sem) $valor += $eventoDetails->preco_sem_menor;
                        } else {
                            // Responsável só inscreve na categoria, então usa preço normal (não absoluto)
                            if ($mod_com) $valor += $eventoDetails->preco;
                            if ($mod_sem) $valor += $eventoDetails->preco_sem;
                            // NOTA: Absoluto (preco_abs e preco_sem_abs) não se aplica para responsável
                        }
                        
                        // Desconto de 40% se fizer COM e SEM kimono (CATEGORIAS)
                        if ($mod_com && $mod_sem) {
                            $valor *= 0.6;
                        }
                        
                        // Aplicar taxa
                        $valor *= TAXA;
                    }
                    
                    // Verificar se é evento gratuito
                    $eventoGratuito = false;
                    if ($eventoDetails->normal) {
                        $eventoGratuito = ($eventoDetails->normal_preco == 0);
                    } else {
                        $eventoGratuito = ($eventoDetails->preco == 0 && $eventoDetails->preco_menor == 0 &&
                                          $eventoDetails->preco_sem == 0 && $eventoDetails->preco_sem_menor == 0);
                    }
                    
                    // Se for evento pago, criar cobrança no Asaas
                    if (!$eventoGratuito && $valor > 0) {
                        try {
                            require_once __DIR__ . "/classes/AssasService.php";
                            $asaasService = new AssasService($conn);
                            
                            // Buscar ou criar cliente no Asaas
                            $dadosAtleta = [
                                'id' => $alunoId,
                                'nome' => $aluno->nome,
                                'cpf' => $aluno->cpf,
                                'email' => $aluno->email,
                                'fone' => $aluno->fone,
                                'academia' => $aluno->academia
                            ];
                            
                            $customerId = $asaasService->buscarOuCriarCliente($dadosAtleta);
                            
                            $descricao = "Inscrição: " . $eventoDetails->nome . " (" . $modalidade . ") - " . $aluno->nome;
                            
                            $dadosCobranca = [
                                'customer' => $customerId,
                                'value' => $valor,
                                'dueDate' => $eventoDetails->data_limite,
                                'description' => $descricao,
                                'externalReference' => 'EV_' . $eventoId . '_AT_' . $alunoId,
                                'billingType' => 'PIX'
                            ];
                            
                            $cobranca = $asaasService->criarCobranca($dadosCobranca);
                            
                            // Atualizar inscrição com dados do pagamento
                            $asaasService->atualizarInscricaoComPagamento(
                                $alunoId,
                                $eventoId,
                                $cobranca['payment']['id'],
                                'PENDING',
                                $valor
                            );
                            
                        } catch (Exception $asaasError) {
                            // Log do erro, mas continua com a inscrição
                            error_log("Erro ao criar cobrança Asaas para aluno $alunoId: " . $asaasError->getMessage());
                        }
                    } else {
                        // Para eventos gratuitos, marca como GRATUITO
                        require_once __DIR__ . "/classes/AssasService.php";
                        $asaasService = new AssasService($conn);
                        $asaasService->atualizarInscricaoComPagamento(
                            $alunoId,
                            $eventoId,
                            null,
                            'GRATUITO',
                            0
                        );
                    }
                    
                    $sucessos[] = "Aluno {$aluno->nome} inscrito com sucesso";
                    
                } else {
                    $falhas[] = "Falha ao inscrever aluno {$aluno->nome}";
                }
                
            } catch (Exception $e) {
                $falhas[] = "Erro ao inscrever aluno {$aluno->nome}: " . $e->getMessage();
            }
        }
        
        // Confirmar transação
        $pdo->commit();
        
        // Armazenar resultados na sessão
        $_SESSION['resultado_inscricao'] = [
            'sucessos' => $sucessos,
            'falhas' => $falhas,
            'evento' => $eventoDetails->nome
        ];
        
        header("Location: inscrever_academia.php?resultado=1");
        exit();
        
    } catch (Exception $e) {
        // Reverter transação em caso de erro
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        $_SESSION['erro'] = "Erro no processo de inscrição: " . $e->getMessage();
        header("Location: inscrever_academia.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscrever Academia em Campeonato</title>
    <link rel="stylesheet" href="/style.css">
    <link rel="icon" href="/estilos/icone.jpeg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container-duplo {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }
        
        .coluna {
            flex: 1;
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--box-shadow);
        }
        
        .coluna h3 {
            margin-top: 0;
            color: var(--primary-dark);
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .lista-alunos {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .item-aluno {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .item-aluno:hover {
            background: #f5f5f5;
        }
        
        .item-aluno input[type="checkbox"] {
            margin-right: 10px;
        }
        
        .info-aluno {
            flex: 1;
        }
        
        .nome-aluno {
            font-weight: 600;
            color: var(--dark);
        }
        
        .detalhes-aluno {
            font-size: 12px;
            color: var(--gray);
            margin-top: 2px;
        }
        
        .eventos-container {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .evento-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }
        
        .evento-item:hover {
            background: #f5f5f5;
        }
        
        .evento-item.selecionado {
            background: var(--primary-light);
            color: var(--white);
            border-radius: 5px;
        }
        
        .evento-item.disabled {
            opacity: 0.6;
            cursor: not-allowed;
            background: #f5f5f5;
        }
        
        .nome-evento {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .detalhes-evento {
            font-size: 12px;
            color: var(--gray);
        }
        
        .acoes-container {
            margin-top: 30px;
            text-align: center;
        }
        
        .btn-inscrever {
            background: var(--success);
            color: var(--white);
            border: none;
            padding: 15px 30px;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
        }
        
        .btn-inscrever:hover {
            background: #2f855a;
            transform: translateY(-2px);
        }
        
        .btn-voltar {
            background: var(--primary);
            color: var(--white);
            padding: 10px 20px;
            border-radius: var(--border-radius);
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
        }
        
        .contador-selecionados {
            background: var(--accent);
            color: var(--dark);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .resultados {
            margin: 20px 0;
            padding: 15px;
            border-radius: var(--border-radius);
            background: var(--white);
            box-shadow: var(--box-shadow);
        }
        
        .resultados.sucesso {
            border-left: 5px solid var(--success);
        }
        
        .resultados.erro {
            border-left: 5px solid var(--danger);
        }
        
        .lista-resultados {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .item-resultado {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        
        .item-resultado.sucesso {
            color: var(--success);
        }
        
        .item-resultado.erro {
            color: var(--danger);
        }
        
        .info-responsavel {
            background: #f0f9ff;
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            border-left: 4px solid var(--primary);
        }
        
        @media (max-width: 768px) {
            .container-duplo {
                flex-direction: column;
            }
            
            .coluna {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include "menu/add_menu.php"; ?>
    <?php include "include_hamburger.php"; ?>
    
    <div class="container">
        <div class="principal">
            <div class="info-responsavel">
                <h3><i class="fas fa-info-circle"></i> Informações Importantes</h3>
                <p><strong>Atenção Responsável:</strong> Você está inscrevendo alunos apenas na <strong>CATEGORIA</strong> (não no absoluto).</p>
                <p>A categoria de peso é determinada automaticamente com base no peso, idade e gênero do aluno.</p>
            </div>
            
            <h1><i class="fas fa-user-plus"></i> Inscrever Academia em Campeonato</h1>
            
            <?php if (isset($_SESSION['erro'])): ?>
                <div class="alert-message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <h3>Erro!</h3>
                    <p><?php echo htmlspecialchars($_SESSION['erro']); unset($_SESSION['erro']); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['resultado']) && isset($_SESSION['resultado_inscricao'])): 
                $resultado = $_SESSION['resultado_inscricao'];
                unset($_SESSION['resultado_inscricao']);
            ?>
                <div class="resultados <?php echo empty($resultado['falhas']) ? 'sucesso' : 'erro'; ?>">
                    <h3>
                        <?php if (empty($resultado['falhas'])): ?>
                            <i class="fas fa-check-circle"></i> Inscrições Realizadas com Sucesso!
                        <?php else: ?>
                            <i class="fas fa-exclamation-triangle"></i> Resultado das Inscrições
                        <?php endif; ?>
                    </h3>
                    <p><strong>Campeonato:</strong> <?php echo htmlspecialchars($resultado['evento']); ?></p>
                    
                    <?php if (!empty($resultado['sucessos'])): ?>
                        <h4>Inscrições bem-sucedidas: <?php echo count($resultado['sucessos']); ?></h4>
                        <div class="lista-resultados">
                            <?php foreach ($resultado['sucessos'] as $sucesso): ?>
                                <div class="item-resultado sucesso">
                                    <i class="fas fa-check"></i> <?php echo htmlspecialchars($sucesso); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($resultado['falhas'])): ?>
                        <h4>Falhas: <?php echo count($resultado['falhas']); ?></h4>
                        <div class="lista-resultados">
                            <?php foreach ($resultado['falhas'] as $falha): ?>
                                <div class="item-resultado erro">
                                    <i class="fas fa-times"></i> <?php echo htmlspecialchars($falha); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 15px;">
                        <a href="inscrever_academia.php" class="btn-voltar" style="margin-right: 10px;">
                            <i class="fas fa-plus"></i> Nova Inscrição
                        </a>
                        <a href="lista_alunos.php" class="btn-voltar">
                            <i class="fas fa-users"></i> Ver Alunos
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="inscrever_academia.php" id="formInscricao">
                <div class="container-duplo">
                    <!-- Coluna dos Alunos -->
                    <div class="coluna">
                        <h3>
                            <i class="fas fa-users"></i> Meus Alunos 
                            <span id="contadorAlunos" class="contador-selecionados">0 selecionados</span>
                        </h3>
                        
                        <?php if (empty($alunos)): ?>
                            <div class="estado-vazio">
                                <i class="fas fa-user-graduate"></i>
                                <p>Nenhum aluno cadastrado na sua academia.</p>
                                <a href="/cadastro_atleta.php" class="btn-voltar" style="margin-top: 10px;">
                                    <i class="fas fa-user-plus"></i> Cadastrar Aluno
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="lista-alunos">
                                <div class="item-aluno">
                                    <input type="checkbox" id="selecionar-todos">
                                    <label for="selecionar-todos" style="cursor: pointer;">
                                        <strong>Selecionar todos</strong>
                                    </label>
                                </div>
                                
                                <?php foreach ($alunos as $aluno): 
                                    $idade = calcularIdade($aluno->data_nascimento);
                                ?>
                                    <div class="item-aluno">
                                        <input type="checkbox" name="alunos[]" value="<?php echo $aluno->id; ?>" 
                                               class="checkbox-aluno" id="aluno-<?php echo $aluno->id; ?>">
                                        <div class="info-aluno">
                                            <label for="aluno-<?php echo $aluno->id; ?>" style="cursor: pointer;">
                                                <div class="nome-aluno"><?php echo htmlspecialchars($aluno->nome); ?></div>
                                                <div class="detalhes-aluno">
                                                    Idade: <?php echo $idade; ?> anos | 
                                                    Faixa: <?php echo htmlspecialchars($aluno->faixa); ?> | 
                                                    Peso: <?php echo htmlspecialchars($aluno->peso); ?> kg
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Coluna dos Campeonatos -->
                    <div class="coluna">
                        <h3><i class="fas fa-trophy"></i> Campeonatos Disponíveis</h3>
                        
                        <?php if (empty($eventos)): ?>
                            <div class="estado-vazio">
                                <i class="fas fa-calendar-times"></i>
                                <p>Nenhum campeonato disponível no momento.</p>
                            </div>
                        <?php else: ?>
                            <div class="eventos-container">
                                <?php foreach ($eventos as $evento): 
                                    $disponibilidade = $eventoServ->verificarDisponibilidadeEvento($evento->id);
                                    $disponivel = $disponibilidade['disponivel'];
                                ?>
                                    <div class="evento-item <?php echo !$disponivel ? 'disabled' : ''; ?>" 
                                         data-evento-id="<?php echo $evento->id; ?>"
                                         onclick="<?php echo $disponivel ? "selecionarEvento(this)" : ""; ?>">
                                        <div class="nome-evento"><?php echo htmlspecialchars($evento->nome); ?></div>
                                        <div class="detalhes-evento">
                                            <?php if (!$disponivel): ?>
                                                <span style="color: var(--danger);">
                                                    <i class="fas fa-ban"></i> <?php echo htmlspecialchars($disponibilidade['mensagem']); ?>
                                                </span>
                                            <?php else: ?>
                                                <i class="fas fa-check-circle" style="color: var(--success);"></i> Inscrições abertas
                                            <?php endif; ?>
                                        </div>
                                        <input type="radio" name="evento_id" value="<?php echo $evento->id; ?>" 
                                               style="display: none;" <?php echo !$disponivel ? 'disabled' : ''; ?>>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div id="eventoSelecionadoInfo" style="display: none; margin-top: 15px; padding: 10px; background: #f0f9ff; border-radius: 5px;">
                            <strong>Campeonato selecionado:</strong>
                            <span id="nomeEventoSelecionado"></span>
                        </div>
                    </div>
                </div>
                
                <div class="acoes-container">
                    <button type="submit" name="inscrever" class="btn-inscrever" id="btnInscrever" disabled>
                        <i class="fas fa-paper-plane"></i> Inscrever Alunos Selecionados
                    </button>
                    <a href="lista_alunos.php" class="btn-voltar">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <?php include "menu/footer.php"; ?>
    
    <script>
        // Contador de alunos selecionados
        function atualizarContador() {
            const checkboxes = document.querySelectorAll('.checkbox-aluno:checked');
            const contador = document.getElementById('contadorAlunos');
            const btnInscrever = document.getElementById('btnInscrever');
            
            contador.textContent = checkboxes.length + ' selecionados';
            
            // Habilitar botão se houver alunos e evento selecionados
            const eventoSelecionado = document.querySelector('input[name="evento_id"]:checked');
            btnInscrever.disabled = !(checkboxes.length > 0 && eventoSelecionado);
        }
        
        // Selecionar todos os alunos
        document.getElementById('selecionar-todos').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.checkbox-aluno');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            atualizarContador();
        });
        
        // Atualizar contador quando qualquer checkbox for alterado
        document.querySelectorAll('.checkbox-aluno').forEach(checkbox => {
            checkbox.addEventListener('change', atualizarContador);
        });
        
        // Selecionar evento
        function selecionarEvento(elemento) {
            // Remover seleção anterior
            document.querySelectorAll('.evento-item').forEach(item => {
                item.classList.remove('selecionado');
            });
            
            // Adicionar seleção atual
            elemento.classList.add('selecionado');
            
            // Atualizar radio button
            const eventoId = elemento.getAttribute('data-evento-id');
            const radioButton = document.querySelector(`input[name="evento_id"][value="${eventoId}"]`);
            radioButton.checked = true;
            
            // Mostrar informações do evento selecionado
            const nomeEvento = elemento.querySelector('.nome-evento').textContent;
            document.getElementById('nomeEventoSelecionado').textContent = nomeEvento;
            document.getElementById('eventoSelecionadoInfo').style.display = 'block';
            
            // Atualizar botão
            atualizarContador();
        }
        
        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            // Remover mensagens de alerta após 5 segundos
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert-message');
                alerts.forEach(alert => {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                });
            }, 5000);
        });
        
        // Confirmar envio do formulário
        document.getElementById('formInscricao').addEventListener('submit', function(e) {
            const checkboxes = document.querySelectorAll('.checkbox-aluno:checked');
            const eventoSelecionado = document.querySelector('input[name="evento_id"]:checked');
            
            if (checkboxes.length === 0) {
                e.preventDefault();
                alert('Selecione pelo menos um aluno para inscrever.');
                return false;
            }
            
            if (!eventoSelecionado) {
                e.preventDefault();
                alert('Selecione um campeonato para inscrever os alunos.');
                return false;
            }
            
            return confirm(`Deseja inscrever ${checkboxes.length} aluno(s) no campeonato selecionado?\n\nIMPORTANTE: Responsável só inscreve na CATEGORIA, NÃO no ABSOLUTO.\n\nSerão criadas cobranças no sistema Asaas para os alunos selecionados.`);
        });
    </script>
</body>
</html>