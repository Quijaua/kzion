@extends('layouts.tabler')

@section('page-title', 'Editar pessoa')
@section('page-pretitle', 'Pessoas')
@section('page-subtitle', $person->name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('people.index') }}">Pessoas</a></li>
    <li class="breadcrumb-item"><a href="{{ route('people.show', $person) }}">{{ Str::limit($person->name, 24) }}</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">Editar</a></li>
@endsection

@section('page-actions')
    <a href="{{ route('people.show', $person) }}" class="btn btn-ghost-secondary">
        <i class="ti ti-arrow-left me-1"></i> Voltar
    </a>
@endsection

@section('content')
<form method="POST" action="{{ route('people.update', $person) }}" novalidate>
    @csrf @method('PUT')
    @include('people.partials.form', ['person' => $person])
</form>
@endsection
