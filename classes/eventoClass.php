<?php
class Evento {
    private $id;
    private $nome;
    private $data_camp;
    private $local_camp;
    private $img;
    private $descricao;
    private $data_limite;
    private $tipoCom;
    private $tipoSem;
    //private $tipoAbCom;
    //private $tipoAbSem;
    private $preco;
    private $preco_menor;
    private $preco_abs;
    private $doc;
    private $normal;
    private $normal_preco;

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