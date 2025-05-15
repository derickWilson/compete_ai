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
        id_cobranca_asaas VARCHAR(50) NULL,
        status_pagamento VARCHAR(20) DEFAULT 'PENDING',
        valor_pago FLOAT(5,2) NULL,
        aceite_regulamento BOOLEAN DEFAULT FALSE,
        aceite_responsabilidade BOOLEAN DEFAULT FALSE,
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

ALTER TABLE atleta MODIFY fone VARCHAR(20)
ALTER TABLE evento ADD normal BOOLEAN DEFAULT 0;