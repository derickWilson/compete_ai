<?php
class Conexao {
    private $usuario = "root";
    private $dbname = "usuario";
    private $password = "";
    private $host = "localhost";
    public function conectar() {
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->dbname;
            $conexao = new PDO($dsn, $this->usuario, $this->password);
            return $conexao;
        } catch (PDOException $e) {
            echo "Erro de conexão: " . $e->getMessage();
            return null;
        }
    }

        // Método para executar uma consulta simples
        public function query($query) {
            $conn = $this->conectar();
            if ($conn) {
                return $conn->query($query);  // Executa a query e retorna o resultado
            }
            return null;
        }
    
        // Método para preparar uma consulta (usado para consultas com parâmetros)
        public function prepare($query) {
            $conn = $this->conectar();
            if ($conn) {
                return $conn->prepare($query);  // Retorna o PDOStatement
            }
            return null;
        }
    
        // Método para executar uma consulta preparada (com parâmetros)
        public function execute($query, $params) {
            $conn = $this->conectar();
            if ($conn) {
                $stmt = $conn->prepare($query);
                return $stmt->execute($params);
            }
            return false;
        }
}
?>
