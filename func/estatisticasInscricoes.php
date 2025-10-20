<?php
/**
 * Função para obter estatísticas de inscrições para um evento
 * @param object $evserv Serviço de eventos
 * @param int $eventoId ID do evento
 * @param int $idadeUsuario Idade do usuário
 * @param string $faixaUsuario Faixa do usuário
 * @param string $categoriaAuto Categoria automática do usuário
 * @param string $faixaEtaria Faixa etária do usuário
 * @return array Estatísticas organizadas
 */
function obterEstatisticasInscricoes($evserv, $eventoId, $idadeUsuario, $faixaUsuario, $categoriaAuto, $faixaEtaria) {
    $estatisticas = [
        'com_kimono' => null,
        'sem_kimono' => null,
        'info_usuario' => [
            'faixa' => $faixaUsuario,
            'peso' => $_SESSION["peso"] ?? 'N/A',
            'categoria' => $categoriaAuto,
            'faixa_etaria' => $faixaEtaria
        ]
    ];

    // Obter detalhes do evento
    try {
        $eventoDetails = $evserv->getById($eventoId);
        
        if (!$eventoDetails) {
            throw new Exception("Evento não encontrado");
        }

        // Estatísticas para COM Kimono
        if ($eventoDetails->tipo_com) {
            $estatisticas['com_kimono'] = [
                'categoria' => [
                    'pendentes' => $evserv->contagemCategoria($eventoId, $idadeUsuario, false, false, 'com', $faixaUsuario),
                    'confirmados' => $evserv->contagemCategoria($eventoId, $idadeUsuario, false, true, 'com', $faixaUsuario)
                ],
                'absoluto' => [
                    'pendentes' => $evserv->contagemCategoria($eventoId, $idadeUsuario, true, false, 'com', $faixaUsuario),
                    'confirmados' => $evserv->contagemCategoria($eventoId, $idadeUsuario, true, true, 'com', $faixaUsuario)
                ]
            ];
        }

        // Estatísticas para SEM Kimono
        if ($eventoDetails->tipo_sem) {
            $estatisticas['sem_kimono'] = [
                'categoria' => [
                    'pendentes' => $evserv->contagemCategoria($eventoId, $idadeUsuario, false, false, 'sem', $faixaUsuario),
                    'confirmados' => $evserv->contagemCategoria($eventoId, $idadeUsuario, false, true, 'sem', $faixaUsuario)
                ],
                'absoluto' => [
                    'pendentes' => $evserv->contagemCategoria($eventoId, $idadeUsuario, true, false, 'sem', $faixaUsuario),
                    'confirmados' => $evserv->contagemCategoria($eventoId, $idadeUsuario, true, true, 'sem', $faixaUsuario)
                ]
            ];
        }

        return $estatisticas;

    } catch (Exception $e) {
        error_log("Erro ao obter estatísticas: " . $e->getMessage());
        return [
            'com_kimono' => null,
            'sem_kimono' => null,
            'info_usuario' => $estatisticas['info_usuario'],
            'erro' => $e->getMessage()
        ];
    }
}

/**
 * Função para renderizar as estatísticas em HTML
 * @param array $estatisticas Estatísticas retornadas por obterEstatisticasInscricoes
 * @return string HTML das estatísticas
 */
function renderizarEstatisticas($estatisticas) {
    $html = '';

    // Informações do usuário
    $html .= '
    <div class="info-usuario">
        <p>Sua Faixa: ' . htmlspecialchars($estatisticas['info_usuario']['faixa']) . '</p>
        <p>Seu Peso: ' . htmlspecialchars($estatisticas['info_usuario']['peso']) . 'Kg</p>
        <p>Sua Categoria: ' . htmlspecialchars($estatisticas['info_usuario']['categoria']) . '</p>
        <p>Sua Faixa Etária: ' . htmlspecialchars($estatisticas['info_usuario']['faixa_etaria']) . '</p>
    </div>';

    $html .= '<p class="aviso-info"><strong>⚠️ Atenção:</strong> Os números abaixo estão sujeitos a alterações constantes</p>';

    // Container principal
    $html .= '<div class="estatisticas-container">';

    // COM Kimono
    if ($estatisticas['com_kimono']) {
        $html .= '
        <div class="modalidade-container">
            <h4>🥋 COM Kimono</h4>
            <div class="tabelas-wrapper">
                <div class="tabela-container">
                    <table>
                        <caption>' . htmlspecialchars($estatisticas['info_usuario']['categoria']) . '</caption>
                        <tr><th>Pendentes</th><th>Confirmados</th></tr>
                        <tr>
                            <td>' . htmlspecialchars($estatisticas['com_kimono']['categoria']['pendentes']) . '</td>
                            <td>' . htmlspecialchars($estatisticas['com_kimono']['categoria']['confirmados']) . '</td>
                        </tr>
                    </table>
                </div>
                <div class="tabela-container">
                    <table>
                        <caption>Absoluto</caption>
                        <tr><th>Pendentes</th><th>Confirmados</th></tr>
                        <tr>
                            <td>' . htmlspecialchars($estatisticas['com_kimono']['absoluto']['pendentes']) . '</td>
                            <td>' . htmlspecialchars($estatisticas['com_kimono']['absoluto']['confirmados']) . '</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>';
    }

    // SEM Kimono
    if ($estatisticas['sem_kimono']) {
        $html .= '
        <div class="modalidade-container">
            <h4>👊 SEM Kimono</h4>
            <div class="tabelas-wrapper">
                <div class="tabela-container">
                    <table>
                        <caption>' . htmlspecialchars($estatisticas['info_usuario']['categoria']) . '</caption>
                        <tr><th>Pendentes</th><th>Confirmados</th></tr>
                        <tr>
                            <td>' . htmlspecialchars($estatisticas['sem_kimono']['categoria']['pendentes']) . '</td>
                            <td>' . htmlspecialchars($estatisticas['sem_kimono']['categoria']['confirmados']) . '</td>
                        </tr>
                    </table>
                </div>
                <div class="tabela-container">
                    <table>
                        <caption>Absoluto</caption>
                        <tr><th>Pendentes</th><th>Confirmados</th></tr>
                        <tr>
                            <td>' . htmlspecialchars($estatisticas['sem_kimono']['absoluto']['pendentes']) . '</td>
                            <td>' . htmlspecialchars($estatisticas['sem_kimono']['absoluto']['confirmados']) . '</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>';
    }

    $html .= '</div>';

    return $html;
}
?>