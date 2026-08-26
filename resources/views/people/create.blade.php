@extends('layouts.tabler')

@section('page-title', 'Nova pessoa')
@section('page-pretitle', 'Pessoas')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('people.index') }}">Pessoas</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">Nova</a></li>
@endsection

@section('page-actions')
    <a href="{{ route('people.index') }}" class="btn btn-ghost-secondary">
        <i class="ti ti-arrow-left me-1"></i> Voltar
    </a>
@endsection

@section('content')
<form method="POST" action="{{ route('people.store') }}" novalidate>
    @csrf
    @include('people.partials.form')
</form>
@endsection
