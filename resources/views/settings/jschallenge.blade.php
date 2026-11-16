@extends('settings.layout')

@section('settings-tab', 'Desafio JS')

@section('settings-content')
<form method="POST" action="{{ route('settings.jschallenge.update') }}" class="d-flex flex-column flex-fill" novalidate>
    @csrf @method('PUT')

    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <h2 class="mb-0 me-auto">Desafio JS</h2>
            @if($customized)
                <span class="badge bg-blue-lt">Personalizado nesta tela</span>
            @else
                <span class="badge bg-secondary-lt">Usando os padrões do .env</span>
            @endif
        </div>
        <p class="text-secondary mb-4">
            A tela "Verificando seu navegador…" que aparece antes do acervo. O navegador resolve um
            pequeno cálculo em JavaScript e recebe um passe; bots que não executam JavaScript
            (scrapers simples, robôs de força bruta no login) ficam do lado de fora.
            O que for salvo aqui se sobrepõe ao <code>.env</code>.
        </p>

        @unless($defaults['enabled'])
            <div class="alert alert-warning" role="alert">
                <div class="d-flex">
                    <div class="me-2"><i class="ti ti-alert-triangle icon alert-icon"></i></div>
                    <div>
                        <h4 class="alert-title">Desligado no .env</h4>
                        <div class="text-secondary">
                            <code>JSCHALLENGE_ENABLED=false</code> é a chave de emergência e vence esta
                            tela: enquanto estiver assim, o desafio fica desligado para todos, seja qual
                            for o valor abaixo.
                        </div>
                    </div>
                </div>
            </div>
        @endunless

        {{-- ------------------------------------------------------------ --}}
        <h3 class="card-title"><i class="ti ti-shield-check me-1"></i> Desafio</h3>

        <input type="hidden" name="enabled" value="0">
        <label class="form-check form-switch form-switch-lg mb-4">
            <input class="form-check-input" type="checkbox" name="enabled" value="1" role="switch"
                   @checked(old('enabled', $values['enabled'] ? '1' : '0') === '1')>
            <span class="form-check-label">Mostrar o desafio antes do acervo</span>
            <span class="form-check-description">
                Desligado, ninguém vê a tela de verificação. Padrão do .env:
                <strong>{{ $defaults['enabled'] ? 'ligado' : 'desligado' }}</strong>.
            </span>
        </label>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label" for="jsc-difficulty">Dificuldade</label>
                <div class="input-group">
                    <input id="jsc-difficulty" type="number" name="difficulty" min="8" max="22" step="1"
                           class="form-control @error('difficulty') is-invalid @enderror"
                           value="{{ old('difficulty', $values['difficulty']) }}" required>
                    <span class="input-group-text">bits</span>
                </div>
                @error('difficulty')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="form-hint">
                    <span data-jsc-estimate></span>
                    Cada +1 dobra o tempo. Padrão do .env: <strong>{{ $defaults['difficulty'] }}</strong>.
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="jsc-pass">Validade do passe</label>
                <div class="input-group">
                    <input id="jsc-pass" type="number" name="pass_ttl_hours" min="1" max="720" step="1"
                           class="form-control @error('pass_ttl_hours') is-invalid @enderror"
                           value="{{ old('pass_ttl_hours', intdiv($values['pass_ttl'], 3600) ?: 1) }}" required>
                    <span class="input-group-text">horas</span>
                </div>
                @error('pass_ttl_hours')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="form-hint">
                    Depois disso o visitante passa pelo desafio de novo. Padrão do .env:
                    <strong>{{ intdiv($defaults['pass_ttl'], 3600) }} h</strong>.
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="jsc-except">IPs que nunca veem o desafio</label>
            <textarea id="jsc-except" name="except_ips" rows="3"
                      class="form-control font-monospace @error('except_ips') is-invalid @enderror"
                      placeholder="200.1.2.3&#10;10.0.0.0/8">{{ old('except_ips', implode("\n", $values['except_ips'])) }}</textarea>
            @error('except_ips')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <div class="form-hint">
                Um por linha: IP ou faixa CIDR (IPv4 ou IPv6). Para monitor de uptime, sua rede, outros
                servidores. Seu IP agora: <code>{{ $myIp }}</code>
                <a href="#" data-jsc-add-ip="{{ $myIp }}">adicionar</a>.
                @if($defaults['except_ips'])
                    Padrão do .env: <code>{{ implode(', ', $defaults['except_ips']) }}</code>.
                @endif
            </div>
        </div>

        {{-- ------------------------------------------------------------ --}}
        <h3 class="card-title mt-5"><i class="ti ti-device-laptop me-1"></i> Dispositivo confiável</h3>
        <p class="card-subtitle">
            Quem marca "Confiar neste dispositivo" no login pula o desafio por alguns dias. O passe é
            revogado no logout, na troca ou redefinição de senha e quando o mesmo cookie aparece em IPs
            demais.
        </p>

        <input type="hidden" name="trusted_enabled" value="0">
        <label class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="trusted_enabled" value="1" role="switch"
                   @checked(old('trusted_enabled', $values['trusted_enabled'] ? '1' : '0') === '1')>
            <span class="form-check-label">Oferecer "Confiar neste dispositivo" no login</span>
            <span class="form-check-description">
                Desligado, a caixa some do login e os dispositivos já confiáveis deixam de valer.
                Padrão do .env: <strong>{{ $defaults['trusted_enabled'] ? 'ligado' : 'desligado' }}</strong>.
            </span>
        </label>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label" for="jsc-days">Validade</label>
                <div class="input-group">
                    <input id="jsc-days" type="number" name="trusted_days" min="1" max="365" step="1"
                           class="form-control @error('trusted_days') is-invalid @enderror"
                           value="{{ old('trusted_days', $values['trusted_days']) }}" required>
                    <span class="input-group-text">dias</span>
                </div>
                @error('trusted_days')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="form-hint">
                    Vale para os próximos logins. Padrão do .env: <strong>{{ $defaults['trusted_days'] }}</strong>.
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="jsc-maxips">Máximo de IPs por hora</label>
                <input id="jsc-maxips" type="number" name="trusted_max_ips" min="0" max="100" step="1"
                       class="form-control @error('trusted_max_ips') is-invalid @enderror"
                       value="{{ old('trusted_max_ips', $values['trusted_max_ips']) }}" required>
                @error('trusted_max_ips')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="form-hint">
                    Acima disso o dispositivo é revogado (indício de cookie copiado). 0 desliga.
                    Padrão do .env: <strong>{{ $defaults['trusted_max_ips'] }}</strong>.
                </div>
            </div>
        </div>

        <div class="mb-0">
            <div class="form-label">Rede do dispositivo</div>
            <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column">
                @foreach([
                    'none' => ['Não conferir a rede', 'Recomendado. O passe vale em qualquer rede até expirar: no kzion ele só pula a tela de verificação, não dá acesso a foto nenhuma.'],
                    'prefix' => ['Mesma faixa de IP', 'Compara o começo do IP (/16 no IPv4). Trocar de rede (4G ↔ Wi-Fi) pede o desafio de novo, uma vez.'],
                    'asn' => ['Mesma operadora', 'Trocar entre 4G e Wi-Fi da mesma operadora não pede o desafio. Precisa da base GeoLite2-ASN da MaxMind.'],
                ] as $mode => [$title, $text])
                    <label class="form-selectgroup-item flex-fill">
                        <input type="radio" name="trusted_network" value="{{ $mode }}" class="form-selectgroup-input"
                               @checked(old('trusted_network', $values['trusted_network']) === $mode)>
                        <div class="form-selectgroup-label d-flex align-items-center p-3">
                            <div class="me-3"><span class="form-selectgroup-check"></span></div>
                            <div class="text-start">
                                <div class="fw-medium">
                                    {{ $title }}
                                    @if($defaults['trusted_network'] === $mode)
                                        <span class="badge bg-secondary-lt ms-1">padrão do .env</span>
                                    @endif
                                    @if($mode === 'asn' && ! $asnAvailable)
                                        <span class="badge bg-yellow-lt ms-1">base não instalada</span>
                                    @endif
                                </div>
                                <div class="text-secondary small">{{ $text }}</div>
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>
            @unless($asnAvailable)
                <div class="form-hint mt-2">
                    Sem a base de ASN, "Mesma operadora" funciona como "Mesma faixa de IP".
                    Instalação: <code>composer require maxmind-db/reader</code> e o arquivo
                    <code>storage/app/geoip/GeoLite2-ASN.mmdb</code>.
                </div>
            @endunless
        </div>
    </div>

    <div class="card-footer bg-transparent mt-auto">
        <div class="btn-list">
            @if($customized)
                <button type="submit" form="jsc-reset" class="btn btn-ghost-secondary">
                    <i class="ti ti-restore me-1"></i> Restaurar padrões do .env
                </button>
            @endif
            <div class="ms-auto btn-list">
                <a href="{{ route('settings.jschallenge') }}" class="btn">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</form>

{{-- Fora do formulário principal (formulários não podem ser aninhados). --}}
<form id="jsc-reset" method="POST" action="{{ route('settings.jschallenge.update') }}" class="d-none"
      data-confirm="Restaurar os padrões do .env?"
      data-confirm-text="Os valores salvos nesta tela são apagados e o desafio volta a usar o que está no .env."
      data-confirm-action="Restaurar">
    @csrf @method('PUT')
    <input type="hidden" name="action" value="reset">
</form>
@endsection

@push('scripts')
<script>
    // Estimativa do tempo médio do desafio: 2^bits tentativas de SHA-256.
    // ~250 mil/s num computador e ~65 mil/s num celular intermediário
    // (medidas do solver do desafio; o README do pacote dá 16 ≈ 0,3 s / ~1 s).
    (function () {
        var input = document.getElementById('jsc-difficulty');
        var out = document.querySelector('[data-jsc-estimate]');
        function fmt(s) {
            if (s < 1) return '≈ ' + s.toFixed(1).replace('.', ',') + ' s';
            if (s < 60) return '≈ ' + Math.round(s) + ' s';
            return '≈ ' + Math.round(s / 60) + ' min';
        }
        function update() {
            var bits = parseInt(input.value, 10);
            if (!bits || bits < 1 || bits > 32) { out.textContent = ''; return; }
            var tries = Math.pow(2, bits);
            out.textContent = 'Tempo médio: ' + fmt(tries / 250000) + ' no computador, ' + fmt(tries / 65000) + ' no celular.';
        }
        input.addEventListener('input', update);
        update();

        var add = document.querySelector('[data-jsc-add-ip]');
        var area = document.getElementById('jsc-except');
        add && add.addEventListener('click', function (e) {
            e.preventDefault();
            var ip = add.dataset.jscAddIp;
            var lines = area.value.split(/[\s,;]+/).filter(Boolean);
            if (lines.indexOf(ip) === -1) {
                area.value = (area.value.trim() ? area.value.trim() + '\n' : '') + ip;
            }
            area.focus();
        });
    })();
</script>
@endpush
