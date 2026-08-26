@extends('layouts.tabler')

@section('page-title', 'Novo usuário')
@section('page-pretitle', 'Administração')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Usuários</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">Novo</a></li>
@endsection

@section('page-actions')
    <a href="{{ route('users.index') }}" class="btn btn-ghost-secondary">
        <i class="ti ti-arrow-left me-1"></i> Voltar
    </a>
@endsection

@section('content')
<form method="POST" action="{{ route('users.store') }}" novalidate>
    @csrf
    @include('users.partials.form')
</form>
@endsection
