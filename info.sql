CREATE DATABASE IF NOT EXISTS usuario;
    USE usuario;
    CREATE TABLE IF NOT EXISTS atleta(
        id INT NOT NULL AUTO_INCREMENT,
        nome VARCHAR(50),
        senha VARCHAR(255),
        email VARCHAR (100),
        data_nascimento DATE,
        fone VARCHAR(12),
        academia VARCHAR(255),
        faixa VARCHAR(30),
        peso FLOAT(5,2),
        validado TINYINT,
        adm TINYINT default 0,
        diploma VARCHAR(30),
        PRIMARY KEY (id)
    );

#--tabela dos eventos
CREATE TABLE IF NOT EXISTS evento(
    id INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    data_limite DATE,
    tipo_com TINYINT,
    tipo_sem TINYINT,
    imagen VARCHAR(50),
    preco FLOAT(5,2),
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
        PRIMARY KEY (id_atleta, id_evento),
        FOREIGN KEY (id_atleta) REFERENCES atleta(id),
        FOREIGN KEY (id_evento) REFERENCES evento(id)
    );

#--exemplos

INSERT INTO atleta (nome, senha, email, data_nascimento, fone, academia, faixa, peso, validado, adm)
VALUES 
('João Silva', 'senha123', 'joao.silva@example.com', '1998-01-15', '11987654321', 'Academia A', 'Preta', 75.50, 1, 0),
('Maria Oliveira', 'senha456', 'maria.oliveira@example.com', '1993-03-22', '11987654322', 'Academia B', 'Coral', 65.00, 1, 0),
('Pedro Santos', 'senha789', 'pedro.santos@example.com', '1995-05-30', '11987654323', 'Academia C', 'Vermelha', 80.00, 1, 1),
('Ana Costa', 'senha101', 'ana.costa@example.com', '2001-07-14', '11987654324', 'Academia A', 'Preta e Vermelha', 55.00, 1, 0),
('Carlos Almeida', 'senha202', 'carlos.almeida@example.com', '1988-09-09', '11987654325', 'Academia B', 'Preta e Branca', 85.00, 1, 0),
('Beatriz Lima', 'senha303', 'beatriz.lima@example.com', '1997-11-18', '11987654326', 'Academia C', 'Coral', 60.00, 1, 0),
('Rafael Souza', 'senha404', 'rafael.souza@example.com', '1992-02-20', '11987654327', 'Academia A', 'Vermelha', 70.00, 0, 0),
('Juliana Pereira', 'senha505', 'juliana.pereira@example.com', '1996-04-08', '11987654328', 'Academia B', 'Preta', 62.50, 1, 0),
('Lucas Fernandes', 'senha606', 'lucas.fernandes@example.com', '1994-06-12', '11987654329', 'Academia C', 'Preta e Branca', 78.00, 1, 0),
('Laura Martins', 'senha707', 'laura.martins@example.com', '1999-08-25', '11987654330', 'Academia A', 'Coral', 57.00, 0, 0);

INSERT INTO evento (nome, descricao, data_limite, tipo_com, tipo_sem, preco)
VALUES 
('Torneio de Verão', 'Competição anual de verão', '2024-06-30', 1, 0, 50.00),
('Campeonato de Inverno', 'Campeonato de inverno com várias categorias', '2024-12-15', 0, 1, 60.00),
('Desafio de Outono', 'Desafio de outono com atletas de todos os níveis', '2024-10-05', 1, 1, 40.00),
('Festival de Primavera', 'Festival de primavera com eventos diversos', '2024-04-20', 1, 0, 45.00),
('Copa do Brasil', 'Competição nacional com os melhores atletas', '2024-09-01', 1, 1, 70.00),
('Open Internacional', 'Evento internacional aberto a todos', '2024-11-11', 1, 1, 80.00),
('Maratona de Verão', 'Maratona de verão com várias distâncias', '2024-07-20', 0, 1, 55.00),
('Torneio de Aniversário', 'Torneio especial para comemorar o aniversário da academia', '2024-08-25', 1, 0, 30.00),
('Desafio de Inverno', 'Desafio para atletas em condições extremas', '2024-12-01', 0, 1, 65.00),
('Campeonato Estadual', 'Campeonato para atletas do estado', '2024-05-15', 1, 1, 50.00);
