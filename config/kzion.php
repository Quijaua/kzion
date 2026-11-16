<?php

/*
 * Configurações próprias do kzion que vêm do ambiente (.env).
 * O que um admin muda pela interface fica na tabela `settings`, não aqui.
 */
return [

    'upload' => [
        // Quantas imagens o envio em lote manda ao mesmo tempo. Mais envios em
        // paralelo aproveitam melhor conexões com latência alta, mas cada um
        // ocupa um processo do PHP-FPM enquanto grava. Limitado entre 1 e 10.
        'parallel' => max(1, min(10, (int) env('UPLOAD_PARALLEL', 3))),
    ],

];
