@extends('layouts.auth')

@section('page-title', 'Criar conta')
@section('heading', 'Criar uma conta')
@section('subheading', 'Leva menos de um minuto')

@section('content')
<form method="POST" action="{{ route('register') }}" novalidate>
    @csrf

    <div class="mb-3">
        <label class="form-label" for="name">Nome</label>
        <input id="name" type="text" name="name" value="{{ old('name') }}"
               class="form-control @error('name') is-invalid @enderror"
               placeholder="Seu nome completo" required autofocus autocomplete="name">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label class="form-label" for="email">E-mail</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}"
               class="form-control @error('email') is-invalid @enderror"
               placeholder="voce@exemplo.com" required autocomplete="username">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label class="form-label" for="password">Senha</label>
        <input id="password" type="password" name="password"
               class="form-control @error('password') is-invalid @enderror"
               placeholder="Ao menos 8 caracteres" required autocomplete="new-password">
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label class="form-label" for="password_confirmation">Confirmar senha</label>
        <input id="password_confirmation" type="password" name="password_confirmation"
               class="form-control @error('password_confirmation') is-invalid @enderror"
               placeholder="Repita a senha" required autocomplete="new-password">
        @error('password_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-footer">
        <button type="submit" class="btn btn-primary w-100">
            <i class="ti ti-user-plus me-1"></i> Criar conta
        </button>
    </div>
</form>
@endsection

@section('footer')
    Já tem conta? <a href="{{ route('login') }}" tabindex="-1">Entrar</a>
@endsection
