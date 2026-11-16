@extends('layouts.tabler')

@section('page-title', 'Editar local')
@section('page-pretitle', 'Locais')
@section('page-subtitle', $location->name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('locations.index') }}">Locais</a></li>
    <li class="breadcrumb-item"><a href="{{ route('locations.show', $location) }}">{{ Str::limit($location->name, 24) }}</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">Editar</a></li>
@endsection

@section('page-actions')
    <a href="{{ route('locations.show', $location) }}" class="btn btn-ghost-secondary">
        <i class="ti ti-arrow-left me-1"></i> Voltar
    </a>
@endsection

@section('content')
<form method="POST" action="{{ route('locations.update', $location) }}" novalidate>
    @csrf @method('PUT')
    @include('locations.partials.form', ['location' => $location])
</form>
@endsection
