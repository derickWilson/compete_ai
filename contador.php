<!DOCTYPE html>
<html>

<head>
    <title>Contador de Pontos</title>
    <style>
        /* ===== VARIÁVEIS E ESTILOS BASE ===== */
        :root {
            --primary-dark: #2520a0;
            --primary: #322ec0;
            --primary-light: #4a45d9;
            --secondary: #d14141;
            --accent: #e9b949;
            --light: #f8f9fa;
            --dark: #2d3748;
            --gray: #718096;
            --success: #38a169;
            --warning: #d69e2e;
            --danger: #e53e3e;
            --white: #ffffff;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            height: 100%;
            overflow: hidden;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-dark) 0%, #1a1a2e 100%);
            color: var(--dark);
            line-height: 1.6;
            height: 100vh;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            width: 100%;
            height: 100vh;
            margin: 0;
            background: var(--white);
            display: flex;
            flex-direction: column;
        }

        /* ===== CABEÇALHO COMPACTO ===== */
        .cabecalho {
            background: linear-gradient(to right, var(--primary-dark), var(--primary));
            color: var(--white);
            padding: 5px 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            height: 50px;
            border-bottom: 2px solid var(--accent);
        }

        .cabecalho h1 {
            font-size: 16px;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            white-space: nowrap;
        }

        .filtros {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .filtro-group {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .filtro-group label {
            font-weight: 600;
            font-size: 10px;
            color: var(--accent);
            white-space: nowrap;
        }

        .cabecalho select {
            padding: 3px 6px;
            border-radius: 4px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: var(--white);
            font-weight: 500;
            min-width: 110px;
            transition: var(--transition);
            font-size: 11px;
            height: 24px;
        }

        .cabecalho select:hover {
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.15);
        }

        .cabecalho select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(233, 185, 73, 0.3);
        }

        .cabecalho option {
            background: var(--primary-dark);
            color: var(--white);
        }

        /* ===== INSTRUÇÕES COMPACTAS ===== */
        .instrucoes-container {
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
            color: var(--white);
            padding: 4px 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            flex-shrink: 0;
            height: 30px;
            overflow: hidden;
        }

        .instrucoes-linha {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            height: 100%;
        }

        .instrucao-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .tecla {
            display: inline-block;
            background: var(--primary);
            color: var(--white);
            padding: 1px 5px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            margin: 0 2px;
            min-width: 18px;
            text-align: center;
            font-size: 10px;
            height: 16px;
            line-height: 14px;
        }

        .instrucao-texto {
            color: var(--accent);
            font-weight: 600;
            margin-right: 4px;
        }

        .instrucao-descricao {
            color: rgba(255, 255, 255, 0.8);
        }

        /* ===== CRONÔMETRO GRANDE ===== */
        .timer-container {
            padding: 15px 20px;
            text-align: center;
            background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
            border-bottom: 1px solid #dee2e6;
            flex-shrink: 0;
            min-height: 160px; /* Reduzido */
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .timer-display {
            font-family: 'Courier New', monospace;
            font-size: 85px; /* Reduzido ligeiramente */
            font-weight: bold;
            padding: 18px 35px; /* Reduzido */
            border-radius: var(--border-radius);
            display: inline-block;
            min-width: 320px; /* Reduzido */
            margin: 0 auto;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            transition: var(--transition);
            letter-spacing: 2px;
        }

        .timer-stopped {
            background: linear-gradient(135deg, var(--success) 0%, #2d8f4d 100%);
            color: var(--white);
            border: 4px solid #2d8f4d;
        }

        .timer-running {
            background: linear-gradient(135deg, var(--danger) 0%, #c53030 100%);
            color: var(--white);
            border: 4px solid #c53030;
            animation: pulse 1.5s infinite;
        }

        .timer-paused {
            background: linear-gradient(135deg, var(--warning) 0%, #b7791f 100%);
            color: var(--white);
            border: 4px solid #b7791f;
        }

        .timer-info-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px; /* Reduzido */
            margin-top: 6px; /* Reduzido */
        }

        .config-info {
            font-size: 12px; /* Reduzido */
            color: var(--primary-dark);
            font-weight: 500;
            background: rgba(233, 185, 73, 0.1);
            padding: 4px 10px; /* Reduzido */
            border-radius: 20px;
            display: inline-block;
        }

        .timer-controles {
            font-size: 10px; /* Reduzido */
            color: var(--gray);
            background: var(--white);
            padding: 3px 8px; /* Reduzido */
            border-radius: var(--border-radius);
            display: inline-block;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .timer-controles strong {
            color: var(--primary-dark);
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.03); }
            100% { transform: scale(1); }
        }

        /* ===== ÁREA DE PONTUAÇÃO COMPACTA ===== */
        .placar-container {
            display: flex;
            padding: 15px 20px 20px 20px; /* Reduzido top */
            gap: 25px; /* Reduzido */
            justify-content: center;
            background: var(--white);
            flex: 1;
            overflow: hidden;
            min-height: 0;
        }

        .atleta-container {
            flex: 1;
            background: var(--light);
            border-radius: var(--border-radius);
            padding: 20px; /* Reduzido */
            box-shadow: var(--box-shadow);
            border-top: 8px solid var(--primary);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            max-width: 500px;
            margin: 0 10px;
        }

        /* Atleta CLARO (esquerda) - fundo claro */
        #esqr {
            background: linear-gradient(135deg, #ffffff 0%, #f5f5f5 100%);
            border-top-color: #cccccc;
        }

        /* Atleta AZUL (direita) - fundo azul ESCURO */
        #dir {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            border-top-color: #3b82f6;
        }

        .atleta-container.selecionado {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transform: translateY(-5px);
        }

        /* Quando selecionado, intensificar o fundo */
        #esqr.selecionado {
            background: linear-gradient(135deg, #ffffff 0%, #e8e8e8 100%);
            border-top-color: #999999;
        }

        #dir.selecionado {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            border-top-color: #60a5fa;
        }

        .atleta-container h2 {
            text-align: center;
            margin-bottom: 15px; /* Reduzido */
            padding-bottom: 8px; /* Reduzido */
            border-bottom: 3px solid;
            font-size: 20px; /* Reduzido */
            flex-shrink: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: bold;
        }

        /* Cor do texto e borda para CLARO */
        #esqr h2 {
            color: #333333;
            border-bottom-color: #999999;
        }

        /* Cor do texto e borda para AZUL */
        #dir h2 {
            color: #ffffff;
            border-bottom-color: #60a5fa;
        }

        /* PONTOS GRANDE */
        .pontos-container {
            margin-bottom: 10px; /* Reduzido */
            flex-shrink: 0;
        }

        .pontos-container label {
            display: block;
            margin-bottom: 6px; /* Reduzido */
            font-weight: 700;
            font-size: 16px; /* Reduzido */
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Cor do label para CLARO */
        #esqr .pontos-container label {
            color: #333333;
        }

        /* Cor do label para AZUL */
        #dir .pontos-container label {
            color: #ffffff;
        }

        .pontos-input {
            width: 100%;
            padding: 20px 15px; /* Reduzido */
            border: 3px solid;
            border-radius: var(--border-radius);
            font-size: 52px; /* Reduzido */
            font-weight: bold;
            text-align: center;
            transition: var(--transition);
            font-family: 'Courier New', monospace;
            height: 100px; /* Reduzido */
        }

        /* Input para CLARO */
        #esqr .pontos-input {
            background: #ffffff;
            color: #333333;
            border-color: #cccccc;
        }

        #esqr .pontos-input:focus {
            outline: none;
            border-color: #999999;
            box-shadow: 0 0 0 4px rgba(153, 153, 153, 0.15);
        }

        /* Input para AZUL */
        #dir .pontos-input {
            background: rgba(255, 255, 255, 0.9);
            color: #1e40af;
            border-color: #60a5fa;
        }

        #dir .pontos-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
        }

        /* VANTAGENS E FALTAS LADO A LADO */
        .vantagens-faltas-container {
            display: flex;
            gap: 12px; /* Reduzido */
            margin-bottom: 10px; /* Reduzido */
        }

        .vantagens-container, .faltas-container {
            flex: 1;
        }

        .vantagens-container label, .faltas-container label {
            display: block;
            margin-bottom: 5px; /* Reduzido */
            font-weight: 600;
            font-size: 13px; /* Reduzido */
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Cor do label para CLARO */
        #esqr .vantagens-container label,
        #esqr .faltas-container label {
            color: #333333;
        }

        /* Cor do label para AZUL */
        #dir .vantagens-container label,
        #dir .faltas-container label {
            color: #ffffff;
        }

        .vantagem-input, .falta-input {
            width: 100%;
            padding: 12px 8px; /* Reduzido */
            border: 2px solid;
            border-radius: var(--border-radius);
            font-size: 28px; /* Reduzido */
            font-weight: bold;
            text-align: center;
            transition: var(--transition);
            font-family: 'Courier New', monospace;
            height: 60px; /* Reduzido */
        }

        /* Inputs para CLARO */
        #esqr .vantagem-input,
        #esqr .falta-input {
            background: #ffffff;
            color: #333333;
            border-color: #cccccc;
        }

        #esqr .vantagem-input:focus,
        #esqr .falta-input:focus {
            outline: none;
            border-color: #999999;
            box-shadow: 0 0 0 3px rgba(153, 153, 153, 0.1);
        }

        /* Inputs para AZUL */
        #dir .vantagem-input,
        #dir .falta-input {
            background: rgba(255, 255, 255, 0.9);
            color: #1e40af;
            border-color: #60a5fa;
        }

        #dir .vantagem-input:focus,
        #dir .falta-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* VENCEDOR */
        .vencedor-container {
            margin-top: 10px; /* Reduzido */
            padding-top: 10px; /* Reduzido */
            border-top: 2px solid;
            text-align: center;
            flex-shrink: 0;
        }

        /* Borda para CLARO */
        #esqr .vencedor-container {
            border-top-color: #eeeeee;
        }

        /* Borda para AZUL */
        #dir .vencedor-container {
            border-top-color: rgba(255, 255, 255, 0.2);
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px; /* Reduzido */
            cursor: pointer;
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: 24px; /* Reduzido */
            height: 24px; /* Reduzido */
            cursor: pointer;
            transform: scale(1.3); /* Reduzido */
        }

        .checkbox-wrapper label {
            font-size: 18px; /* Reduzido */
            font-weight: 700;
            cursor: pointer;
            margin: 0;
            text-transform: uppercase;
        }

        /* Label para CLARO */
        #esqr .checkbox-wrapper label {
            color: #333333;
        }

        /* Label para AZUL */
        #dir .checkbox-wrapper label {
            color: #ffffff;
        }

        /* ===== STATUS BAR ===== */
        .status-bar {
            background: linear-gradient(to right, var(--dark), #1a202c);
            color: var(--white);
            padding: 3px 15px;
            font-size: 10px;
            text-align: center;
            flex-shrink: 0;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .status-text {
            color: var(--accent);
            font-weight: 600;
        }

        /* ===== ANIMAÇÕES ===== */
        @keyframes highlight {
            0% { background-color: var(--white); }
            50% { background-color: rgba(233, 185, 73, 0.2); }
            100% { background-color: var(--white); }
        }

        .destaque {
            animation: highlight 1s ease;
        }

        /* ===== RESPONSIVIDADE ===== */
        @media (max-width: 1200px) {
            .timer-display {
                font-size: 65px;
                min-width: 280px;
                padding: 15px 30px;
            }
            
            .pontos-input {
                font-size: 45px;
                height: 90px;
                padding: 18px 12px;
            }
            
            .vantagem-input, .falta-input {
                font-size: 24px;
                height: 55px;
                padding: 10px 6px;
            }
            
            .atleta-container h2 {
                font-size: 18px;
            }
        }

        @media (max-width: 992px) {
            .placar-container {
                flex-direction: column;
                align-items: center;
                padding: 12px 15px 15px 15px;
                gap: 18px;
            }
            
            .atleta-container {
                width: 100%;
                max-width: 650px;
                margin: 0;
                padding: 18px;
            }
            
            .instrucoes-linha {
                flex-wrap: wrap;
                height: auto;
                padding: 3px 0;
            }
            
            .instrucao-item {
                margin: 1px 0;
            }
        }

        @media (max-width: 768px) {
            .timer-display {
                font-size: 45px;
                min-width: 200px;
                padding: 12px 20px;
            }
            
            .cabecalho {
                flex-direction: column;
                height: auto;
                padding: 8px 10px;
                gap: 8px;
            }
            
            .filtros {
                width: 100%;
                justify-content: center;
            }
            
            .pontos-input {
                font-size: 36px;
                height: 75px;
                padding: 12px 8px;
            }
            
            .vantagem-input, .falta-input {
                font-size: 20px;
                height: 45px;
                padding: 8px 5px;
            }
            
            .atleta-container h2 {
                font-size: 16px;
            }
            
            .checkbox-wrapper label {
                font-size: 16px;
            }
            
            .instrucoes-container {
                height: auto;
                min-height: 40px;
            }
            
            .instrucoes-linha {
                flex-direction: column;
                align-items: flex-start;
                gap: 2px;
            }
        }

        @media (max-height: 800px) {
            .timer-display {
                font-size: 55px;
                padding: 12px 20px;
            }
            
            .pontos-input {
                font-size: 36px;
                height: 70px;
                padding: 12px 8px;
            }
            
            .vantagem-input, .falta-input {
                font-size: 20px;
                height: 45px;
                padding: 8px 5px;
            }
            
            .atleta-container {
                padding: 15px;
            }
            
            .atleta-container h2 {
                margin-bottom: 10px;
                font-size: 18px;
            }
        }

        @media (max-height: 700px) {
            .timer-container {
                min-height: 140px;
                padding: 10px 15px;
            }
            
            .timer-display {
                font-size: 50px;
                padding: 10px 15px;
                min-width: 250px;
            }
            
            .placar-container {
                padding: 10px 15px 15px 15px;
            }
            
            .atleta-container {
                padding: 12px;
            }
            
            .pontos-input {
                font-size: 32px;
                height: 65px;
                padding: 10px 6px;
            }
            
            .vantagem-input, .falta-input {
                font-size: 18px;
                height: 40px;
                padding: 6px 4px;
            }
        }

        /* Scroll personalizado */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }
    </style>
    <!-- Adicionando Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <div class="cabecalho">
            <h1><i class="fas fa-stopwatch"></i> CONTADOR DE LUTA - JIU-JITSU</h1>
            <div class="filtros">
                <div class="filtro-group">
                    <label for="categoria-select"><i class="fas fa-weight"></i> Categoria:</label>
                    <select id="categoria-select">
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
                </div>
                
                <div class="filtro-group">
                    <label for="idade-select"><i class="fas fa-user"></i> Idade:</label>
                    <select id="idade-select">
                        <option value="pre-mirim">Pre-Mirim</option>
                        <option value="mirim-1">Mirim 1</option>
                        <option value="mirim-2">Mirim 2</option>
                        <option value="infantil-1">Infantil 1</option>
                        <option value="infantil-2">Infantil 2</option>
                        <option value="infanto-juvenil">Infanto-Juvenil</option>
                        <option value="juvenil">Juvenil</option>
                        <option value="adulto">Adulto</option>
                        <option value="master">Master</option>
                    </select>
                </div>
                
                <div class="filtro-group">
                    <label for="faixa-select"><i class="fas fa-award"></i> Faixa:</label>
                    <select id="faixa-select">
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
            </div>
        </div>

        <div class="instrucoes-container">
            <div class="instrucoes-linha">
                <div class="instrucao-item">
                    <span class="instrucao-texto">SELEÇÃO:</span>
                    <span class="tecla">←</span>
                    <span class="instrucao-descricao">CLARO</span>
                    <span class="tecla">→</span>
                    <span class="instrucao-descricao">AZUL</span>
                </div>
                
                <div class="instrucao-item">
                    <span class="instrucao-texto">PONTOS:</span>
                    <span class="tecla">2</span>
                    <span class="tecla">3</span>
                    <span class="tecla">4</span>
                </div>
                
                <div class="instrucao-item">
                    <span class="instrucao-texto">VANTAGENS/FALTAS:</span>
                    <span class="tecla">V</span>
                    <span class="instrucao-descricao">+1 Vantagem</span>
                    <span class="tecla">F</span>
                    <span class="instrucao-descricao">+1 Falta</span>
                </div>
                
                <div class="instrucao-item">
                    <span class="instrucao-texto">CRONÔMETRO:</span>
                    <span class="tecla">ESPAÇO</span>
                    <span class="instrucao-descricao">Iniciar/Pausar</span>
                    <span class="tecla">R</span>
                    <span class="instrucao-descricao">Resetar</span>
                </div>
            </div>
        </div>

        <div class="timer-container">
            <div id="timerDisplay" class="timer-display timer-stopped">--:--</div>
            <div class="timer-info-container">
                <div id="configInfo" class="config-info">Selecione idade e faixa para configurar o tempo</div>
                <div class="timer-controles">
                    <strong>ESPAÇO</strong> Iniciar/Pausar • <strong>R</strong> Resetar
                </div>
            </div>
        </div>

        <div class="placar-container">
            <div class="atleta-container" id="esqr">
                <h2><i class="fas fa-user-fighter"></i> ATLETA CLARO</h2>
                
                <div class="pontos-container">
                    <label>Pontos:</label>
                    <input type="number" name="ponto-left" id="ponto-left" value="0" min="0" class="pontos-input">
                </div>
                
                <div class="vantagens-faltas-container">
                    <div class="vantagens-container">
                        <label>Vantagens:</label>
                        <input type="number" name="vantagem-left" id="vantagem-left" value="0" min="0" class="vantagem-input">
                    </div>
                    
                    <div class="faltas-container">
                        <label>Faltas:</label>
                        <input type="number" name="falta-left" id="falta-left" value="0" min="0" class="falta-input">
                    </div>
                </div>
                
                <div class="vencedor-container">
                    <div class="checkbox-wrapper">
                        <input type="checkbox" name="vencedor-left" id="vencedor-left">
                        <label for="vencedor-left">VENCEDOR</label>
                    </div>
                </div>
            </div>

            <div class="atleta-container" id="dir">
                <h2><i class="fas fa-user-fighter"></i> ATLETA AZUL</h2>
                
                <div class="pontos-container">
                    <label>Pontos:</label>
                    <input type="number" name="ponto-right" id="ponto-right" value="0" min="0" class="pontos-input">
                </div>
                
                <div class="vantagens-faltas-container">
                    <div class="vantagens-container">
                        <label>Vantagens:</label>
                        <input type="number" name="vantagem-right" id="vantagem-right" value="0" min="0" class="vantagem-input">
                    </div>
                    
                    <div class="faltas-container">
                        <label>Faltas:</label>
                        <input type="number" name="falta-right" id="falta-right" value="0" min="0" class="falta-input">
                    </div>
                </div>
                
                <div class="vencedor-container">
                    <div class="checkbox-wrapper">
                        <input type="checkbox" name="vencedor-right" id="vencedor-right">
                        <label for="vencedor-right">VENCEDOR</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="status-bar">
            <span class="status-text">SISTEMA PRONTO</span>
        </div>
    </div>

    <script>
        // (O JavaScript permanece exatamente o mesmo)
        // Tabela de tempos baseada na imagem fornecida
        const tempoLutas = {
            'pre-mirim': { 'todas': 2 },
            'mirim-1': { 'todas': 2 },
            'mirim-2': { 'todas': 2 },
            'infantil-1': { 'todas': 3 },
            'infantil-2': { 'todas': 3 },
            'infanto-juvenil': {
                'branca': 4, 'cinza': 4, 'amarela': 4, 'laranja': 4,
                'verde': 4, 'azul': 4, 'roxa': 4, 'marrom': 4, 'preta': 4
            },
            'juvenil': {
                'branca': 4, 'cinza': 4, 'amarela': 4, 'laranja': 4,
                'verde': 4, 'azul': 5, 'roxa': 7, 'marrom': 8, 'preta': 9
            },
            'adulto': {
                'branca': 4, 'cinza': 4, 'amarela': 4, 'laranja': 4,
                'verde': 4, 'azul': 5, 'roxa': 7, 'marrom': 8, 'preta': 9
            },
            'master': {
                'branca': 4, 'cinza': 4, 'amarela': 4, 'laranja': 4,
                'verde': 4, 'azul': 6, 'roxa': 6, 'marrom': 6, 'preta': 6
            }
        };

        // Variáveis do cronômetro
        let timerInterval;
        let timerRunning = false;
        let timeLeft = 0;
        const timerDisplay = document.getElementById('timerDisplay');
        const configInfo = document.getElementById('configInfo');
        const idadeSelect = document.getElementById('idade-select');
        const faixaSelect = document.getElementById('faixa-select');
        const categoriaSelect = document.getElementById('categoria-select');
        const statusBar = document.querySelector('.status-text');

        // Função para calcular o tempo baseado na idade e faixa
        function calcularTempoLuta(idade, faixa) {
            if (!idade || !faixa) return null;
            if (!tempoLutas[idade]) return null;
            
            if (tempoLutas[idade]['todas']) {
                return tempoLutas[idade]['todas'];
            }
            
            if (tempoLutas[idade][faixa]) {
                return tempoLutas[idade][faixa];
            }
            
            return null;
        }

        // Função para atualizar o cronômetro baseado nas seleções
        function atualizarTempo() {
            const idade = idadeSelect.value;
            const faixa = faixaSelect.value;
            
            const minutos = calcularTempoLuta(idade, faixa);
            
            if (minutos !== null) {
                if (timerRunning) pauseTimer();
                
                timeLeft = minutos * 60;
                updateTimerDisplay();
                
                const idadeTexto = idadeSelect.options[idadeSelect.selectedIndex].text;
                const faixaTexto = faixaSelect.options[faixaSelect.selectedIndex].text;
                configInfo.innerHTML = `<i class="fas fa-cog"></i> ${idadeTexto} - ${faixaTexto}: ${minutos} minutos`;
                statusBar.textContent = `CONFIGURADO: ${idadeTexto} - ${faixaTexto} (${minutos} min)`;
                
                configInfo.classList.add('destaque');
                setTimeout(() => configInfo.classList.remove('destaque'), 1000);
                
                resetTimerState();
            } else {
                timerDisplay.textContent = '--:--';
                configInfo.textContent = 'Selecione idade e faixa válidos';
                statusBar.textContent = 'AGUARDANDO CONFIGURAÇÃO';
            }
        }

        // Função para resetar o estado do cronômetro
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
            statusBar.textContent = 'CRONÔMETRO EM ANDAMENTO';
            
            timerInterval = setInterval(() => {
                if (timeLeft > 0) {
                    timeLeft--;
                    updateTimerDisplay();
                    
                    if (timeLeft === 30) {
                        timerDisplay.style.animation = 'pulse 1s infinite';
                        statusBar.textContent = 'ATENÇÃO: 30 SEGUNDOS RESTANTES';
                    }
                    
                    if (timeLeft === 0) {
                        timerDisplay.classList.remove('timer-running');
                        timerDisplay.classList.add('timer-stopped');
                        timerDisplay.style.animation = 'none';
                        clearInterval(timerInterval);
                        timerRunning = false;
                        statusBar.textContent = 'TEMPO ESGOTADO!';
                        playAlertSound();
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
            statusBar.textContent = 'CRONÔMETRO PAUSADO';
        }
        
        function resetTimer() {
            clearInterval(timerInterval);
            timerRunning = false;
            const idade = idadeSelect.value;
            const faixa = faixaSelect.value;
            const minutos = calcularTempoLuta(idade, faixa);
            
            if (minutos !== null) {
                timeLeft = minutos * 60;
                updateTimerDisplay();
                statusBar.textContent = 'CRONÔMETRO RESETADO';
            }
            
            timerDisplay.classList.remove('timer-running', 'timer-paused');
            timerDisplay.classList.add('timer-stopped');
            timerDisplay.style.animation = 'none';
        }
        
        function playAlertSound() {
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.value = 800;
                oscillator.type = 'sine';
                
                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 1);
                
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 1);
            } catch (e) {
                console.log("Som de alerta não suportado");
            }
        }
        
        // Adicionar animação de pulso
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.03); }
                100% { transform: scale(1); }
            }
        `;
        document.head.appendChild(style);
        
        // Evento para limpar dados ao recarregar
        window.addEventListener("DOMContentLoaded", () => {
            // Zerar todos os valores
            document.getElementById("ponto-left").value = 0;
            document.getElementById("vantagem-left").value = 0;
            document.getElementById("falta-left").value = 0;
            document.getElementById("vencedor-left").checked = false;

            document.getElementById("ponto-right").value = 0;
            document.getElementById("vantagem-right").value = 0;
            document.getElementById("falta-right").value = 0;
            document.getElementById("vencedor-right").checked = false;
            
            // Configurar eventos para os selects
            idadeSelect.addEventListener('change', atualizarTempo);
            faixaSelect.addEventListener('change', atualizarTempo);
            categoriaSelect.addEventListener('change', () => {
                categoriaSelect.classList.add('destaque');
                setTimeout(() => categoriaSelect.classList.remove('destaque'), 500);
                statusBar.textContent = `CATEGORIA: ${categoriaSelect.options[categoriaSelect.selectedIndex].text}`;
            });
            
            // Inicializar cronômetro
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

        // Adicionar evento keydown para prevenir comportamento padrão do espaço
        document.addEventListener("keydown", function (event) {
            // Prevenir rolagem quando espaço for pressionado
            if (event.code === "Space") {
                event.preventDefault();
            }
        });

        document.addEventListener("keyup", function (event) {
            // Controle do cronômetro com ESPAÇO
            if (event.code === "Space") {
                event.preventDefault();
                if (timerRunning) {
                    pauseTimer();
                } else {
                    startTimer();
                }
                return;
            }
            
            // Reset do cronômetro com R
            if (event.code === "KeyR" && !timerRunning) {
                resetTimer();
                return;
            }

            // Caso do lado direito (AZUL)
            if (event.code === "ArrowRight") {
                esquerda.classList.remove("selecionado");
                selecionado = direita;
                selecionado.classList.add("selecionado");

                pontos = document.getElementById("ponto-right");
                vantagem = document.getElementById("vantagem-right");
                falta = document.getElementById("falta-right");

                selecionado.classList.add('destaque');
                setTimeout(() => selecionado.classList.remove('destaque'), 500);
                statusBar.textContent = 'ATLETA AZUL SELECIONADO';
            }

            // Caso do lado esquerdo (CLARO)
            if (event.code === "ArrowLeft") {
                direita.classList.remove("selecionado");
                selecionado = esquerda;
                selecionado.classList.add("selecionado");

                pontos = document.getElementById("ponto-left");
                vantagem = document.getElementById("vantagem-left");
                falta = document.getElementById("falta-left");
                
                selecionado.classList.add('destaque');
                setTimeout(() => selecionado.classList.remove('destaque'), 500);
                statusBar.textContent = 'ATLETA CLARO SELECIONADO';
            }

            // Adicionar os pontos
            if (pontos_validos.includes(Number(event.key))) {
                if (pontos) {
                    let valor = Number(pontos.value) + Number(event.key);
                    pontos.value = String(valor);
                    
                    pontos.classList.add('destaque');
                    setTimeout(() => pontos.classList.remove('destaque'), 300);
                    statusBar.textContent = `PONTOS ADICIONADOS: +${event.key}`;
                }
            }

            // Adicionar faltas e vantagens
            if (keys_validos.includes(event.key.toUpperCase())) {
                if (vantagem && falta) {
                    if(event.key.toUpperCase() == "F"){
                        let valor_f = Number(falta.value) + 1;
                        falta.value = String(valor_f);
                        falta.classList.add('destaque');
                        setTimeout(() => falta.classList.remove('destaque'), 300);
                        statusBar.textContent = 'FALTA ADICIONADA: +1';
                    }

                    if(event.key.toUpperCase() == "V"){
                        let valor_v = Number(vantagem.value) + 1;
                        vantagem.value = String(valor_v);
                        vantagem.classList.add('destaque');
                        setTimeout(() => vantagem.classList.remove('destaque'), 300);
                        statusBar.textContent = 'VANTAGEM ADICIONADA: +1';
                    }
                }
            }
        });
    </script>
</body>
</html>