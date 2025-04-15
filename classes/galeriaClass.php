<?php
    class Galeria{
        // Getters and setters for all properties
        private $img;
        private $legenda;
        public function __get($atributo) {
            return $this->$atributo;
        }
    
        public function __set($atributo, $value) {
            $this->$atributo = $value;
        }
    }

    class GaleriaService{
        try {
            require_once __DIR__ . "/../func/database.php";  // Caminho absoluto para database.php            
        } catch (\Throwable $th) {
            print("[". $th->getMessage() ."]");
        }
        private $conn;
        private $galeria;

        public function __constructor(Conexao $conn, Galeria $galeria) {
            $this->$conn = $conn->conectar();
            $this->$galeria = $galeria;
        }

        // adicionar uma foto nova na galeria
        public function addGaleria() {
            $query = "INSERT INTO galeria (imagem, legenda) VALUES (:imagem, :titulo)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':imagem', $this->galeria->__get('img'));
            $stmt->bindValue(':titulo', $this->galeria->__get('legenda'));
            try {
                $stmt->execute();
                //alert 1 :aguarde sua conta ser validada
                header("Location: index.php?message=1");
            } catch (Exception $e) {
                echo "[ ".$e->getMessage()."]";
            }
        }

        //pegar todas as fotos da galeria
        public function listGaleria() {
            $query = "SELECT id, imgagem, legenda FROM galeria";
            $stmt = $this->conn->prepare($query);
            try {
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_OBJ);
            } catch (Exception $e) {
                echo "[ ".$e->getMessage()." ]";
            }
        }

        //deletar da galeria
        public function deleteGaleria($id) {
            // Primeiro, buscar o nome do arquivo para poder excluir do servidor
            $stmt = $this->conn->prepare("SELECT imagem FROM galeria WHERE id = :id");
            $stmt->bindValue(":id", $id);
            $stmt->execute();
            $img = $stmt->fetch(PDO::FETCH_OBJ);
        
            if ($img) {
                // Excluir o arquivo da pasta
                $caminho = __DIR__ . "/../galeria/" . $img->imagem;
                if (file_exists($caminho)) {
                    unlink($caminho);
                }
        
                // Agora excluir do banco
                $stmt = $this->conn->prepare("DELETE FROM galeria WHERE id = :id");
                $stmt->bindValue(":id", $id);
                return $stmt->execute();
            }
            return false;
        }
        
    }
?>