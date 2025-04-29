INSERT INTO evento (nome, descricao, data_limite, data_evento, local_evento, tipo_com, tipo_sem, imagen, preco)
VALUES 
('Campeonato Paulista de Jiu-Jitsu', 'Competição estadual de Jiu-Jitsu com atletas de diversos níveis.', '2025-12-31', '2025-12-31', 'Ginásio Arena Paulista, SP', 1, 0, 'campeonato_paulista.jpg', 100.00),
('Campeonato Brasileiro de Jiu-Jitsu', 'O maior campeonato nacional de Jiu-Jitsu, com presença de campeões mundiais.', '2025-01-25', '2025-01-26', 'Ginásio do Ibirapuera, SP', 0, 1, 'campeonato_brasileiro.jpg', 150.00),
('Copa São Paulo de Jiu-Jitsu', 'Competição voltada para atletas iniciantes e intermediários, com várias categorias.', '2025-01-30', '2025-02-05', 'Arena São Paulo, SP', 1, 0, 'copa_sp_jiu_jitsu.jpg', 200.00),
('Copa do Mundo de Jiu-Jitsu', 'Competição internacional com os melhores atletas de Jiu-Jitsu do mundo.', '2025-01-15', '2025-02-10', 'Centro de Convenções, Rio de Janeiro', 0, 1, 'copa_mundo_jiu_jitsu.jpg', 0.00),
('Campeonato Internacional de Jiu-Jitsu', 'Competição com atletas de todo o mundo, reunindo os melhores do Jiu-Jitsu.', '2025-02-01', '2025-02-15', 'Arena Internacional, Rio de Janeiro', 1, 0, 'campeonato_internacional.jpg', 500.00),
('Copa Rio de Jiu-Jitsu', 'Competição aberta para todos os atletas de Jiu-Jitsu no estado do Rio de Janeiro.', '2025-03-05', '2025-03-10', 'Ginásio Carioca, RJ', 1, 0, 'copa_rio_jiu_jitsu.jpg', 80.00),
('Desafio de Jiu-Jitsu', 'Desafios entre atletas conhecidos em combate direto.', '2025-02-20', '2025-03-01', 'Ginásio da Luta, SP', 0, 1, 'desafio_jiu_jitsu.jpg', 30.00),
('Campeonato de Jiu-Jitsu do Norte', 'Campeonato regional de Jiu-Jitsu reunindo os melhores atletas do Norte do Brasil.', '2025-02-10', '2025-02-14', 'Ginásio Norte, Manaus', 0, 1, 'campeonato_norte.jpg', 50.00),
('Copa Norte-Nordeste de Jiu-Jitsu', 'Competição reunindo atletas de todas as regiões Norte e Nordeste do Brasil.', '2025-02-01', '2025-02-05', 'Arena Nordeste, Fortaleza', 1, 0, 'copa_norte_nordeste.jpg', 25.00),
('Super Liga Brasileira de Jiu-Jitsu', 'Liga profissional de Jiu-Jitsu com competições em várias cidades do Brasil.', '2025-03-10', '2025-03-15', 'Centro de Treinamento, São Paulo', 1, 0, 'super_liga_jiu_jitsu.jpg', 150.00);

CREATE DATABASE IF NOT EXISTS usuario;
    USE usuario;
    
    CREATE TABLE IF NOT EXISTS academia_filiada(
        id INT NOT NULL AUTO_INCREMENT,
        nome VARCHAR(100) NOT NULL,
        cep VARCHAR(20) NOT NULL,
        estado VARCHAR (2) NOT NULL,
        cidade VARCHAR (50) NOT NULL,
        responsavel INT,
        PRIMARY KEY (id)
        );

    CREATE TABLE IF NOT EXISTS atleta(
        id INT NOT NULL AUTO_INCREMENT,
        nome VARCHAR(50) NOT NULL,
        cpf VARCHAR(19),
        foto VARCHAR(30) NOT NULL,
        senha VARCHAR(100) NOT NULL,
        email VARCHAR (100) NOT NULL,
        data_nascimento DATE NOT NULL,
        fone VARCHAR(15) NOT NULL,
        academia INT,
        faixa VARCHAR(30) NOT NULL,
        peso FLOAT(5,2) NOT NULL,
        validado TINYINT NOT NULL,
        adm TINYINT default 0,
        genero VARCHAR(9),
        responsavel TINYINT NOT NULL,
        diploma VARCHAR(30) NOT NULL,
        FOREIGN KEY (academia) REFERENCES academia_filiada(id),
        PRIMARY KEY (id)
    );

#--tabela dos eventos
CREATE TABLE IF NOT EXISTS evento(
    id INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    data_limite DATE,
    data_evento DATE,
    local_evento VARCHAR(100),
    tipo_com TINYINT,
    tipo_sem TINYINT,
    imagen VARCHAR(30),
    preco FLOAT(5,2),
    preco_menor FLOAT(5,2),
    preco_abs FLOAT(5,2),
    doc VARCHAR (20),
    PRIMARY KEY (id)
);

#--tabela pra relacionas qual atleta se cadatra em cada evento
    CREATE TABLE IF NOT EXISTS inscricao(
        id_atleta INT,
        id_evento INT,
        mod_com TINYINT,
        mod_sem TINYINT,
        mod_ab_com TINYINT,
        mod_ab_sem TINYINT,
        modalidade VARCHAR(18),
        pago TINYINT(1) default 0,
        PRIMARY KEY (id_atleta, id_evento),
        FOREIGN KEY (id_atleta) REFERENCES atleta(id),
        FOREIGN KEY (id_evento) REFERENCES evento(id)
    );

CREATE TABLE IF NOT EXISTS galeria (
    id INT NOT NULL AUTO_INCREMENT,
    legenda VARCHAR(100),
    imagem VARCHAR(20) NOT NULL,
    PRIMARY KEY (id)
);

-- Tabela inscricao
ALTER TABLE inscricao ADD COLUMN id_cobranca_asaas VARCHAR(50) NULL,
ALTER TABLE inscricao ADD COLUMN status_pagamento VARCHAR(20) DEFAULT 'PENDING',
ALTER TABLE inscricao ADD COLUMN valor_pago FLOAT(5,2) NULL,