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

        public function addGaleria(){
            $query = "INSERT INTO galeria";
        }

    }
?>