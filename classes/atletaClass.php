<?php
class Atleta {
    private $id;
    private $nome;
    private $senha;
    private $email;
    private $data_nascimento;
    private $fone;
    private $academia;
    private $faixa;
    private $peso;
    private $diploma;
    private $validado;
    private $adm;

    // Getters and setters for all properties
    public function __get($atributo) {
        return $this->$atributo;
    }

    public function __set($atributo, $value) {
        $this->$atributo = $value;
    }
}
?>
