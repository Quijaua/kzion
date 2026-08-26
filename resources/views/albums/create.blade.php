@extends('layouts.tabler')

@section('page-title', 'Novo álbum')
@section('page-pretitle', 'Álbuns')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('albums.index') }}">Álbuns</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">Novo</a></li>
@endsection

@section('page-actions')
    <a href="{{ route('albums.index') }}" class="btn btn-ghost-secondary">
        <i class="ti ti-arrow-left me-1"></i> Voltar
    </a>
@endsection

@section('content')
<form method="POST" action="{{ route('albums.store') }}" novalidate>
    @csrf
    @include('albums.partials.form')
</form>
@endsection
