<?php

return [

    // Liga/desliga o desafio (ex.: JSCHALLENGE_ENABLED=false no .env de dev)
    'enabled' => (bool) env('JSCHALLENGE_ENABLED', true),

    // Dificuldade do proof-of-work, em bits zerados no início do SHA-256.
    // Média de tentativas = 2^N; o JS faz ~250 mil hashes/s num desktop.
    // 16 ≈ 65 mil hashes: ~0,3s em desktop, ~1s em celular intermediário.
    // Cada +1 dobra o custo médio (18 ≈ 1s desktop / 3–4s celular).
    'difficulty' => (int) env('JSCHALLENGE_DIFFICULTY', 16),

    // Tempo mínimo (ms) que a tela fica visível, mesmo se o cálculo terminar antes.
    // A Hostinger usa 3000. 0 = libera assim que resolver.
    'min_delay_ms' => (int) env('JSCHALLENGE_MIN_DELAY_MS', 0),

    // Nome do cookie de liberação
    'cookie' => env('JSCHALLENGE_COOKIE', '__jsc'),

    // Validade do cookie de liberação (segundos)
    'pass_ttl' => (int) env('JSCHALLENGE_PASS_TTL', 60 * 60 * 12),

    // Validade do token do desafio (segundos): tempo máximo para resolver e enviar
    'token_ttl' => 300,

    // Amarrar o cookie ao IP do cliente. Mais seguro, mas usuários de celular
    // que trocam de rede (4G <-> Wi-Fi) terão que refazer o desafio.
    'bind_ip' => (bool) env('JSCHALLENGE_BIND_IP', false),

    // Caminhos que nunca passam pelo desafio (sintaxe de $request->is()).
    // Inclua webhooks, APIs, health checks, feeds, callbacks de pagamento etc.
    'except' => [
        '_jsc/*',
        'api/*',
        'up',
        'webhooks/*',
        'robots.txt',
        'sitemap.xml',
        'favicon.ico',
        'build/*',
        'storage/*',
    ],

    // IPs ou CIDRs liberados, separados por vírgula
    // (monitoramento, seu escritório, outros servidores seus).
    // Ex.: JSCHALLENGE_EXCEPT_IPS="10.0.0.0/8,200.1.2.3"
    'except_ips' => array_values(array_filter(array_map('trim',
        explode(',', (string) env('JSCHALLENGE_EXCEPT_IPS', ''))
    ))),

    // Buscadores liberados SÓ após confirmação por DNS reverso + direto
    // (impossível de falsificar só trocando o User-Agent). Resultado fica em cache.
    // Formato: regex do User-Agent => sufixos de hostname aceitos.
    'verified_bots' => [
        '/Googlebot|Google-InspectionTool|AdsBot-Google|Storebot-Google/i' => ['.googlebot.com', '.google.com', '.googleusercontent.com'],
        '/bingbot|BingPreview/i' => ['.search.msn.com'],
        '/YandexBot/i' => ['.yandex.ru', '.yandex.net', '.yandex.com'],
        '/Applebot/i' => ['.applebot.apple.com'],
    ],

    // User-Agents liberados SEM verificação (falsificável!).
    // Útil para pré-visualização de links no WhatsApp, Telegram etc., que não
    // executam JavaScript. Um bot que copiar esse User-Agent passa direto:
    // é uma troca consciente. Esvazie a lista se não precisar de prévias.
    'allow_user_agents' => [
        '/WhatsApp\//i',
        '/facebookexternalhit|facebookcatalog|meta-externalagent/i',
        '/TelegramBot/i',
        '/Twitterbot/i',
        '/LinkedInBot/i',
        '/Slackbot-LinkExpanding|Discordbot/i',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dispositivo confiável (3ª camada)
    |--------------------------------------------------------------------------
    | Após o login, se o usuário marcar "Confiar neste dispositivo", emite um
    | cookie de longa duração vinculado ao usuário e guardado no banco
    | (tabela js_challenge_devices), que pode ser revogado a qualquer momento.
    */
    'trusted' => [
        'enabled' => (bool) env('JSCHALLENGE_TRUSTED_ENABLED', true),

        'cookie' => env('JSCHALLENGE_TRUSTED_COOKIE', '__jsc_t'),

        // Validade (dias)
        'ttl_days' => (int) env('JSCHALLENGE_TRUSTED_DAYS', 30),

        // Campo do formulário de login que liga o recurso.
        // Use 'remember' para aproveitar o "Lembrar de mim" do Breeze/Jetstream.
        'input' => env('JSCHALLENGE_TRUSTED_INPUT', 'trust_device'),

        // Se true, todo login emite o passe longo, mesmo sem a caixa marcada
        'always' => (bool) env('JSCHALLENGE_TRUSTED_ALWAYS', false),

        // Vínculo com a rede, comparado a cada acesso. No kzion o padrão é
        // 'none': o passe só pula a tela de verificação (o acesso às fotos
        // depende do login), então conferir a rede protegia pouco e pedia o
        // desafio de novo a cada troca 4G ↔ Wi-Fi.
        //   'prefix' → mesma faixa de IP (IPv4 /16, IPv6 /48 por padrão)
        //   'asn'    → mesma operadora; requer `composer require maxmind-db/reader`
        //              e a base GeoLite2-ASN.mmdb (gratuita, com cadastro na MaxMind).
        //              Sem a base, cai automaticamente para 'prefix'.
        //   'none'   → não confere a rede
        // Rede diferente NÃO derruba o login: só mostra o desafio de novo,
        // e depois que ele é resolvido o dispositivo passa a valer na rede nova.
        'network' => env('JSCHALLENGE_TRUSTED_NETWORK', 'none'),
        'ipv4_prefix' => 16,
        'ipv6_prefix' => 48,
        'asn_db' => env('JSCHALLENGE_ASN_DB', storage_path('app/geoip/GeoLite2-ASN.mmdb')),

        // Detecção de compartilhamento: se o mesmo cookie aparecer em mais que
        // N IPs diferentes dentro de 1 hora, o dispositivo é revogado. 0 = desliga.
        'max_ips_per_hour' => (int) env('JSCHALLENGE_TRUSTED_MAX_IPS', 8),

        // Cache da consulta ao banco (segundos) para não bater no banco a cada
        // requisição. A revogação limpa esse cache na hora.
        'cache_seconds' => 60,
    ],

];
