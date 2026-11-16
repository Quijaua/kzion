@extends('layouts.tabler')

@section('page-title', 'Novo local')
@section('page-pretitle', 'Locais')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('locations.index') }}">Locais</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">Novo</a></li>
@endsection

@section('page-actions')
    <a href="{{ route('locations.index') }}" class="btn btn-ghost-secondary">
        <i class="ti ti-arrow-left me-1"></i> Voltar
    </a>
@endsection

@section('content')
<form method="POST" action="{{ route('locations.store') }}" novalidate>
    @csrf
    @include('locations.partials.form')
</form>
@endsection
