<?php
function calcularIdade($dataNascimento) {
    // Cria um objeto DateTime para a data de nascimento
    $nascimento = new DateTime($dataNascimento);
    // Cria um objeto DateTime para a data atual
    $hoje = new DateTime();
    // Calcula a diferença entre os anos
    $idade = $hoje->format('Y') - $nascimento->format('Y');
    // Retorna a idade
    return $idade;
}
?>

