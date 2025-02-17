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
}

?>
