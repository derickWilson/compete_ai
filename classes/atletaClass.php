<?php
class Atleta {
    private $nome;
    private $cpf;
    private $id;
    private $foto;
    private $genero;
    private $senha;
    private $email;
    private $data_nascimento;
    private $fone;
    private $academia;
    private $faixa;
    private $peso;
    private $diploma;
    private $validado;
    private $responsavel;
    private $adm;
    private $permissao_email;

    // Getters and setters for all properties
    public function __get($atributo) {
        return $this->$atributo;
    }

    public function __set($atributo, $value) {
        $this->$atributo = $value;
    }
}
?>