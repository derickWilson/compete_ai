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
            $query = "INSERT INTO galeria (imagem, titulo) VALUES (:imagem, :titulo)";
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

    }
?>