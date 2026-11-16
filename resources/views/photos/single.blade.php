@extends('layouts.tabler')

@section('page-title', 'Foto única')
@section('page-pretitle', 'Enviar fotos')
@section('page-subtitle', 'Envie a imagem já com os metadados preenchidos')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('photos.index') }}">Fotos</a></li>
    <li class="breadcrumb-item"><a href="{{ route('photos.create') }}">Enviar</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">Foto única</a></li>
@endsection

@section('page-actions')
    <a href="{{ route('photos.create') }}" class="btn btn-ghost-secondary">
        <i class="ti ti-arrow-left me-1"></i> Voltar
    </a>
@endsection

@section('content')
<form method="POST" action="{{ route('photos.store') }}" enctype="multipart/form-data" novalidate>
    @csrf

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Imagem</h3></div>
                <div class="card-body">
                    <label class="form-label required" for="photo-file">Arquivo</label>
                    <input id="photo-file" type="file" name="photos[]"
                           class="form-control @error('photos') is-invalid @enderror"
                           accept="image/jpeg,image/png,image/webp" required>
                    <div class="form-hint">JPG, PNG ou WEBP, até 20 MB.</div>
                    @error('photos')<div class="invalid-feedback">{{ $message }}</div>@enderror

                    {{-- O hidden manda 0 quando o switch está desligado (checkbox
                         desmarcado não é enviado); ligado, o 1 do checkbox prevalece. --}}
                    <input type="hidden" name="read_exif" value="0">
                    <label class="form-check form-switch mt-3 mb-0">
                        <input class="form-check-input" type="checkbox" name="read_exif" value="1"
                               @checked(old('read_exif', '1') !== '0')>
                        <span class="form-check-label">Ler dados EXIF da imagem</span>
                        <span class="form-check-description">
                            Data, câmera e GPS gravados pela câmera. Desligue para scans, fotos
                            de WhatsApp ou imagens editadas, que normalmente não têm EXIF.
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Metadados</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="title">Título</label>
                        <input id="title" type="text" name="title" class="form-control"
                               value="{{ old('title') }}" placeholder="Ex.: Natal de 1994 na casa da vó">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="person-ids">Pessoas na foto</label>
                        <select id="person-ids" name="person_ids[]" class="form-select" multiple
                                data-searchable data-placeholder="Marque quem aparece na foto">
                            @foreach($people as $p)
                                <option value="{{ $p->id }}" @selected(in_array($p->id, (array) old('person_ids', [])))>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="album-ids">Álbuns</label>
                        <select id="album-ids" name="album_ids[]" class="form-select" multiple
                                data-searchable data-placeholder="Adicionar a álbuns">
                            @foreach($albums as $a)
                                <option value="{{ $a->id }}" @selected(in_array($a->id, (array) old('album_ids', [])))>{{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="description">Descrição</label>
                        <textarea id="description" name="description" class="form-control" rows="3"
                                  placeholder="O que está acontecendo na foto?">{{ old('description') }}</textarea>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label" for="taken_at">Data da foto</label>
                            <input id="taken_at" type="date" name="taken_at" class="form-control"
                                   value="{{ old('taken_at') }}">
                            <div class="form-hint">Se vazio e a leitura de EXIF estiver ligada, usamos a data do EXIF.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="location-id">Local</label>
                            <select id="location-id" name="location_id" class="form-select @error('location_id') is-invalid @enderror"
                                    data-searchable data-placeholder="Escolha o local">
                                <option value=""></option>
                                @foreach($locations as $l)
                                    <option value="{{ $l->id }}" @selected((string) old('location_id', '') === (string) $l->id)>{{ $l->name }}</option>
                                @endforeach
                            </select>
                            @error('location_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <div class="form-hint">
                                Não está na lista?
                                <a href="{{ route('locations.create') }}" target="_blank" rel="noopener">Cadastrar novo local</a>
                                e recarregar esta página.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i> Salvar foto
                    </button>
                    <a href="{{ route('photos.index') }}" class="btn btn-ghost-secondary">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
