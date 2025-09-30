<?php
// classes/patrocinadorClass.php
try {
    require_once __DIR__ . "/../func/database.php";
} catch (\Throwable $th) {
    print("[" . $th->getMessage() . "]");
}

class Patrocinador {
    private $id;
    private $nome;
    private $imagem;
    private $link;

    public function __get($atributo) {
        return $this->$atributo;
    }

    public function __set($atributo, $value) {
        $this->$atributo = $value;
    }
}

class PatrocinadorService {
    private $conn;
    private $patrocinador;

    public function __construct(Conexao $conn, Patrocinador $patrocinador) {
        $this->conn = $conn->conectar();
        $this->patrocinador = $patrocinador;
    }

    // Adicionar novo patrocinador
    public function addPatrocinador() {
        $query = "INSERT INTO patrocinador (nome, imagem, link) 
                  VALUES (:nome, :imagem, :link)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':nome', $this->patrocinador->__get('nome'));
        $stmt->bindValue(':imagem', $this->patrocinador->__get('imagem'));
        $stmt->bindValue(':link', $this->patrocinador->__get('link'));        
        try {
            $stmt->execute();
            header("Location: /admin/patrocinadores.php?message=1");
        } catch (Exception $e) {
            echo "[ " . $e->getMessage() . "]";
        }
    }

    // Listar todos os patrocinadores
    public function listPatrocinadores() {
        $query = "SELECT id, nome, imagem, link FROM patrocinador ORDER BY nome ASC";
        $stmt = $this->conn->prepare($query);
        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            echo "[ " . $e->getMessage() . " ]";
        }
    }

    // Pegar um patrocinador por ID
    public function getById($id) {
        $query = "SELECT id, nome, imagem, link FROM patrocinador WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":id", $id);
        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            echo "[ " . $e->getMessage() . " ]";
        }
    }

    // Editar patrocinador
    public function editarPatrocinador() {
        $sql = "UPDATE patrocinador 
                SET nome = :nome, imagem = :imagem, link = :link 
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nome', $this->patrocinador->__get("nome"));
        $stmt->bindValue(':imagem', $this->patrocinador->__get("imagem"));
        $stmt->bindValue(':link', $this->patrocinador->__get("link"));
        $stmt->bindValue(':id', $this->patrocinador->__get("id"));
        
        try {
            $stmt->execute();
            header("Location: patrocinadores.php?message=Patrocinador atualizado com sucesso&message_type=success");
            exit();
        } catch (Exception $e) {
            echo "[ " . $e->getMessage() . " ]";
        }
    }

    // Deletar patrocinador
    public function deletePatrocinador($id) {
        // Primeiro, buscar o nome do arquivo para poder excluir do servidor
        $stmt = $this->conn->prepare("SELECT imagem FROM patrocinador WHERE id = :id");
        $stmt->bindValue(":id", $id);
        $stmt->execute();
        $img = $stmt->fetch(PDO::FETCH_OBJ);

        if ($img) {
            // Excluir o arquivo da pasta
            $caminho = __DIR__ . "/../patrocinio/" . $img->imagem;
            if (file_exists($caminho)) {
                unlink($caminho);
            }

            // Agora excluir do banco
            $stmt = $this->conn->prepare("DELETE FROM patrocinador WHERE id = :id");
            $stmt->bindValue(":id", $id);
            return $stmt->execute();
        }
        return false;
    }
}
?>