<?php
class Evento {
    private $id;
    private $nome;
    private $local;
    private $data_camp;
    private $img;
    private $descricao;
    private $data_limite;
    private $tipoCom;
    private $tipoSem;
    private $preco;
    private $preco_menor;

    // Método mágico __get para getters
    public function __get($atributo) {
            return $this->$atributo;
    }

    // Método mágico __set para setters
    public function __set($atributo, $valor) {
        $this->$atributo = $valor;
    }
}
?>
