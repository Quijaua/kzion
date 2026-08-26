@extends('layouts.tabler')

@section('page-title', 'Editar álbum')
@section('page-pretitle', 'Álbuns')
@section('page-subtitle', $album->name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('albums.index') }}">Álbuns</a></li>
    <li class="breadcrumb-item"><a href="{{ route('albums.show', $album) }}">{{ Str::limit($album->name, 24) }}</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">Editar</a></li>
@endsection

@section('page-actions')
    <a href="{{ route('albums.show', $album) }}" class="btn btn-ghost-secondary">
        <i class="ti ti-arrow-left me-1"></i> Voltar
    </a>
@endsection

@section('content')
<form method="POST" action="{{ route('albums.update', $album) }}" novalidate>
    @csrf @method('PUT')
    @include('albums.partials.form', ['album' => $album])
</form>
@endsection
