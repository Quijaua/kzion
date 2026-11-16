{{--
    Caixa "Confiar neste dispositivo" para o formulário de login.
    Uso: @include('jschallenge.partials.trust-device')
    Adaptada ao Tabler (o kzion não usa Tailwind).
--}}
@if (config('jschallenge.trusted.enabled') && ! config('jschallenge.trusted.always') && config('jschallenge.trusted.input') !== 'remember')
    <label class="form-check mt-2 mb-0">
        <input id="trust_device" type="checkbox" value="1" class="form-check-input"
               name="{{ config('jschallenge.trusted.input', 'trust_device') }}">
        <span class="form-check-label">
            Confiar neste dispositivo por {{ config('jschallenge.trusted.ttl_days', 30) }} dias
        </span>
        <span class="form-check-description">
            Pula a tela "Verificando seu navegador…". Não marque em computadores compartilhados.
        </span>
    </label>
@endif
