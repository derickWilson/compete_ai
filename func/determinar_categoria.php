<?php
function determinarCategoriaPeso($peso, $idade, $genero)
{
    // Normalizar gênero para maiúsculas
    $genero = $genero == "Masculino" ? "M" : "F";

    // Determinar faixa etária
    $faixaEtaria = determinarFaixaEtaria($idade);

    // Determinar categoria baseada no peso e faixa etária
    return determinarCategoriaPorPeso($peso, $faixaEtaria, $genero);
}

function determinarFaixaEtaria($idade)
{
    $faixa_etaria = match (true) {
        $idade >= 4 && $idade <= 5 => "PRE-MIRIM",
        $idade >= 6 && $idade <= 7 => "MIRIM 1",
        $idade >= 8 && $idade <= 9 => "MIRIM 2",
        $idade >= 10 && $idade <= 11 => "INFANTIL 1",
        $idade >= 12 && $idade <= 13 => "INFANTIL 2",
        $idade >= 14 && $idade <= 15 => "INFANTO-JUVENIL",
        $idade >= 16 && $idade <= 17 => "JUVENIL",
        $idade >= 18 && $idade <= 29 => "ADULTO",
        $idade >= 30 => "MASTER",
        default => "OUTROS"
    };

    return $faixa_etaria;
}

function determinarCategoriaPorPeso($peso, $faixaEtaria, $genero)
{
    // Tabela de categorias de peso
    //formato: categoria|faixa etária| limite
    $categorias = [
        'galo' => [
            'PRE-MIRIM' => null,
            'MIRIM 1' => null,
            'MIRIM 2' => 24.000,
            'INFANTIL 1' => 32.200,
            'INFANTIL 2' => 36.200,
            'INFANTO-JUVENIL' => ['F' => 44.300, 'M' => 44.300],
            'JUVENIL' => ['F' => 53.500, 'M' => 48.500],
            'ADULTO' => ['F' => 57.500, 'M' => 57.500],
            'MASTER' => ['F' => 57.500, 'M' => 57.500]
        ],
        'pluma' => [
            'PRE-MIRIM' => 17.900,
            'MIRIM 1' => 21.000,
            'MIRIM 2' => 27.000,
            'INFANTIL 1' => 36.200,
            'INFANTIL 2' => 40.300,
            'INFANTO-JUVENIL' => ['F' => 48.300, 'M' => 48.300],
            'JUVENIL' => ['F' => 58.500, 'M' => 53.500],
            'ADULTO' => ['F' => 64.000, 'M' => 64.000],
            'MASTER' => ['F' => 64.000, 'M' => 64.000]
        ],
        'pena' => [
            'PRE-MIRIM' => 20.000,
            'MIRIM 1' => 24.000,
            'MIRIM 2' => 30.200,
            'INFANTIL 1' => 40.300,
            'INFANTIL 2' => 44.300,
            'INFANTO-JUVENIL' => ['F' => 52.300, 'M' => 52.300],
            'JUVENIL' => ['F' => 64.000, 'M' => 58.500],
            'ADULTO' => ['F' => 70.000, 'M' => 70.000],
            'MASTER' => ['F' => 70.000, 'M' => 70.000]
        ],
        'leve' => [
            'PRE-MIRIM' => 23.000,
            'MIRIM 1' => 27.200,
            'MIRIM 2' => 33.200,
            'INFANTIL 1' => 44.300,
            'INFANTIL 2' => 48.300,
            'INFANTO-JUVENIL' => ['F' => 56.500, 'M' => 56.500],
            'JUVENIL' => ['F' => 69.000, 'M' => 64.000],
            'ADULTO' => ['F' => 76.000, 'M' => 76.000],
            'MASTER' => ['F' => 76.000, 'M' => 76.000]
        ],
        'medio' => [
            'PRE-MIRIM' => 26.000,
            'MIRIM 1' => 30.200,
            'MIRIM 2' => 36.200,
            'INFANTIL 1' => 48.300,
            'INFANTIL 2' => 52.500,
            'INFANTO-JUVENIL' => ['F' => 60.500, 'M' => 60.500],
            'JUVENIL' => ['F' => 74.000, 'M' => 69.000],
            'ADULTO' => ['F' => 82.300, 'M' => 82.300],
            'MASTER' => ['F' => 82.300, 'M' => 82.300]
        ],
        'meio-pesado' => [
            'PRE-MIRIM' => 29.000,
            'MIRIM 1' => 33.200,
            'MIRIM 2' => 39.300,
            'INFANTIL 1' => 52.500,
            'INFANTIL 2' => 56.500,
            'INFANTO-JUVENIL' => ['F' => 65.000, 'M' => 65.000],
            'JUVENIL' => ['F' => 79.300, 'M' => 74.000],
            'ADULTO' => ['F' => 88.300, 'M' => 88.300],
            'MASTER' => ['F' => 88.300, 'M' => 88.300]
        ],
        'pesado' => [
            'PRE-MIRIM' => 32.000,
            'MIRIM 1' => 36.200,
            'MIRIM 2' => 42.300,
            'INFANTIL 1' => 56.500,
            'INFANTIL 2' => 60.500,
            'INFANTO-JUVENIL' => ['F' => 69.000, 'M' => 69.000],
            'JUVENIL' => ['F' => 84.300, 'M' => 79.300],
            'ADULTO' => ['F' => 94.300, 'M' => 94.300],
            'MASTER' => ['F' => 94.300, 'M' => 94.300]
        ],
        'super-pesado' => [
            'PRE-MIRIM' => 35.000,
            'MIRIM 1' => 39.300,
            'MIRIM 2' => 45.300,
            'INFANTIL 1' => 60.500,
            'INFANTIL 2' => 65.000,
            'INFANTO-JUVENIL' => ['F' => 73.000, 'M' => 73.000],
            'JUVENIL' => ['F' => 89.300, 'M' => 84.300],
            'ADULTO' => ['F' => 100.500, 'M' => 100.500],
            'MASTER' => ['F' => 100.500, 'M' => 100.500]
        ],
        'pesadissimo' => [
            'PRE-MIRIM' => 35.001,
            'MIRIM 1' => 39.301,
            'MIRIM 2' => 45.301,
            'INFANTIL 1' => 60.501,
            'INFANTIL 2' => 65.001,
            'INFANTO-JUVENIL' => ['F' => 73.001, 'M' => 77.000],
            'JUVENIL' => ['F' => 94.300, 'M' => 100.500],
            'ADULTO' => ['F' => 116.500, 'M' => 116.500],
            'MASTER' => ['F' => 116.500, 'M' => 116.500]
        ],
        'super-pesadissimo' => [
            'PRE-MIRIM' => null,
            'MIRIM 1' => null,
            'MIRIM 2' => null,
            'INFANTIL 1' => null,
            'INFANTIL 2' => null,
            'INFANTO-JUVENIL' => ['F' => 77.001, 'M' => 94.301],
            'JUVENIL' => ['F' => 94.301, 'M' => 100.501],
            'ADULTO' => ['F' => 116.501, 'M' => 116.501],
            'MASTER' => ['F' => 116.501, 'M' => 116.501]
        ]
    ];

    // Verificar cada categoria
    foreach ($categorias as $categoria => $limites) {
        $limite = $limites[$faixaEtaria] ?? null;

        if ($limite === null) {
            continue; // Categoria não se aplica a esta faixa etária
        }

        // Para faixas etárias que diferenciam por gênero
        if (in_array($faixaEtaria, ['INFANTO-JUVENIL', 'JUVENIL', 'ADULTO', 'MASTER'])) {
            $limiteGenero = $limite[$genero[0]] ?? null; // Pega F ou M

            if ($limiteGenero === null) {
                continue;
            }

            // Verificar se é a última categoria (acima de)
            if (in_array($categoria, ['pesadissimo', 'super-pesadissimo'])) {
                if ($peso >= $limiteGenero) {
                    return $categoria;
                }
            } else {
                if ($peso <= $limiteGenero) {
                    return $categoria;
                }
            }
        } else {
            // Para faixas etárias sem diferenciação por gênero
            if (in_array($categoria, ['pesadissimo', 'super-pesadissimo'])) {
                if ($peso >= $limite) {
                    return $categoria;
                }
            } else {
                if ($peso <= $limite) {
                    return $categoria;
                }
            }
        }
    }

    // Se não encontrou categoria, retorna a mais pesada possível
    return 'super-pesadissimo';
}
?>