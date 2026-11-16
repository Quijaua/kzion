@extends('layouts.auth')

@section('page-title', 'Entrar')
@section('heading', 'Entre na sua conta')
@section('subheading', 'Acesse o acervo de fotos da família')

@section('content')
<form method="POST" action="{{ route('login') }}" autocomplete="on" novalidate>
    @csrf

    <div class="mb-3">
        <label class="form-label" for="email">E-mail</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}"
               class="form-control @error('email') is-invalid @enderror"
               placeholder="voce@exemplo.com" required autofocus autocomplete="username">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-2">
        <label class="form-label" for="password">
            Senha
            @if (Route::has('password.request'))
                <span class="form-label-description">
                    <a href="{{ route('password.request') }}">Esqueci minha senha</a>
                </span>
            @endif
        </label>
        <input id="password" type="password" name="password"
               class="form-control @error('password') is-invalid @enderror"
               placeholder="Sua senha" required autocomplete="current-password">
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label class="form-check">
            <input type="checkbox" name="remember" class="form-check-input">
            <span class="form-check-label">Manter conectado neste dispositivo</span>
        </label>
        @include('jschallenge.partials.trust-device')
    </div>

    <div class="form-footer">
        <button type="submit" class="btn btn-primary w-100">
            <i class="ti ti-login me-1"></i> Entrar
        </button>
    </div>
</form>
@endsection

@section('footer')
    @if (Route::has('register') && \App\Models\Setting::registrationEnabled())
        Ainda não tem conta? <a href="{{ route('register') }}" tabindex="-1">Criar conta</a>
    @endif
@endsection
