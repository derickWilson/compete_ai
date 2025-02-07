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
    try {
        $query = "INSERT INTO evento (nome, descricao, data_limite, data_evento, local_evento, tipo_com, tipo_sem, imagen, preco)
                  VALUES(:nome, :descricao, :data_limite, :data_camp, :local_comp, :tipoCom, :tipoSem, :img, :preco)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':nome', $this->evento->__get('nome'));
        $stmt->bindValue(':data_camp', $this->evento->__get('data_camp'));
        $stmt->bindValue(':local_comp', $this->evento->__get('local'));
        $stmt->bindValue(':descricao', $this->evento->__get('descricao'));
        $stmt->bindValue(':data_limite', $this->evento->__get('data_limite'));
        $stmt->bindValue(':tipoCom', $this->evento->__get('tipoCom'));
        $stmt->bindValue(':tipoSem', $this->evento->__get('tipoSem'));
        $stmt->bindValue(':preco', $this->evento->__get('preco'));
        $stmt->bindValue(':img', $this->evento->__get('img'));
        $stmt->execute();
    } catch (Exception $e) {
        echo 'Erro ao adicionar evento: ' . $e->getMessage();
    }
}


    public function listAll(){
        //$query = "SELECT id, nome FROM evento AS e WHERE e.data_limite <= CURRENT_DATE()";
        $query = "SELECT id, nome FROM evento";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getById($id){
        //$query = "SELECT id, nome, descricao, data_limite, tipo_com, tipo_sem, preco
        // FROM evento as e 
        // WHERE e.data_limite <= CURRENT_DATE() AND id = :id";
        $query = "SELECT id, nome, descricao, data_evento, local_evento, tipo_com, tipo_sem, preco, imagen 
                    FROM evento as e WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getInscritos($id){
        $query = "SELECT e.nome AS evento, a.nome AS inscrito, a.data_nascimento,
                a.faixa, a.peso, a.academia,
                i.mod_com, i.mod_sem, i.mod_ab_com, i.mod_ab_sem
                FROM evento e
                JOIN inscricao i ON i.id_evento = e.id
                JOIN atleta a ON a.id = i.id_atleta
                WHERE e.id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    
    

    public function inscrever( $id_atleta, $id_evento, $com, $abs_com, $sem, $abs_sem){
        //funçao para inscrever um atleta em um evento
        $query = 'INSERT INTO inscricao (id_atleta, id_evento,mod_com,mod_sem,mod_ab_com,mod_ab_sem)
                    VALUES (:atleta, :evento, :com, :sem, :abs_com, :abs_sem)';
        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(':atleta', $id_atleta);
        $stmt->bindValue(':evento', $id_evento);
        $stmt->bindValue(':com', $com);
        $stmt->bindValue(':sem', $abs_com);
        $stmt->bindValue(':abs_com', $sem);
        $stmt->bindValue(':abs_sem', $abs_sem);

        $stmt->execute();
    }

    public function isInscrito($idAtleta, $idEvento){
        $query = "SELECT COUNT(*) as numero FROM inscricao i WHERE i.id_atleta = :idAtlera AND i.id_evento = :idEvento";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindValue(':idAtlera', $idAtleta);
        $stmt->bindValue(':idEvento', $idEvento);

        $stmt->execute();
        $num = $stmt->fetch(PDO::FETCH_OBJ);

        return $num->numero == 0;
    }

    public function montarChapa($id,$cor,$infantil,$infantojuvenil, $masters,$pPesado,$medio){
        $todos = $this->getInscritos($id);
        
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
	$faixaPeso = ["leve","medio","pesado"];
	$listaPeso = [
		"leve" => array(),
		"medio" => array(),
		"pesado" => array()
	];
	foreach($listaIdade as $key => $value){
		$faixaEtaria = $listaIdade[$key];
		//faixa pega somente uma faixa etaria
		//agora percorrer cada um e dividir por peso
		if($inscrito->peso < $medio){
			array_push($listaPeso["leve"],$inscrito);
		}
		if($inscrito->peso >= $medio && $incrito->peso < $pPesado){
			array_push($listaPeso["medio"],$inscrito);
		}
		if($inscrito->peso >= $pPesado){
			array_push($listaPeso["pesado"],$inscrito);
		}
	}

    }
} 
?>
