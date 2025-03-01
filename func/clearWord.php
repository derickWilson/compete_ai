<?php
function cleanWords($input) {
            // Remove espaços em branco no início e no final
            $input = trim($input);
            // Remove caracteres especiais e potencialmente perigosos
            $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            // Adicionalmente, você pode remover tags HTML
            $input = strip_tags($input);
            return $input;
        }
?>