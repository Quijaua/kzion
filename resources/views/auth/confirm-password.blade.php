@extends('layouts.auth')

@section('page-title', 'Confirmar senha')
@section('heading', 'Confirme sua senha')
@section('subheading', 'Esta é uma área protegida. Confirme sua senha para continuar.')

@section('content')
<form method="POST" action="{{ route('password.confirm') }}" novalidate>
    @csrf

    <div class="mb-3">
        <label class="form-label" for="password">Senha</label>
        <input id="password" type="password" name="password"
               class="form-control @error('password') is-invalid @enderror"
               placeholder="Sua senha" required autofocus autocomplete="current-password">
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-footer">
        <button type="submit" class="btn btn-primary w-100">
            <i class="ti ti-shield-check me-1"></i> Confirmar
        </button>
    </div>
</form>
@endsection
