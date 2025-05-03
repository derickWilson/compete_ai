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
            data_evento = :data_evento,
            local_evento = :local_evento,
            tipo_com = :tipoCom,
            tipo_sem = :tipoSem,
            preco = :preco,
            preco_abs = :preco_abs,
            preco_menor = :preco_menor,
            doc = :doc
            WHERE id = :id";
    
        $stmt = $this->conn->prepare($query);
        
        // Mapeamento correto dos campos
        $stmt->bindValue(':id', $this->evento->__get('id'), PDO::PARAM_INT);
        $stmt->bindValue(':nome', $this->evento->__get('nome'), PDO::PARAM_STR);
        $stmt->bindValue(':imagen', $this->evento->__get('img'), PDO::PARAM_STR);
        $stmt->bindValue(':data_evento', $this->evento->__get('data_camp'), PDO::PARAM_STR);
        $stmt->bindValue(':local_evento', $this->evento->__get('local_camp'), PDO::PARAM_STR);
        $stmt->bindValue(':descricao', $this->evento->__get('descricao'), PDO::PARAM_STR);
        $stmt->bindValue(':data_limite', $this->evento->__get('data_limite'), PDO::PARAM_STR);
        $stmt->bindValue(':tipoCom', $this->evento->__get('tipoCom'), PDO::PARAM_INT);
        $stmt->bindValue(':tipoSem', $this->evento->__get('tipoSem'), PDO::PARAM_INT);
        $stmt->bindValue(':preco', $this->evento->__get('preco'));
        $stmt->bindValue(':preco_menor', $this->evento->__get('preco_menor'));
        $stmt->bindValue(':preco_abs', $this->evento->__get('preco_abs'));
        $stmt->bindValue(':doc', $this->evento->__get('doc'), PDO::PARAM_STR);
    
        try {
            $result = $stmt->execute();
            return $result; // Retorna true/false para o chamador decidir o redirecionamento
        } catch (Exception $e) {
            error_log('Erro ao editar evento: ' . $e->getMessage());
            throw new Exception("Erro ao atualizar o evento no banco de dados");
        }
}

    //listar todos os eventos
    public function listAll(){
        //$query = "SELECT id, nome FROM evento WHERE data_evento >= CURRENT_DATE";
        $query = "SELECT id, nome, imagen FROM evento WHERE data_limite >= CURRENT_DATE";
        $stmt = $this->conn->prepare($query);
        try {
            $stmt->execute();
        } catch (Exception $e) {
            echo 'Erro ao listar eventos : ' . $e->getMessage();
        }
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getById($id) {
        $query = "SELECT 
                    id, 
                    nome, 
                    descricao, 
                    data_evento, 
                    data_limite, 
                    local_evento, 
                    tipo_com, 
                    tipo_sem, 
                    preco, 
                    preco_menor, 
                    preco_abs, 
                    imagen, 
                    doc
                  FROM evento 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        try {
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            
            // Garantir que todas as propriedades necessárias existam
            $propriedadesNecessarias = [
                'id', 'nome', 'descricao', 'data_evento', 'data_limite',
                'local_evento', 'tipo_com', 'tipo_sem', 'preco',
                'preco_menor', 'preco_abs', 'imagen', 'doc'
            ];
            
            foreach ($propriedadesNecessarias as $prop) {
                if (!property_exists($result, $prop)) {
                    $result->$prop = null;
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('Erro ao buscar evento por ID: ' . $e->getMessage());
            throw new Exception("Erro ao buscar informações do evento");
        }
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
    public function inscrever($id_atleta, $id_evento, $mod_com, $mod_abs_com, $mod_sem, $mod_abs_sem, $modalidade, $aceite_regulamento, $aceite_responsabilidade) {
        try {
            $query = "INSERT INTO inscricao (
                        id_atleta, 
                        id_evento, 
                        mod_com, 
                        mod_sem, 
                        mod_ab_com, 
                        mod_ab_sem, 
                        modalidade,
                        aceite_regulamento,
                        aceite_responsabilidade
                      ) VALUES (
                        :id_atleta, 
                        :id_evento, 
                        :mod_com, 
                        :mod_sem, 
                        :mod_ab_com, 
                        :mod_ab_sem, 
                        :modalidade,
                        :aceite_regulamento,
                        :aceite_responsabilidade
                      )";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id_atleta', $id_atleta, PDO::PARAM_INT);
            $stmt->bindValue(':id_evento', $id_evento, PDO::PARAM_INT);
            $stmt->bindValue(':mod_com', $mod_com, PDO::PARAM_INT);
            $stmt->bindValue(':mod_sem', $mod_sem, PDO::PARAM_INT);
            $stmt->bindValue(':mod_ab_com', $mod_abs_com, PDO::PARAM_INT);
            $stmt->bindValue(':mod_ab_sem', $mod_abs_sem, PDO::PARAM_INT);
            $stmt->bindValue(':modalidade', $modalidade, PDO::PARAM_STR);
            $stmt->bindValue(':aceite_regulamento', $aceite_regulamento, PDO::PARAM_BOOL);
            $stmt->bindValue(':aceite_responsabilidade', $aceite_responsabilidade, PDO::PARAM_BOOL);
            
            $result = $stmt->execute();
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Erro ao inscrever atleta: " . $e->getMessage());
            return false;
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
    //montar chapa, não funciona
    public function montarChapa($id,$infantil,$infantojuvenil, $masters,$pPesado,$medio){
        $todos = $this->getInscritos($id);
        $faixas = ["Branca", "Azul","Roxa","Preta", "Coral", "Vermelha", "Preta e Vermelha", "Preta e Branca"];

        foreach($faixas as $cor){
            $listaIdade = [
                "infantil"=>array(),
                "juvenil"=>array(),
                "adulto"=>array(),
                "master"=>array()
            ];
            foreach($todos as $inscrito){
                //loop para separar por idade
                if($inscrito->faixa == $cor){
                    $idade = calcularIdade($inscrito->data_nascimento);
                    switch($idade){
                        case $idade < $infantil:
                            array_push($listaIdade["infantil"], $inscrito);
                            break;
                
                        case $idade >= $infantil && $idade < $infantojuvenil:
                            array_push($listaIdade["juvenil"], $inscrito);
                            break;
                        case $idade >= $infantojuvenil && $idade < $masters:
                            array_push($listaIdade["adulto"], $inscrito);
                            break;
                        case $idade >= $masters:
                            array_push($listaIdade["master"], $inscrito);
                            break;
                    }
                }
            }
        //separar por peso	
            $listaPeso = [
                "leve" => array(),
                "medio" => array(),
                "pesado" => array()
            ];
            foreach($listaIdade as $key => $value){
                $faixaEtaria = $listaIdade[$key];
                //faixa pega somente uma faixa etaria
                //agora percorrer cada um e dividir por peso
                echo "<h2>classificação : ".$key."<br></h2>";
                foreach($value as $inscrito){
                    // separar por peso
                    if($inscrito->peso < $medio){
                        array_push($listaPeso["leve"],$inscrito);
                    }
                    if($inscrito->peso >= $medio && $inscrito->peso < $pPesado){
                        array_push($listaPeso["medio"],$inscrito);
                    }
                    if($inscrito->peso >= $pPesado){
                        array_push($listaPeso["pesado"],$inscrito);
                    }
                }
                //criar as chapas com os lista peso aqui
                echo "<h2>faixa " . $cor . "</h2>";
                foreach($listaPeso as $key => $inscrito){
                    echo "<h3>peso ".$key."</h3><br>";
                    shuffle($inscrito);
                
                    for($i = 0; $i < count($inscrito); $i++){
                        echo "<ul>";
                        echo "<li>".$inscrito->nome."</li>";
                        if(($i+1) % 2 == 0){
                            echo "<br>";
                        }
                    
                        echo "</ul>";
                    }
                }
            }   
        }
    }
} 
?>