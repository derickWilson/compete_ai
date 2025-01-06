<?php
function calcularIdade($dataNascimento) {
    // Cria um objeto DateTime para a data de nascimento
    $nascimento = new DateTime($dataNascimento);
    // Cria um objeto DateTime para a data atual
    $hoje = new DateTime();
    // Calcula a diferença entre as datas
    $idade = $hoje->diff($nascimento);
    // Retorna a idade em anos
    return $idade->y;
}
?>