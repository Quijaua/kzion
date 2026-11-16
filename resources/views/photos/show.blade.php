@extends('layouts.tabler')

@php($label = $photo->title ?? $photo->original_filename)

@section('page-title', $label)
@section('page-pretitle', 'Foto')
@section('page-subtitle')
    @if($photo->taken_at){{ $photo->taken_at->format('d/m/Y') }}@endif
    @if($photo->location) · {{ $photo->location }}@endif
@endsection

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('photos.index') }}">Fotos</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">{{ Str::limit($label, 30) }}</a></li>
@endsection

@section('page-actions')
    <a href="{{ route('photos.file', $photo) }}" class="btn" target="_blank" rel="noopener">
        <i class="ti ti-external-link me-1"></i> Abrir original
    </a>
    <a href="{{ route('photos.edit', $photo) }}" class="btn btn-primary">
        <i class="ti ti-edit me-1"></i> Editar
    </a>
    <form method="POST" action="{{ route('photos.destroy', $photo) }}" class="d-inline"
          data-confirm="Excluir esta foto?"
          data-confirm-text="O arquivo original e a miniatura serão apagados do servidor."
          data-confirm-action="Excluir foto">
        @csrf @method('DELETE')
        <button class="btn btn-icon btn-ghost-danger" aria-label="Excluir foto" title="Excluir foto">
            <i class="ti ti-trash"></i>
        </button>
    </form>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card" data-lightbox-gallery>
            <button type="button" class="btn btn-icon position-absolute top-0 end-0 m-2 z-1"
                    data-lightbox="{{ route('photos.file', $photo) }}"
                    data-lightbox-caption="{{ $label }}"
                    aria-label="Ampliar foto" title="Ampliar">
                <i class="ti ti-maximize"></i>
            </button>
            <img src="{{ route('photos.file', $photo) }}" class="card-img" alt="{{ $label }}">
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Detalhes</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5 text-secondary fw-normal">Título</dt>
                    <dd class="col-7">{{ $photo->title ?? '—' }}</dd>

                    <dt class="col-5 text-secondary fw-normal">Descrição</dt>
                    <dd class="col-7">{{ $photo->description ?? '—' }}</dd>

                    <dt class="col-5 text-secondary fw-normal">Data</dt>
                    <dd class="col-7">{{ $photo->taken_at?->format('d/m/Y H:i') ?? '—' }}</dd>

                    <dt class="col-5 text-secondary fw-normal">Local</dt>
                    <dd class="col-7">{{ $photo->location ?? '—' }}</dd>

                    <dt class="col-5 text-secondary fw-normal">Dimensões</dt>
                    <dd class="col-7">{{ $photo->width }}&times;{{ $photo->height }} px</dd>

                    <dt class="col-5 text-secondary fw-normal">Arquivo</dt>
                    <dd class="col-7 text-truncate" title="{{ $photo->original_filename }}">
                        {{ $photo->original_filename }}
                    </dd>

                    @if($photo->uploader)
                        <dt class="col-5 text-secondary fw-normal">Enviada por</dt>
                        <dd class="col-7">{{ $photo->uploader->name }}</dd>
                    @endif

                    <dt class="col-5 text-secondary fw-normal">Enviada em</dt>
                    <dd class="col-7">{{ $photo->created_at?->timezone('America/Sao_Paulo')->format('d/m/Y H:i') ?? '—' }}</dd>

                    @if($photo->file_size)
                        <dt class="col-5 text-secondary fw-normal">Tamanho</dt>
                        <dd class="col-7">
                            @if($photo->file_size >= 1048576)
                                {{ number_format($photo->file_size / 1048576, 1, ',', '.') }} MB
                            @else
                                {{ number_format($photo->file_size / 1024, 0, ',', '.') }} KB
                            @endif
                        </dd>
                    @endif
                </dl>
            </div>

            <div class="card-body border-top">
                <div class="text-secondary mb-2">
                    <i class="ti ti-users me-1"></i> Pessoas
                </div>
                <div class="d-flex flex-wrap gap-1">
                    @forelse($photo->people as $p)
                        @if(auth()->user()->canSeePerson($p))
                            <a href="{{ route('people.show', $p) }}" class="badge bg-primary-lt">{{ $p->name }}</a>
                        @else
                            {{-- Sem permissão para navegar por esta pessoa: mostra o nome,
                                 mas sem link, que só levaria a um 403. --}}
                            <span class="badge bg-secondary-lt"
                                  title="Você não tem permissão para ver as outras fotos desta pessoa">{{ $p->name }}</span>
                        @endif
                    @empty
                        <span class="text-secondary">Nenhuma pessoa marcada</span>
                    @endforelse
                </div>

                <div class="text-secondary mt-3 mb-2">
                    <i class="ti ti-album me-1"></i> Álbuns
                </div>
                <div class="d-flex flex-wrap gap-1">
                    @forelse($photo->albums as $a)
                        <a href="{{ route('albums.show', $a) }}" class="badge bg-secondary-lt">{{ $a->name }}</a>
                    @empty
                        <span class="text-secondary">Nenhum álbum</span>
                    @endforelse
                </div>
            </div>
        </div>

        @if($photo->exif)
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title"><i class="ti ti-camera me-1"></i> EXIF</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5 text-secondary fw-normal">Câmera</dt>
                        <dd class="col-7">{{ trim($photo->exif->make.' '.$photo->exif->model) ?: '—' }}</dd>

                        <dt class="col-5 text-secondary fw-normal">Lente</dt>
                        <dd class="col-7">{{ $photo->exif->lens ?? '—' }}</dd>

                        <dt class="col-5 text-secondary fw-normal">ISO</dt>
                        <dd class="col-7">{{ $photo->exif->iso ?? '—' }}</dd>

                        <dt class="col-5 text-secondary fw-normal">Abertura</dt>
                        <dd class="col-7">{{ $photo->exif->aperture ?? '—' }}</dd>

                        <dt class="col-5 text-secondary fw-normal">Velocidade</dt>
                        <dd class="col-7">{{ $photo->exif->shutter_speed ?? '—' }}</dd>

                        <dt class="col-5 text-secondary fw-normal">Dist. focal</dt>
                        <dd class="col-7">{{ $photo->exif->focal_length ?? '—' }}</dd>

                        <dt class="col-5 text-secondary fw-normal">Software</dt>
                        <dd class="col-7">{{ $photo->exif->software ?? '—' }}</dd>

                        @if($photo->exif->gps_latitude)
                            <dt class="col-5 text-secondary fw-normal">GPS</dt>
                            <dd class="col-7">
                                <a href="https://www.openstreetmap.org/?mlat={{ $photo->exif->gps_latitude }}&mlon={{ $photo->exif->gps_longitude }}#map=15/{{ $photo->exif->gps_latitude }}/{{ $photo->exif->gps_longitude }}"
                                   target="_blank" rel="noopener">
                                    {{ $photo->exif->gps_latitude }}, {{ $photo->exif->gps_longitude }}
                                </a>
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
