@extends('layouts.auth')

@section('page-title', 'Recuperar senha')
@section('heading', 'Esqueceu a senha?')
@section('subheading', 'Informe seu e-mail e enviaremos um link para você criar uma nova senha.')

@section('content')
<form method="POST" action="{{ route('password.email') }}" novalidate>
    @csrf

    <div class="mb-3">
        <label class="form-label" for="email">E-mail</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}"
               class="form-control @error('email') is-invalid @enderror"
               placeholder="voce@exemplo.com" required autofocus>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-footer">
        <button type="submit" class="btn btn-primary w-100">
            <i class="ti ti-mail me-1"></i> Enviar link de recuperação
        </button>
    </div>
</form>
@endsection

@section('footer')
    <a href="{{ route('login') }}" tabindex="-1"><i class="ti ti-arrow-left"></i> Voltar para o login</a>
@endsection
