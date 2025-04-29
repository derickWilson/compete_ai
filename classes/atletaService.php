<?php
    ob_start(); // INICIA O BUFFER DE SAÍDA
    session_start();
    try {
        require_once __DIR__ . "/../func/database.php";  // Caminho absoluto para database.php
        require_once __DIR__ . "/../classes/atletaClass.php";  // Caminho absoluto para atletaClass.php
        include __DIR__ . "/../func/calcularIdade.php";
    } catch (\Throwable $th) {
        print("[". $th->getMessage() ."]");
    }
    class atletaService {
        private $conn;
        private $atleta;
        // Adicione na classe AtletaService
        public function __construct(Conexao $conn, Atleta $atleta) {
            $this->conn = $conn->conectar();
            $this->atleta = $atleta;
        }

        //adicionar academia e responsavel
        public function addAcademiaResponsavel($acad) {
            $query = "INSERT INTO atleta (nome, cpf, senha, genero, foto, email, data_nascimento, fone, faixa, peso, diploma, validado, responsavel)
                    VALUES (:nome, :cpf, :senha, :genero, :foto, :email, :data_nascimento, :fone, :faixa, :peso, :diploma, 0, 1)";
            $stmt = $this->conn->prepare($query);
            // Bind dos valores
            $senhaCriptografada = password_hash($this->atleta->__get("senha"), PASSWORD_BCRYPT);        
            $stmt->bindValue(":nome", ucwords($this->atleta->__get("nome")));
            $stmt->bindValue(":cpf", $this->atleta->__get("cpf"));
            $stmt->bindValue(":genero", $this->atleta->__get("genero"));
            $stmt->bindValue(":foto", $this->atleta->__get("foto"));
            $stmt->bindValue(":senha", $senhaCriptografada);
            $stmt->bindValue(":email", strtolower($this->atleta->__get("email")));
            $stmt->bindValue(":data_nascimento", $this->atleta->__get("data_nascimento"));
            $stmt->bindValue(":fone", $this->atleta->__get("fone"));
            $stmt->bindValue(":faixa", $this->atleta->__get("faixa"));
            $stmt->bindValue(":peso", $this->atleta->__get("peso"));
            $stmt->bindValue(":diploma", $this->atleta->__get("diploma"));
            //vincular uma academia
            //vincular academia ao responsavel
            // Executar a query
            try {
                $stmt->execute();
                $idResponsavel = $this->getResponsavel($this->atleta->__get("email"),$this->atleta->__get("nome"));
                $this->atribuirAcademia($acad, $idResponsavel["id"]);
                //alert 1 :aguarde sua conta ser validada
                header("Location: index.php?message=1");
            } catch (Exception $e) {
                echo "[ ".$e->getMessage()."]";
            }
        }
        //adicionar atleta
        public function addAtleta() {
            // Verificar a faixa
            $query = "INSERT INTO atleta (nome, cpf, senha, genero, foto, email, academia, data_nascimento, fone, faixa, peso, diploma, validado, responsavel)
                    VALUES (:nome, :cpf, :senha, :genero, :foto, :email, :academia, :data_nascimento, :fone, :faixa, :peso, :diploma, :validado, :responsavel)";
            $stmt = $this->conn->prepare($query);
            // Bind dos valores
            $senhaCriptografada = password_hash($this->atleta->__get("senha"), PASSWORD_BCRYPT);        
            $stmt->bindValue(":nome", ucwords($this->atleta->__get("nome")));
            $stmt->bindValue(":cpf", $this->atleta->__get("cpf"));
            $stmt->bindValue(":genero", $this->atleta->__get("genero"));
            $stmt->bindValue(":foto", $this->atleta->__get("foto"));
            $stmt->bindValue(":academia", $this->atleta->__get("academia"));
            $stmt->bindValue(":senha", $senhaCriptografada);
            $stmt->bindValue(":email", strtolower($this->atleta->__get("email")));
            $stmt->bindValue(":data_nascimento", $this->atleta->__get("data_nascimento"));
            $stmt->bindValue(":fone", $this->atleta->__get("fone"));
            $stmt->bindValue(":faixa", $this->atleta->__get("faixa"));
            $stmt->bindValue(":peso", $this->atleta->__get("peso"));
            $stmt->bindValue(":diploma", $this->atleta->__get("diploma"));
            $stmt->bindValue(":validado", 0);
            $stmt->bindValue(":responsavel", 0);
            // Executar a query
            try {
                $stmt->execute();
                //alert 1 :aguarde sua conta ser validada
                header("Location: index.php?message=1");
            } catch (Exception $e) {
                echo "[ ".$e->getMessage()."]";
            }
        }
        //listar todos os atletas
        public function listAll() {
            $query = "SELECT a.id, a.nome, a.faixa, f.nome as academia, a.validado 
            FROM atleta AS a 
            JOIN academia_filiada as f ON f.id = a.academia";
            $stmt = $this->conn->prepare($query);
            try{
                $stmt->execute();
            }catch(Exception $e){
                echo "erro [".$e->getMessage()."]";
            }
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        }

        public function listInvalido() {
            $query = "SELECT a.id, a.nome, a.email, a.data_nascimento,
            a.fone, f.nome as academia, a.faixa, a.peso
            FROM atleta a
            JOIN academia_filiada f ON f.id = a.academia
            WHERE a.validado = 0";
            $stmt = $this->conn->prepare($query);
            try{
                $stmt->execute();
            }catch(Exception $e){
                echo "erro [".$e->getMessage()."]";
            }
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        }
    //logar atleta
    public function logar() {
            $query = "SELECT id, nome, senha, foto, academia, email, data_nascimento, fone, faixa, peso, adm, validado, responsavel, diploma
                    FROM atleta
                    WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":email", $this->atleta->__get("email"));
            try {
                $stmt->execute(); // Tenta executar a consulta
                $atleta = $stmt->fetch(PDO::FETCH_OBJ);
                if ($atleta) {
                    if(!password_verify($this->atleta->__get("senha"), $atleta->senha)){
                        //echo "senha cripto : " . $senhaCriptografada. "<br>";
                        //echo "senha outra : " . $atleta->senha . "<br>";
                        //erro 3 senha inválida
                        header('Location: login.php?erro=3');
                        exit();
                    }
                    if($atleta->validado){
                        // Define as variáveis da sessão
                        $_SESSION["logado"] = true;
                        $_SESSION["id"] = $atleta->id;
                        $_SESSION["nome"] = $atleta->nome;
                        $_SESSION["email"] = $atleta->email;
                        $_SESSION["foto"] = $atleta->foto;
                        $_SESSION["idade"] = calcularIdade($atleta->data_nascimento);
                        $_SESSION["data_nascimento"] = $atleta->data_nascimento;
                        $_SESSION["fone"] = $atleta->fone;
                        $_SESSION["academia"] = $this->getAcad($atleta->academia);
                        $_SESSION["faixa"] = $atleta->faixa;
                        $_SESSION["peso"] = $atleta->peso;
                        $_SESSION["diploma"] = $atleta->diploma;
                        $_SESSION["admin"] = $atleta->adm == 0 ? 0 : 1;
                        $_SESSION["responsavel"] = $atleta->responsavel == 0 ? 0 : 1;
                        $_SESSION["validado"] = true;
                        header("Location: pagina_pessoal.php");
                        exit();
                    } else {
                        //erro dois conta não validada
                        header('Location: index.php?message=1');
                        exit();
                    }
                } else {
                    header('Location: login.php?erro=1');
                    exit();
                }
            } catch (PDOException $e) {
                // Captura qualquer erro gerado pela execução da consulta
                echo "Erro ao tentar logar: " . $e->getMessage();
            }
        }
        //retornar um atleta especifico
        public function getById($id){
            $query = "SELECT a.id, a.nome, a.email, a.data_nascimento, a.foto, a.academia as acadid,
                    a.fone, f.nome AS academia, a.faixa, a.peso, a.validado, a.diploma, a.responsavel
                    FROM atleta a JOIN academia_filiada f ON a.academia = f.id WHERE a.id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":id", $id);
            try{
                $stmt->execute();
            }catch(Exception $e){
                echo "erro [".$e->getMessage()."]";
            }
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return $result;
        }
        //edição feita pelo administrador
        public function editAdmin($id, $validado, $faixa){
            $query = "UPDATE atleta SET validado = :validado, faixa = :faixa WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":validado", $validado);
            $stmt->bindValue(":faixa", $faixa);
            $stmt->bindValue(":id", $id);
            try{
                $stmt->execute();
            }catch(Exception $e){
                echo "erro [".$e->getMessage()."]";
            }
            //header("Location: controle?user=".$id."?msg=sucesso");
        }
        //ver se um email existe
        public function emailExists($email) {
            // Query para verificar se o e-mail existe
            $query = "SELECT COUNT(*) as count FROM atleta WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":email", $email);
            $stmt->execute();
            // Obtém o número de registros encontrados
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        }
        public function listarCampeonatos($id_atleta){
            $query = 'SELECT e.id as idC, e.nome as campeonato, e.local_evento as lugar, e.data_evento as dia,
            i.mod_com as mcom, i.mod_sem as msem, 
            i.mod_ab_com as macom, i.mod_ab_sem as masem, i.modalidade
            FROM inscricao i
            JOIN evento e ON e.id = i.id_evento
            WHERE i.id_atleta = :idAtleta';
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":idAtleta", $id_atleta);
            try{
                $stmt->execute();
            }catch(Exception $e){
                echo "erro [".$e->getMessage()."]";
            }
            $result = $stmt->fetchAll(PDO::FETCH_OBJ);
            return $result;
        }
        public function updateAtleta(){
            $query = "UPDATE atleta SET email = :email, fone = :fone, foto = :foto, faixa = :faixa, peso = :peso, diploma = :diploma
            WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":email", $this->atleta->__get("email"));
            $stmt->bindValue(":fone", $this->atleta->__get("fone"));
            $stmt->bindValue(":faixa", $this->atleta->__get("faixa"));
            $stmt->bindValue(":foto", $this->atleta->__get("foto"));
            $stmt->bindValue(":peso", $this->atleta->__get("peso"));
            $stmt->bindValue(":diploma", $this->atleta->__get("diploma"));
            $stmt->bindValue(":id", $this->atleta->__get("id"));
            try{
                $stmt->execute();
                $_SESSION["email"] = $this->atleta->__get("email");
                $_SESSION["foto"] = $this->atleta->__get("foto");
                $_SESSION["fone"] = $this->atleta->__get("fone");
                $_SESSION["faixa"] = $this->atleta->__get("faixa");
                $_SESSION["peso"] = $this->atleta->__get("peso");
                $_SESSION["diploma"] = $this->atleta->__get("diploma");
                header("Location: /pagina_pessoal.php");
                exit();
            }catch(Exception $e){
                print("Erro ao editar: " . $e->getMessage());
            }
        }
        //função para pegar inscricao
        public function getInscricao($evento, $atleta){
            $query = "SELECT 
                        e.nome AS nome, e.id, e.preco, e.preco_menor, e.preco_abs, 
                        e.tipo_com, e.tipo_sem, 
                        i.mod_com, i.mod_ab_com, i.mod_sem, i.mod_ab_sem, i.modalidade,
                        i.id_cobranca_asaas
                      FROM inscricao i
                      JOIN evento e ON e.id = i.id_evento
                      JOIN atleta a ON a.id = i.id_atleta
                      WHERE i.id_evento = :evento AND i.id_atleta = :atleta";
                      
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":evento", $evento, PDO::PARAM_INT);
            $stmt->bindValue(":atleta", $atleta, PDO::PARAM_INT);
        
            try {
                $stmt->execute();
                return $stmt->fetch(PDO::FETCH_OBJ);
            } catch(Exception $e) {
                error_log("Erro ao buscar inscrição: " . $e->getMessage());
                return null;
            }
        }        
        //editar inscrição
        public function editarInscricao($evento, $idAtleta, $com, $abCom, $sem, $abSem, $moda){
            $query= "UPDATE inscricao
            SET mod_com = :com, mod_sem = :sem, mod_ab_com = :abCom, mod_ab_sem = :abSem, modalidade = :mod
            WHERE id_evento = :evento AND id_atleta = :idAtleta";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":com", $com);
            $stmt->bindValue(":sem", $sem);
            $stmt->bindValue(":abCom", $abCom);
            $stmt->bindValue(":abSem", $abSem);
            $stmt->bindValue(":mod", $moda);
            $stmt->bindValue(":evento", $evento);
            $stmt->bindValue(":idAtleta", $idAtleta);
            try {
                $stmt->execute();
                header("Location: eventos_cadastrados.php");
                exit();
            } catch (Exception $e) {
                echo "erro ao editar inscricao [ ".$e->getMessage()." ]";
            }
        }

        //Deletar atleta
        public function excluirAtleta($id){
            $dados = $this->getById($id);
            $quary = "DELETE FROM atleta WHERE id = :id";
            $stmt = $this->conn->prepare($quary);
            $stmt->bindValue(":id", $id);
            try {
                $stmt->execute();
                //remover diploma e foto
                unlink("diploma/".$dados->diploma);
                unlink("fotos/".$dados->foto);

                if($dados->responsavel){
                    //deletar todos os atletas
                    $quary="SELECT id FROM atleta WHERE academia = :acad";
                    $stmt = $this->conn->prepare($quary);
                    $stmt->bindValue(":acad", $dados->acadid);
                    try {
                        $stmt->execute();
                        $lista = $stmt->fetchAll(PDO::FETCH_OBJ);
                        // Deletar cada atleta da academia
                        $idsAtletas = array_map(function($ar) { return $ar->id; }, $lista);
                        if(count($idsAtletas) > 0) {
                            $idsStr = implode(",", $idsAtletas);
                            $queryDeleteAtletas = "DELETE FROM atleta WHERE id IN ($idsStr)";
                            $stmtDelete = $this->conn->prepare($queryDeleteAtletas);
                            $stmtDelete->execute();
        
                            // Remover diploma e foto de todos os atletas
                            foreach ($lista as $ar) {
                                $atletaData = $this->getById($ar->id);
                                unlink("diploma/" . $atletaData->diploma);
                                unlink("fotos/" . $atletaData->foto);
                            }
                        }
                    
                        // Deletar a própria academia
                        $query = "DELETE FROM academia_filiada WHERE id = :acad";
                        $stmt = $this->conn->prepare($query);
                        $stmt->bindValue(":acad", $dados->acadid);
                        $stmt->execute();
                    } catch (Exception $e) {
                        echo "erro ao editar inscricao [ ".$e->getMessage()." ]";
                    }
                    
                }
                header("Location: /admin/pessoas.php");
                exit();
            } catch (Exception $e) {
                echo "erro ao excluit [ ".$e->getMessage()." ]";
            }
        }

        //excluir inscricao
        public function excluirInscricao($evento, $atleta){
            $quary = "DELETE FROM inscricao WHERE id_atleta = :idA AND id_evento = :idE";
            $stmt = $this->conn->prepare($quary);
            $stmt->bindValue(":idA", $atleta);
            $stmt->bindValue(":idE", $evento);
            try {
                $stmt->execute();
                header("Location: /eventos_cadastrados.php");
                exit();
            } catch (Exception $e) {
                echo "erro ao excluit inscricao [ ".$e->getMessage()." ]";
            }
        }
        //***************************FUNÇÕES DE ACADEMIA*******************/
        //funçoes de afiliação de academia
        public function Filiar($nome, $cep, $cidade, $estado) {
            // Insere a academia e retorna o ID da academia inserida
            $query = "INSERT INTO academia_filiada (nome, cep, cidade, estado) VALUES (:nome, :cep, :cidade, :estado)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":nome", ucwords($nome));
            $stmt->bindValue(":cep", $cep);
            $stmt->bindValue(":cidade", ucwords($cidade));
            $stmt->bindValue(":estado", $estado);
            try{
                $stmt->execute();
            }catch(Exception $e){
                echo "erro [".$e->getMessage()."]";
            }    
        }
        //ver se academia existe
        public function existAcad($nome){
            $query = "SELECT COUNT(*) as num FROM academia_filiada WHERE nome = :nome";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":nome", $nome);
            try{
                $stmt->execute();
                $resp = $stmt->fetch(PDO::FETCH_OBJ);
                return $resp->num == 0;
            }catch(Exception $e){
                echo "erro [".$e->getMessage()."]";
            }
        }
        //funçao para consegui id da academia
        public function getIdAcad($nomeAcad){
            $query = "SELECT id FROM academia_filiada WHERE nome = :nome";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":nome", $nomeAcad);
            try{
                $stmt->execute();
            }catch(Exception $e){
                echo "erro [".$e->getMessage()."]";
            }
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        //conseguir o id do responsavel
        public function getResponsavel($email, $nome){
            $query = "SELECT id FROM atleta WHERE email = :email AND nome = :nome";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue("nome", $nome);
            $stmt->bindValue("email", $email);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        //vincular uma academia a um responsavel e viceversa
        public function atribuirAcademia($acad, $professor){
            //vincula academia
            $query = "UPDATE academia_filiada SET responsavel = :responsavel WHERE id = :academia";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue("responsavel", $professor);
            $stmt->bindValue("academia", $acad);
            try{
                $stmt->execute();
            }catch(Exception $e){
                echo "erro [".$e->getMessage()."]";
            }
            //vincular responsavel
            $query = "UPDATE atleta SET academia = :academia WHERE id = :responsavel";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue("responsavel", $professor);
            $stmt->bindValue("academia", $acad);
            try{
                $stmt->execute();
            }catch(Exception $e){
                echo "erro de atribuição [".$e->getMessage()."]";
            }
        }

        //conseguir o nome da academia
        public function getAcad($id){
            $query = "SELECT nome FROM academia_filiada WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue("id", $id);
            try{
                $stmt->execute();
            }catch(Exception $e){
                echo "erro [".$e->getMessage()."]";
            }        $acad = $stmt->fetch(PDO::FETCH_ASSOC); 
            return $acad["nome"]; 
        }

        public function getAcademias(){
            $query = "SELECT f.id, f.nome FROM academia_filiada f 
            JOIN atleta a ON f.responsavel = a.id 
            WHERE a.validado = 1";
            $stmt = $this->conn->prepare($query);
            try{
                $stmt->execute();
            }catch(Exception $e){
                echo "erro [".$e->getMessage()."]";
            }
            $lista = $stmt->fetchAll(PDO::FETCH_OBJ);
            return $lista;
        }
        /********API**********/

        public function verificarStatusPagamento($atletaId, $eventoId) {
            $query = "SELECT status_pagamento FROM inscricao 
                      WHERE id_atleta = :atletaId AND id_evento = :eventoId";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':atletaId', $atletaId);
            $stmt->bindValue(':eventoId', $eventoId);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && isset($result['status_pagamento'])) {
                return $result['status_pagamento'] == 'RECEIVED' ? 'PAGO' : 'PENDENTE';
            }
            
            return 'NÃO ENCONTRADO';
        }
    }    
?>