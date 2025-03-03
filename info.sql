CREATE DATABASE IF NOT EXISTS usuario;
    USE usuario;
    CREATE TABLE IF NOT EXISTS atleta(
        id INT NOT NULL AUTO_INCREMENT,
        nome VARCHAR(50) NOT NULL,
        senha VARCHAR(255) NOT NULL,
        email VARCHAR (100),
        data_nascimento DATE NOT NULL,
        fone VARCHAR(12) NOT NULL,
        academia VARCHAR(255) NOT NULL,
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
    data_evento DATE,
    local_evento VARCHAR(100),
    tipo_com TINYINT,
    tipo_sem TINYINT,
    imagen VARCHAR(30),
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
