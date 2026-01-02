<!DOCTYPE html>
<html>

<head>
    <style>
        /** {
            border: solid red 1px;
            box-sizing: border-box;
        }*/

        .cabecalho {
            margin-bottom: 20px;
        }

        .bloc {
            float: left;
            width: 48%;
            margin-right: 2%;
            height: 70%;
        }

        .bloc2 {
            float: left;
            width: 48%;
            height: 70%;
        }

        .clear {
            clear: both;
        }
        
        .timer-container {
            text-align: center;
            margin: 20px 0;
        }
        
        .timer-display {
            font-size: 48px;
            font-family: monospace;
            font-weight: bold;
            padding: 20px;
            border: 3px solid #333;
            display: inline-block;
            min-width: 200px;
            background-color: #f0f0f0;
            border-radius: 10px;
        }
        
        .timer-running {
            background-color: #ffcccc !important;
            color: #cc0000;
        }
        
        .timer-paused {
            background-color: #ffffcc !important;
            color: #666600;
        }
        
        .timer-stopped {
            background-color: #ccffcc !important;
            color: #006600;
        }
        
        .timer-instructions {
            margin-top: 10px;
            color: #666;
            font-size: 14px;
        }
        
        .config-info {
            margin-top: 5px;
            font-size: 12px;
            color: #333;
        }
        
        footer {
            margin-top: 30px;
            padding: 15px;
            border-top: 1px solid #ccc;
            font-family: Arial, sans-serif;
        }
    </style>
</head>

<body>
    <div class="cabecalho">
        Categoria<select>
            <option value="galo">Galo</option>
            <option value="pluma">Pluma</option>
            <option value="pena">Pena</option>
            <option value="leve">Leve</option>
            <option value="medio">Médio</option>
            <option value="meio-pesado">Meio-Pesado</option>
            <option value="pesado">Pesado</option>
            <option value="super-pesado">Super-Pesado</option>
            <option value="pesadissimo">Pesadíssimo</option>
            <option value="super-pesadissimo">Super-Pesadíssimo</option>
        </select>
        
        Idade<select id="idade-select">
            <option value="pre-mirim">pre-mirim</option>
            <option value="mirim-1">mirim 1</option>
            <option value="mirim-2">mirim 2</option>
            <option value="infantil-1">infantil 1</option>
            <option value="infantil-2">infantil 2</option>
            <option value="infanto-juvenil">infanto-juvenil</option>
            <option value="juvenil">juvenil</option>
            <option value="adulto">adulto</option>
            <option value="master">master</option>
        </select>
        
        Faixa<select id="faixa-select">
            <option value="">Selecione a graduação</option>
            <option value="branca">Branca</option>
            <option value="cinza">Cinza</option>
            <option value="amarela">Amarela</option>
            <option value="laranja">Laranja</option>
            <option value="verde">Verde</option>
            <option value="azul">Azul</option>
            <option value="roxa">Roxa</option>
            <option value="marrom">Marrom</option>
            <option value="preta">Preta</option>
        </select>
    </div>

    <div class="timer-container">
        <center>
            <h1>TIMER</h1>
            <div id="timerDisplay" class="timer-display timer-stopped">--:--</div>
            <div id="configInfo" class="config-info"></div>
            <div class="timer-instructions">
                Pressione <strong>ESPAÇO</strong> para iniciar/pausar o cronômetro<br>
                Pressione <strong>R</strong> para resetar (quando parado)
            </div>
        </center>
    </div>

    <div class="bloc" id="esqr">
        Pontos <input type="number" name="ponto-left" id="ponto-left" value="0"><br>
        Vantagens <input type="number" name="vantagem-left" id="vantagem-left" value="0">
        Falta <input type="number" name="falta-left" id="falta-left" value="0"><br>
        vencedor <input type="checkbox" name="vencedor-left" id="vencedor-left">
    </div>

    <div class="bloc2" id="dir">
        Pontos <input type="number" name="ponto-right" id="ponto-right" value="0"><br>
        Vantagens <input type="number" name="vantagem-right" id="vantagem-right" value="0">
        Falta <input type="number" name="falta-right" id="falta-right" value="0"><br>
        vencedor <input type="checkbox" name="vencedor-right" id="vencedor-right">
    </div>

    <div class="clear"></div>
    
    <footer>
        <h3>Instruções do Marcador de Pontos:</h3>
        <p><strong>Seleção de Atleta:</strong></p>
        <ul>
            <li>Seta para ESQUERDA ← : Seleciona o atleta da esquerda (border preto aparecerá)</li>
            <li>Seta para DIREITA → : Seleciona o atleta da direita (border preto aparecerá)</li>
        </ul>
        
        <p><strong>Marcar Pontos:</strong></p>
        <ul>
            <li>Tecla 2 : Adiciona 2 pontos ao atleta selecionado</li>
            <li>Tecla 3 : Adiciona 3 pontos ao atleta selecionado</li>
            <li>Tecla 4 : Adiciona 4 pontos ao atleta selecionado</li>
        </ul>
        
        <p><strong>Marcar Vantagens e Faltas:</strong></p>
        <ul>
            <li>Tecla V : Adiciona 1 vantagem ao atleta selecionado</li>
            <li>Tecla F : Adiciona 1 falta ao atleta selecionado</li>
        </ul>
        
        <p><strong>Controles do Cronômetro:</strong></p>
        <ul>
            <li>ESPAÇO : Inicia ou pausa o cronômetro</li>
            <li>R : Reseta o cronômetro (apenas quando parado)</li>
        </ul>
        
        <p><strong>Notas:</strong></p>
        <ul>
            <li>Primeiro selecione um atleta com as setas, depois pressione as teclas de pontuação</li>
            <li>O tempo da luta é automaticamente configurado conforme idade e faixa selecionadas</li>
            <li>Os pontos 2, 3, 4 correspondem às pontuações padrão do Jiu-Jitsu</li>
        </ul>
    </footer>
</body>

<script>
    // Tabela de tempos baseada na imagem fornecida
    const tempoLutas = {
        // Idade -> Faixa -> Minutos
        'pre-mirim': {
            'todas': 2  // Todas as faixas = 2 minutos
        },
        'mirim-1': {
            'todas': 2  // Todas as faixas = 2 minutos
        },
        'mirim-2': {
            'todas': 2  // Todas as faixas = 2 minutos
        },
        'infantil-1': {
            'todas': 3  // Todas as faixas = 3 minutos
        },
        'infantil-2': {
            'todas': 3  // Todas as faixas = 3 minutos
        },
        'infanto-juvenil': {
            'branca': 4,
            'cinza': 4,
            'amarela': 4,
            'laranja': 4,
            'verde': 4,
            'azul': 4,
            'roxa': 4,
            'marrom': 4,
            'preta': 4
        },
        'juvenil': {
            'branca': 4,
            'cinza': 4,
            'amarela': 4,
            'laranja': 4,
            'verde': 4,
            'azul': 5,  // Azul = 5 minutos
            'roxa': 7,  // Roxa = 7 minutos
            'marrom': 8, // Marrom = 8 minutos
            'preta': 9   // Preta = 9 minutos
        },
        'adulto': {
            'branca': 4,
            'cinza': 4,
            'amarela': 4,
            'laranja': 4,
            'verde': 4,
            'azul': 5,  // Azul = 5 minutos
            'roxa': 7,  // Roxa = 7 minutos
            'marrom': 8, // Marrom = 8 minutos
            'preta': 9   // Preta = 9 minutos
        },
        'master': {
            'branca': 4,
            'cinza': 4,
            'amarela': 4,
            'laranja': 4,
            'verde': 4,
            'azul': 6,  // Azul e acima = 6 minutos
            'roxa': 6,  // Azul e acima = 6 minutos
            'marrom': 6, // Azul e acima = 6 minutos
            'preta': 6   // Azul e acima = 6 minutos
        }
    };

    // Variáveis do cronômetro
    let timerInterval;
    let timerRunning = false;
    let timeLeft = 0; // Começa com 0 até definir
    const timerDisplay = document.getElementById('timerDisplay');
    const configInfo = document.getElementById('configInfo');
    const idadeSelect = document.getElementById('idade-select');
    const faixaSelect = document.getElementById('faixa-select');

    // Função para calcular o tempo baseado na idade e faixa
    function calcularTempoLuta(idade, faixa) {
        // Se não selecionou idade ou faixa
        if (!idade || !faixa) {
            return null;
        }
        
        // Verifica se a idade existe na tabela
        if (!tempoLutas[idade]) {
            return null;
        }
        
        // Para idades que usam 'todas' as faixas
        if (tempoLutas[idade]['todas']) {
            return tempoLutas[idade]['todas'];
        }
        
        // Para idades com faixas específicas
        if (tempoLutas[idade][faixa]) {
            return tempoLutas[idade][faixa];
        }
        
        // Se não encontrou combinação
        return null;
    }

    // Função para atualizar o cronômetro baseado nas seleções
    function atualizarTempo() {
        const idade = idadeSelect.value;
        const faixa = faixaSelect.value;
        
        const minutos = calcularTempoLuta(idade, faixa);
        
        if (minutos !== null) {
            // Para a luta se estiver rodando
            if (timerRunning) {
                pauseTimer();
            }
            
            // Reseta para o novo tempo
            timeLeft = minutos * 60;
            updateTimerDisplay();
            
            // Atualiza informação de configuração
            const idadeTexto = idadeSelect.options[idadeSelect.selectedIndex].text;
            const faixaTexto = faixaSelect.options[faixaSelect.selectedIndex].text;
            configInfo.textContent = `${idadeTexto} - ${faixaTexto}: ${minutos} minutos`;
            
            // Reinicia o estado
            resetTimerState();
        } else {
            // Se não encontrou combinação válida
            timerDisplay.textContent = '--:--';
            configInfo.textContent = 'Selecione idade e faixa válidos';
        }
    }

    // Função para resetar o estado do cronômetro (sem resetar o tempo)
    function resetTimerState() {
        clearInterval(timerInterval);
        timerRunning = false;
        timerDisplay.classList.remove('timer-running', 'timer-paused');
        timerDisplay.classList.add('timer-stopped');
        timerDisplay.style.animation = 'none';
    }

    // Configuração padrão do cronômetro
    function updateTimerDisplay() {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }
    
    function startTimer() {
        if (timerRunning || timeLeft <= 0) return;
        
        timerRunning = true;
        timerDisplay.classList.remove('timer-stopped', 'timer-paused');
        timerDisplay.classList.add('timer-running');
        
        timerInterval = setInterval(() => {
            if (timeLeft > 0) {
                timeLeft--;
                updateTimerDisplay();
                
                // Alerta visual quando faltar 30 segundos
                if (timeLeft === 30) {
                    timerDisplay.style.animation = 'pulse 1s infinite';
                }
                
                // Alerta visual quando o tempo acabar
                if (timeLeft === 0) {
                    timerDisplay.classList.remove('timer-running');
                    timerDisplay.classList.add('timer-stopped');
                    timerDisplay.style.animation = 'none';
                    clearInterval(timerInterval);
                    timerRunning = false;
                    // Opcional: adicionar um som de alerta aqui
                }
            } else {
                clearInterval(timerInterval);
                timerRunning = false;
            }
        }, 1000);
    }
    
    function pauseTimer() {
        if (!timerRunning) return;
        
        clearInterval(timerInterval);
        timerRunning = false;
        timerDisplay.classList.remove('timer-running');
        timerDisplay.classList.add('timer-paused');
        timerDisplay.style.animation = 'none';
    }
    
    function resetTimer() {
        clearInterval(timerInterval);
        timerRunning = false;
        // Volta para o tempo configurado atual
        const idade = idadeSelect.value;
        const faixa = faixaSelect.value;
        const minutos = calcularTempoLuta(idade, faixa);
        
        if (minutos !== null) {
            timeLeft = minutos * 60;
            updateTimerDisplay();
        }
        
        timerDisplay.classList.remove('timer-running', 'timer-paused');
        timerDisplay.classList.add('timer-stopped');
        timerDisplay.style.animation = 'none';
    }
    
    // Adicionar animação de pulso
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    `;
    document.head.appendChild(style);
    
    // Evento para limpar dados ao recarregar
    window.addEventListener("DOMContentLoaded", () => {
        // Zerar lado esquerdo
        document.getElementById("ponto-left").value = 0;
        document.getElementById("vantagem-left").value = 0;
        document.getElementById("falta-left").value = 0;
        document.getElementById("vencedor-left").checked = false;

        // Zerar lado direito
        document.getElementById("ponto-right").value = 0;
        document.getElementById("vantagem-right").value = 0;
        document.getElementById("falta-right").value = 0;
        document.getElementById("vencedor-right").checked = false;
        
        // Configurar eventos para os selects
        idadeSelect.addEventListener('change', atualizarTempo);
        faixaSelect.addEventListener('change', atualizarTempo);
        
        // Inicializar com valores padrão se já estiverem selecionados
        if (idadeSelect.value && faixaSelect.value) {
            atualizarTempo();
        } else {
            timerDisplay.textContent = '--:--';
            configInfo.textContent = 'Selecione idade e faixa';
        }
    });

    // Selecionar os dois contadores
    const direita = document.getElementById("dir");
    const esquerda = document.getElementById("esqr");
    let selecionado;
    let pontos;
    let vantagem;
    let falta;
    let pontos_validos = [2, 3, 4]
    let keys_validos = ["F", "V"];

    document.addEventListener("keyup", function (event) {
        // Controle do cronômetro com ESPAÇO
        if (event.code === "Space") {
            event.preventDefault(); // Previne comportamento padrão (rolar página)
            if (timerRunning) {
                pauseTimer();
            } else {
                startTimer();
            }
            return; // Não processar outros controles quando espaço for pressionado
        }
        
        // Reset do cronômetro com R
        if (event.code === "KeyR" && !timerRunning) {
            resetTimer();
            return;
        }

        // Caso do lado direito
        if (event.code === "ArrowRight") {
            // Desselecionar o lado esquerdo
            esquerda.style.border = "none";

            // Selecionar lado direito
            selecionado = direita;

            // Selecionar os pontos da direita
            pontos = document.getElementById("ponto-right");
            vantagem = document.getElementById("vantagem-right");
            falta = document.getElementById("falta-right");

            selecionado.style.border = "solid black 2px";
        }

        // Caso do lado esquerdo
        if (event.code === "ArrowLeft") {
            direita.style.border = "none";
            selecionado = esquerda;

            // Selecionar os pontos da esquerda
            pontos = document.getElementById("ponto-left");
            vantagem = document.getElementById("vantagem-left");
            falta = document.getElementById("falta-left");
            selecionado.style.border = "solid black 2px";
        }

        // Adicionar os pontos
        if (pontos_validos.includes(Number(event.key))) {
            if (pontos) {
                console.log(Number(event.key));
                let valor = Number(pontos.value) + Number(event.key);
                pontos.value = String(valor);
            }
        }

        // Adicionar faltas e vantagens
        if (keys_validos.includes(event.key.toUpperCase())) {
            if (vantagem && falta) {
                console.log(event.key.toLocaleUpperCase());
                valor_f = Number(falta.value);
                valor_v = Number(vantagem.value);

                if(event.key.toUpperCase() == "F"){
                    valor_f++;
                    falta.value = String(valor_f);
                }

                if(event.key.toUpperCase() == "V"){
                    valor_v++;
                    vantagem.value = String(valor_v);
                }
            }
        }
    });
</script>

</html>