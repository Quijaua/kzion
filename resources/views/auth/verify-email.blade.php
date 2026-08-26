@extends('layouts.auth')

@section('page-title', 'Verificar e-mail')
@section('heading', 'Verifique seu e-mail')
@section('subheading', 'Enviamos um link de verificação para o e-mail cadastrado. Clique nele para liberar o acesso ao acervo.')

@section('content')
@if (session('status') == 'verification-link-sent')
    <div class="alert alert-success" role="alert">
        <div class="d-flex">
            <div class="me-2"><i class="ti ti-circle-check icon alert-icon"></i></div>
            <div>Um novo link de verificação foi enviado para o seu e-mail.</div>
        </div>
    </div>
@endif

<form method="POST" action="{{ route('verification.send') }}">
    @csrf
    <button type="submit" class="btn btn-primary w-100">
        <i class="ti ti-mail-forward me-1"></i> Reenviar e-mail de verificação
    </button>
</form>
@endsection

@section('footer')
<form method="POST" action="{{ route('logout') }}" class="d-inline">
    @csrf
    <button type="submit" class="btn btn-link p-0 text-secondary">Sair</button>
</form>
@endsection
