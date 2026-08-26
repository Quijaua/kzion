@extends('layouts.tabler')

@php($label = $photo->title ?? $photo->original_filename)

@section('page-title', 'Editar foto')
@section('page-pretitle', 'Fotos')
@section('page-subtitle', $label)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('photos.index') }}">Fotos</a></li>
    <li class="breadcrumb-item"><a href="{{ route('photos.show', $photo) }}">{{ Str::limit($label, 24) }}</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">Editar</a></li>
@endsection

@section('page-actions')
    <a href="{{ route('photos.show', $photo) }}" class="btn btn-ghost-secondary">
        <i class="ti ti-arrow-left me-1"></i> Voltar
    </a>
@endsection

@section('content')
<form method="POST" action="{{ route('photos.update', $photo) }}" novalidate>
    @csrf @method('PUT')

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <img src="{{ route('photos.thumbnail', $photo) }}" class="card-img-top" alt="{{ $label }}">
                <div class="card-body">
                    <div class="text-secondary small text-truncate" title="{{ $photo->original_filename }}">
                        <i class="ti ti-file me-1"></i>{{ $photo->original_filename }}
                    </div>
                    <div class="text-secondary small">
                        <i class="ti ti-dimensions me-1"></i>{{ $photo->width }}&times;{{ $photo->height }} px
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="title">Título</label>
                        <input id="title" type="text" name="title" class="form-control"
                               value="{{ old('title', $photo->title) }}" placeholder="Ex.: Natal de 1994">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="person-ids">Pessoas na foto</label>
                        <select id="person-ids" name="person_ids[]" class="form-select" multiple
                                data-searchable data-placeholder="Marque quem aparece na foto">
                            @foreach($people as $p)
                                <option value="{{ $p->id }}" @selected($photo->people->contains($p->id))>{{ $p->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-hint">
                            Quem tiver acesso a <strong>qualquer uma</strong> das pessoas marcadas passa a
                            ver esta foto. Tirar todas as marcações esconde a foto de todo mundo menos dos
                            administradores.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="album-ids">Álbuns</label>
                        <select id="album-ids" name="album_ids[]" class="form-select" multiple
                                data-searchable data-placeholder="Adicionar a álbuns">
                            @foreach($albums as $a)
                                <option value="{{ $a->id }}" @selected($photo->albums->contains($a->id))>{{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="description">Descrição</label>
                        <textarea id="description" name="description" class="form-control" rows="3"
                                  placeholder="O que está acontecendo na foto?">{{ old('description', $photo->description) }}</textarea>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label" for="taken_at">Data da foto</label>
                            <input id="taken_at" type="date" name="taken_at" class="form-control"
                                   value="{{ old('taken_at', $photo->taken_at?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="location">Local</label>
                            <input id="location" type="text" name="location" class="form-control"
                                   value="{{ old('location', $photo->location) }}" placeholder="Cidade, praia, casa…">
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i> Salvar
                    </button>
                    <a href="{{ route('photos.show', $photo) }}" class="btn btn-ghost-secondary">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
