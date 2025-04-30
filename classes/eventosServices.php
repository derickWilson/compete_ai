<?php
require_once __DIR__ . "/../func/database.php";
require_once __DIR__ . "/../classes/eventoClass.php";
require_once __DIR__ . "/../func/calcularIdade.php";

class eventosService{
    private $conn;
    private $evento;

    public function __construct(Conexao $conn, Evento $evento){
        $this->conn = $conn->conectar();
        $this->evento = $evento;
    }

//adicionar um evento novo
public function addEvento() {
    $query = "INSERT INTO evento (nome, descricao, data_limite, data_evento, local_evento, tipo_com, tipo_sem, imagen, preco, preco_menor, preco_abs, doc)
    VALUES(:nome, :descricao, :data_limite, :data_camp, :local_comp, :tipoCom, :tipoSem, :img, :preco, :preco_menor, :preco_abs, :doc)";
    $stmt = $this->conn->prepare($query);
    $stmt->bindValue(':nome', $this->evento->__get('nome'));
    $stmt->bindValue(':data_camp', $this->evento->__get('data_camp'));
    $stmt->bindValue(':local_comp', $this->evento->__get('local'));
    $stmt->bindValue(':descricao', $this->evento->__get('descricao'));
    $stmt->bindValue(':data_limite', $this->evento->__get('data_limite'));
    $stmt->bindValue(':tipoCom', $this->evento->__get('tipoCom'));
    $stmt->bindValue(':tipoSem', $this->evento->__get('tipoSem'));
    $stmt->bindValue(':preco', $this->evento->__get('preco'));
    $stmt->bindValue(':preco_menor', $this->evento->__get('preco_menor'));
    $stmt->bindValue(':preco_abs', $this->evento->__get('preco_abs'));
    $stmt->bindValue(':img', $this->evento->__get('img'));
    $stmt->bindValue(':doc', $this->evento->__get('doc'));
    try {
        $stmt->execute();
    } catch (Exception $e) {
        echo 'Erro ao adicionar evento: ' . $e->getMessage();
    }
}
    //editar evento
    public function editEvento() {
        $query = "UPDATE evento SET
            nome = :nome,
            imagen = :imagen,
            descricao = :descricao,
            data_limite = :data_limite,
            data_evento = :data_camp,
            local_evento = :local_evento,
            tipo_com = :tipoCom,
            tipo_sem = :tipoSem,
            preco = :preco,
            preco_abs = :preco_abs,
            preco_menor = :preco_menor,
            doc = :doc
            WHERE id = :id";
    
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $this->evento->__get('id'));
        $stmt->bindValue(':nome', $this->evento->__get('nome'));
        $stmt->bindValue(':imagen', $this->evento->__get('img'));
        $stmt->bindValue(':data_camp', $this->evento->__get('data_camp'));
        $stmt->bindValue(':local_evento', $this->evento->__get('local_camp'));
        $stmt->bindValue(':descricao', $this->evento->__get('descricao'));
        $stmt->bindValue(':data_limite', $this->evento->__get('data_limite'));
        $stmt->bindValue(':tipoCom', $this->evento->__get('tipoCom'));
        $stmt->bindValue(':tipoSem', $this->evento->__get('tipoSem'));
        $stmt->bindValue(':preco', $this->evento->__get('preco'));
        $stmt->bindValue(':preco_menor', $this->evento->__get('preco_menor'));
        $stmt->bindValue(':preco_abs', $this->evento->__get('preco_abs'));
        $stmt->bindValue(':doc', $this->evento->__get('doc'));
    
        try {
            $stmt->execute();
            header("Location: /eventos.php?id=" . $this->evento->__get('id'));
            exit();
        } catch (Exception $e) {
            echo 'Erro ao editar evento: ' . $e->getMessage();
        }
    }
    
    //listar todos os eventos
    public function listAll(){
        //$query = "SELECT id, nome FROM evento WHERE data_evento >= CURRENT_DATE";
        $query = "SELECT id, nome, imagen FROM evento";
        $stmt = $this->conn->prepare($query);
        try {
            $stmt->execute();
        } catch (Exception $e) {
            echo 'Erro ao listar eventos : ' . $e->getMessage();
        }
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getById($id){
        //$query = "SELECT id, nome, descricao, data_limite, tipo_com, tipo_sem, preco
        // FROM evento as e 
        // WHERE e.data_limite <= CURRENT_DATE() AND id = :id";
        $query = "SELECT id, nome, descricao, data_evento, data_limite, local_evento, tipo_com, tipo_sem, preco, imagen, preco_menor, preco_abs, doc
                    FROM evento as e WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id);
        try {
            $stmt->execute();
        } catch (Exception $e) {
            echo 'Erro ao listar evento: ' . $e->getMessage();
        }
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
    //pegar todos os inscritos de um evento
    public function getInscritos($id){
        $query = "SELECT e.nome AS evento, e.id AS ide, a.nome AS inscrito, a.data_nascimento, a.id,
                a.faixa, a.peso, f.nome as academia, f.id as idAcademia,
                i.mod_com, i.mod_sem, i.mod_ab_com, i.mod_ab_sem, i.modalidade as modalidade,
                i.status_pagamento, i.id_cobranca_asaas, i.valor_pago
                FROM evento e
                JOIN inscricao i ON i.id_evento = e.id
                JOIN atleta a ON a.id = i.id_atleta
                JOIN academia_filiada f ON a.academia = f.id
                WHERE e.id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        try {
            $stmt->execute();
        } catch (Exception $e) {
            error_log('Erro ao listar inscritos no evento: ' . $e->getMessage());
            throw new Exception("Erro ao carregar lista de inscritos");
        }        
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }   
    //inscrever um atleta em um evento
    public function inscrever($id_atleta, $id_evento, $com, $abs_com, $sem, $abs_sem, $modalidade_escolhida, $id_cobranca = null, $status_pagamento = 'PENDING', $valor_pago = null) { 
        $query = 'INSERT INTO inscricao (
                    id_atleta, id_evento, mod_com, mod_sem, mod_ab_com, mod_ab_sem, 
                    modalidade, id_cobranca_asaas, status_pagamento, valor_pago
                  ) VALUES (
                    :atleta, :evento, :com, :sem, :abs_com, :abs_sem, 
                    :modalidade, :cobranca_id, :status_pagamento, :valor_pago
                  )';
        
        $stmt = $this->conn->prepare($query);
    
        $stmt->bindValue(':atleta', $id_atleta);
        $stmt->bindValue(':evento', $id_evento);
        $stmt->bindValue(':com', $com);
        $stmt->bindValue(':sem', $sem);
        $stmt->bindValue(':abs_com', $abs_com);
        $stmt->bindValue(':abs_sem', $abs_sem);
        $stmt->bindValue(':modalidade', $modalidade_escolhida);
        $stmt->bindValue(':cobranca_id', $id_cobranca);
        $stmt->bindValue(':status_pagamento', $status_pagamento);
        $stmt->bindValue(':valor_pago', $valor_pago);
        try {
            $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Erro ao realizar inscrição: " . $e->getMessage());
        }
    }
    //ver se um atleta ja esta inscrito em um evento
    public function isInscrito($idAtleta, $idEvento){
        $query = "SELECT COUNT(*) as numero FROM inscricao i WHERE i.id_atleta = :idAtlera AND i.id_evento = :idEvento";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindValue(':idAtlera', $idAtleta);
        $stmt->bindValue(':idEvento', $idEvento);

        try {
            $stmt->execute();
        } catch (Exception $e) {
            echo 'Erro ao listar atleta: ' . $e->getMessage();
        }
        $num = $stmt->fetch(PDO::FETCH_OBJ);
        return $num->numero == 0;
    }
    //montar chapa
    public function montarChapas($id, $modalidade, $faixa, $idadeMin, $idadeMax) {
        $query = "SELECT a.nome, f.nome as academia, 
                         (YEAR(CURDATE()) - YEAR(a.data_nascimento)) AS idade, 
                         a.faixa
                  FROM inscricao i
                  JOIN atleta a ON i.id_atleta = a.id
                  JOIN academia_filiada f ON a.academia = f.id 
                  WHERE i.id_evento = :evento 
                    AND i.modalidade = :modalidade 
                    AND a.faixa = :faixa 
                    AND (YEAR(CURDATE()) - YEAR(a.data_nascimento)) BETWEEN :idadeMinima AND :idadeMaxima";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":evento", $id);
        $stmt->bindParam(":modalidade", $modalidade);
        $stmt->bindParam(":faixa", $faixa);
        $stmt->bindParam(":idadeMinima", $idadeMin);
        $stmt->bindParam(":idadeMaxima", $idadeMax);

        try {
            $stmt->execute();
            $chapa = $stmt->fetchAll(PDO::FETCH_OBJ);
            shuffle($chapa); // Embaralhar os atletas
            return $chapa;
        } catch (Exception $e) {
            error_log("Erro ao buscar chapa: " . $e->getMessage());
        }
    }
} 
?>