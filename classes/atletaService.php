<?php
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

    //adicionar atleta
    public function addAtleta() {
        // Verificar a faixa
        $faixasGraduadas = ["Preta", "Coral", "Vermelha", "Preta e Vermelha", "Preta e Branca"];
        $valido = in_array($this->atleta->__get("faixa"), $faixasGraduadas) ? 0 : 1;

        $query = "INSERT INTO atleta (nome, senha, email, data_nascimento, fone, academia, faixa, peso, diploma, validado)
                  VALUES (:nome, :senha, :email, :data_nascimento, :fone, :academia, :faixa, :peso, :diploma, :valido)";
        $stmt = $this->conn->prepare($query);

        // Bind dos valores
        $stmt->bindValue(":nome", $this->atleta->__get("nome"));
        $stmt->bindValue(":senha", $this->atleta->__get("senha")); // Criptografar senha
        $stmt->bindValue(":email", $this->atleta->__get("email"));
        $stmt->bindValue(":data_nascimento", $this->atleta->__get("data_nascimento"));
        $stmt->bindValue(":fone", $this->atleta->__get("fone"));
        $stmt->bindValue(":academia", $this->atleta->__get("academia"));
        $stmt->bindValue(":faixa", $this->atleta->__get("faixa"));
        $stmt->bindValue(":peso", $this->atleta->__get("peso"));
        $stmt->bindValue(":valido", $valido);
        $stmt->bindValue(":diploma", $this->atleta->__get("diploma"));

        // Executar a query
        if ($stmt->execute()) {
            $this->logar();
        } else {
            // Se algo deu errado, lançar uma exceção ou retornar um valor indicativo de erro
            throw new Exception("Erro ao adicionar atleta: " . implode(", ", $stmt->errorInfo()));
        }
    }

    //listar todos os atletas
    public function listAll() {
        $query = "SELECT id, nome, faixa, academia, validado FROM atleta";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function listInvalido() {
        $query = "SELECT id, nome, email, data_nascimento,
        fone, academia, faixa, peso FROM atleta
        WHERE validado = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    //logar atleta
    public function logar() {
        $query = "SELECT id, nome, email, data_nascimento, fone, academia, faixa, peso, adm, validado
                  FROM atleta
                  WHERE email = :email AND senha = :senha";
    
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":email", $this->atleta->__get("email"));
        $stmt->bindValue(":senha", $this->atleta->__get("senha"));
        
        $stmt->execute();
    
        $atleta = $stmt->fetch(PDO::FETCH_OBJ);
    
        if ($atleta && $atleta->validado) {
            // Inicia a sessão
            session_start();
            
            // Define as variáveis da sessão
            $_SESSION["logado"] = true;
            $_SESSION["id"] = $atleta->id;
            $_SESSION["nome"] = $atleta->nome;
            $_SESSION["email"] = $atleta->email;
            $_SESSION["idade"] = calcularIdade($atleta->idade);
            $_SESSION["data_nascimento"] = $atleta->data_nascimento;
            $_SESSION["fone"] = $atleta->fone;
            $_SESSION["academia"] = $atleta->academia;
            $_SESSION["faixa"] = $atleta->faixa;
            $_SESSION["peso"] = $atleta->peso;
            $_SESSION["admin"] = $atleta->adm == 0 ? 0 : 1;
            $_SESSION["validado"] = true;
            header("Location: pagina_pessoal.php");
            exit();
        } else {
            if(!$atleta->validado){
                echo 'sua conta ainda não foi validada';
            }
            //to do criar erro 1
            header('Location: login.php?erro=1');
        }
    }

    //retornar um atleta especifico
    public function getById($id){
        $query = "SELECT id, nome, email, data_nascimento,
                fone, academia, faixa, peso, validado, diploma
                FROM atleta
                WHERE id = :id";
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
    
}
?>
