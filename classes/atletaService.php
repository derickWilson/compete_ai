<?php
session_start();
try {
    require_once __DIR__ . "/../func/database.php";  // Caminho absoluto para database.php
    require_once __DIR__ . "/../classes/atletaClass.php";  // Caminho absoluto para atletaClass.php
    include __DIR__ . "/../func/calcularIdade.php";
    
} catch (\Throwable $th) {
    print("[". $th->getMessage() ."]");
}
class atletaService {
    private $conn;
    private $atleta;
    public function __construct(Conexao $conn, Atleta $atleta) {
        $this->conn = $conn->conectar();
        $this->atleta = $atleta;
    }

    //adicionar academia e responsavel
    public function addAcademiaResponsavel($acad) {
        $query = "INSERT INTO atleta (nome, senha, foto, email, data_nascimento, fone, faixa, peso, diploma, validado, responsavel)
                  VALUES (:nome, :senha, :foto, :email, :data_nascimento, :fone, :faixa, :peso, :diploma, 0, 1)";
        $stmt = $this->conn->prepare($query);
        // Bind dos valores
        $senhaCriptografada = password_hash($this->atleta->__get("senha"), PASSWORD_BCRYPT);        
        $stmt->bindValue(":nome", $this->atleta->__get("nome"));
        $stmt->bindValue(":foto", $this->atleta->__get("foto"));
        $stmt->bindValue(":senha", $senhaCriptografada);
        $stmt->bindValue(":email", $this->atleta->__get("email"));
        $stmt->bindValue(":data_nascimento", $this->atleta->__get("data_nascimento"));
        $stmt->bindValue(":fone", $this->atleta->__get("fone"));
        $stmt->bindValue(":faixa", $this->atleta->__get("faixa"));
        $stmt->bindValue(":peso", $this->atleta->__get("peso"));
        $stmt->bindValue(":diploma", $this->atleta->__get("diploma"));
        //vincular uma academia
        //vincular academia ao responsavel
        // Executar a query
        try {
            $stmt->execute();
            $idResponsavel = $this->getResponsavel($this->atleta->__get("email"),$this->atleta->__get("nome"));
            $this->atribuirAcademia($acad, $idResponsavel["id"]);
            //alert 1 :aguarde sua conta ser validada
            header("Location: index.php?alert=1");
        } catch (Exception $e) {
            echo "[ ".$e->getMessage()."]";
        }
    }
    //adicionar atleta
    public function addAtleta() {
        // Verificar a faixa

        //$faixasGraduadas = ["Branca","Cinza","Amarela","Laranja","Verde","Azul","Roxa","Marrom","Preta", "Coral", "Vermelha e Branca","Vermelha"];
        //$valido = in_array($this->atleta->__get("faixa"), $faixasGraduadas) ? 0 : 1;
        $query = "INSERT INTO atleta (nome, senha, foto, email, academia, data_nascimento, fone, faixa, peso, diploma, validado, responsavel)
                VALUES (:nome, :senha, :foto, :email, :academia, :data_nascimento, :fone, :faixa, :peso, :diploma, :validado, :responsavel)";
        $stmt = $this->conn->prepare($query);
        // Bind dos valores
        $senhaCriptografada = password_hash($this->atleta->__get("senha"), PASSWORD_BCRYPT);        
        $stmt->bindValue(":nome", $this->atleta->__get("nome"));
        $stmt->bindValue(":foto", $this->atleta->__get("foto"));
        $stmt->bindValue(":academia", $this->atleta->__get("academia"));
        $stmt->bindValue(":senha", $senhaCriptografada);
        $stmt->bindValue(":email", $this->atleta->__get("email"));
        $stmt->bindValue(":data_nascimento", $this->atleta->__get("data_nascimento"));
        $stmt->bindValue(":fone", $this->atleta->__get("fone"));
        $stmt->bindValue(":faixa", $this->atleta->__get("faixa"));
        $stmt->bindValue(":peso", $this->atleta->__get("peso"));
        $stmt->bindValue(":diploma", $this->atleta->__get("diploma"));
        $stmt->bindValue(":validado", 0);
        $stmt->bindValue(":responsavel", 0);
        // Executar a query
        try {
            $stmt->execute();
            //alert 1 :aguarde sua conta ser validada
            header("Location: index.php?alert=1");
        } catch (Exception $e) {
            echo "[ ".$e->getMessage()."]";
        }
    }
    //listar todos os atletas
    public function listAll() {
        $query = "SELECT a.id, a.nome, a.faixa, f.nome as academia, a.validado 
        FROM atleta AS a 
        JOIN academia_filiada as f ON f.id = a.academia";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function listInvalido() {
        $query = "SELECT a.id, a.nome, a.email, a.data_nascimento,
        a.fone, f.nome as academia, a.faixa, a.peso
        FROM atleta a
        JOIN academia_filiada f ON f.id = a.academia
        WHERE a.validado = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
//logar atleta
public function logar() {
        $query = "SELECT id, nome, senha, foto, academia, email, data_nascimento, fone, faixa, peso, adm, validado, responsavel
                  FROM atleta
                  WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":email", $this->atleta->__get("email"));
        try {
            $stmt->execute(); // Tenta executar a consulta
            $atleta = $stmt->fetch(PDO::FETCH_OBJ);
            if ($atleta) {
                if(!password_verify($this->atleta->__get("senha"), $atleta->senha)){
                    //echo "senha cripto : " . $senhaCriptografada. "<br>";
                    //echo "senha outra : " . $atleta->senha . "<br>";
                    header('Location: login.php?erro=3');
                    exit();
                }
                if($atleta->validado){
                    // Define as variáveis da sessão
                    $_SESSION["logado"] = true;
                    $_SESSION["id"] = $atleta->id;
                    $_SESSION["nome"] = $atleta->nome;
                    $_SESSION["email"] = $atleta->email;
                    $_SESSION["foto"] = $atleta->foto;
                    $_SESSION["idade"] = calcularIdade($atleta->data_nascimento);
                    $_SESSION["data_nascimento"] = $atleta->data_nascimento;
                    $_SESSION["fone"] = $atleta->fone;
                    $_SESSION["academia"] = $this->getAcad($atleta->academia);
                    $_SESSION["faixa"] = $atleta->faixa;
                    $_SESSION["peso"] = $atleta->peso;
                    $_SESSION["admin"] = $atleta->adm == 0 ? 0 : 1;
                    $_SESSION["responsavel"] = $atleta->responsavel == 0 ? 0 : 1;
                    $_SESSION["validado"] = true;
                    header("Location: pagina_pessoal.php");
                    exit();
                } else {
                    //erro dois conta não validada
                    header('Location: index.php?erro=2');
                    exit();
                }
            } else {
                header('Location: login.php?erro=1');
                exit();
            }
        } catch (PDOException $e) {
            // Captura qualquer erro gerado pela execução da consulta
            echo "Erro ao tentar logar: " . $e->getMessage();
        }
    }

    

    //retornar um atleta especifico
    public function getById($id){
        $query = "SELECT a.id, a.nome, a.email, a.data_nascimento, a.foto,
                a.fone, f.nome AS academia, a.faixa, a.peso, a.validado, a.diploma
                FROM atleta a JOIN academia_filiada f ON a.academia = f.id WHERE a.id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":id", $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return $result;
    }
    //edição feita pelo administrador
    public function editAdmin($id, $validado, $faixa){
        $query = "UPDATE atleta SET validado = :validado, faixa = :faixa WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":validado", $validado);
        $stmt->bindValue(":faixa", $faixa);
        $stmt->bindValue(":id", $id);
        $stmt->execute();
        //header("Location: controle?user=".$id."?msg=sucesso");
    }
    //ver se um email existe
    public function emailExists($email) {
        // Query para verificar se o e-mail existe
        $query = "SELECT COUNT(*) as count FROM atleta WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":email", $email);
        $stmt->execute();
        // Obtém o número de registros encontrados
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
    public function listarCampeonatos($id_atleta){
        $query = 'SELECT e.id as idC, e.nome as campeonato, e.local_evento as lugar, e.data_evento as dia,
        i.mod_com as mcom, i.mod_sem as msem, 
        i.mod_ab_com as macom, i.mod_ab_sem as masem
        FROM inscricao i
        JOIN evento e ON e.id = i.id_evento
        WHERE i.id_atleta = :idAtleta';
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":idAtleta", $id_atleta);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $result;
    }

    public function updateAtleta($idAtleta){
        $query = "UPDATE atleta SET email = :email,
         fone = :fone,
         academia = :academia,
         faixa = :faixa,
         peso = :peso,
         diploma = :diploma
         WHERE id = :id";

         $stmt = $this->conn->prepare($query);
         $stmt->bindValue(":email", $this->atleta->__get("email"));
         $stmt->bindValue(":fone", $this->atleta->__get("fone"));
         $stmt->bindValue(":academia", $this->atleta->__get("academia"));
         $stmt->bindValue(":faixa", $this->atleta->__get("faixa"));
         $stmt->bindValue(":peso", $this->atleta->__get("peso"));
         $stmt->bindValue(":diploma", $this->atleta->__get("diploma"));

         try{
            $stmt->execute();
         }catch(Exception $e){
            print("Erro ao adicionar atleta: " . $e->getMessage());
         }
    } 
    //funçoes de afiliação de academia
    public function Filiar($nome, $cep, $cidade, $estado) {
        // Insere a academia e retorna o ID da academia inserida
        $query = "INSERT INTO academia_filiada (nome, cep, cidade, estado) VALUES (:nome, :cep, :cidade, :estado)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":nome", $nome);
        $stmt->bindValue(":cep", $cep);
        $stmt->bindValue(":cidade", $cidade);
        $stmt->bindValue(":estado", $estado);
        $stmt->execute();
    }

    //funçao para consegui id da academia
    public function getIdAcad($nomeAcad){
        $query = "SELECT id FROM academia_filiada WHERE nome = :nome";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue("nome", $nomeAcad);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    //conseguir o id do responsavel
    public function getResponsavel($email, $nome){
        $query = "SELECT id FROM atleta WHERE email = :email AND nome = :nome";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue("nome", $nome);
        $stmt->bindValue("email", $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    //vincular uma academia a um responsavel e viceversa
    public function atribuirAcademia($acad, $professor){
        //vincula academia
        $query = "UPDATE academia_filiada SET responsavel = :responsavel WHERE id = :academia";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue("responsavel", $professor);
        $stmt->bindValue("academia", $acad);
        $stmt->execute();
        //vincular responsavel
        $query = "UPDATE atleta SET academia = :academia WHERE id = :responsavel";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue("responsavel", $professor);
        $stmt->bindValue("academia", $acad);
        $stmt->execute();
    }

    //conseguir o nome da academia
    public function getAcad($id){
        $query = "SELECT nome FROM academia_filiada WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue("id", $id);
        $stmt->execute();
        $acad = $stmt->fetch(PDO::FETCH_ASSOC); 
        return $acad["nome"]; 
    }

    public function getAcademias(){
        $query = "SELECT f.id, f.nome FROM academia_filiada f 
        JOIN atleta a ON f.responsavel = a.id 
        WHERE a.validado = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $lista = $stmt->fetchAll(PDO::FETCH_OBJ);
        return $lista;
    }
}
?>
