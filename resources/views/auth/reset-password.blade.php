@extends('layouts.auth')

@section('page-title', 'Nova senha')
@section('heading', 'Definir nova senha')

@section('content')
<form method="POST" action="{{ route('password.store') }}" novalidate>
    @csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">

    <div class="mb-3">
        <label class="form-label" for="email">E-mail</label>
        <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}"
               class="form-control @error('email') is-invalid @enderror"
               required autofocus autocomplete="username">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label class="form-label" for="password">Nova senha</label>
        <input id="password" type="password" name="password"
               class="form-control @error('password') is-invalid @enderror"
               placeholder="Ao menos 8 caracteres" required autocomplete="new-password">
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label class="form-label" for="password_confirmation">Confirmar nova senha</label>
        <input id="password_confirmation" type="password" name="password_confirmation"
               class="form-control @error('password_confirmation') is-invalid @enderror"
               placeholder="Repita a nova senha" required autocomplete="new-password">
        @error('password_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-footer">
        <button type="submit" class="btn btn-primary w-100">
            <i class="ti ti-lock-check me-1"></i> Redefinir senha
        </button>
    </div>
</form>
@endsection
