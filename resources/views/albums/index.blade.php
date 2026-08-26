@extends('layouts.tabler')

@section('page-title', 'Álbuns')
@section('page-pretitle', 'Acervo')
@section('page-subtitle', $albums->count() . ' ' . ($albums->count() === 1 ? 'álbum' : 'álbuns'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">Álbuns</a></li>
@endsection

@section('page-actions')
    <a href="{{ route('albums.create') }}" class="btn btn-primary">
        <i class="ti ti-plus me-1"></i> Novo álbum
    </a>
@endsection

@section('content')
@if($albums->isEmpty())
    @include('partials.empty', [
        'icon' => 'ti-album-off',
        'title' => 'Nenhum álbum ainda',
        'text' => 'Álbuns agrupam fotos por evento, viagem ou época — independente das pessoas marcadas.',
        'actionUrl' => route('albums.create'),
        'actionLabel' => 'Criar o primeiro álbum',
    ])
@else
    <div class="row row-cards">
        @foreach($albums as $a)
            <div class="col-sm-6 col-lg-4 col-xl-3">
                <div class="card card-link card-link-pop h-100">
                    <a href="{{ route('albums.show', $a) }}" class="photo-tile" style="aspect-ratio: 16/9;">
                        @if($a->coverPhoto)
                            <img src="{{ route('photos.thumbnail', $a->coverPhoto) }}" alt="{{ $a->name }}" loading="lazy">
                        @else
                            <span class="d-flex h-100 align-items-center justify-content-center text-secondary">
                                <i class="ti ti-album fs-1"></i>
                            </span>
                        @endif
                    </a>
                    <div class="card-body">
                        <h3 class="card-title mb-1">
                            <a href="{{ route('albums.show', $a) }}" class="text-reset text-decoration-none">{{ $a->name }}</a>
                        </h3>
                        <div class="text-secondary">
                            {{ $a->photos_count }} {{ $a->photos_count === 1 ? 'foto' : 'fotos' }}
                        </div>
                        @if($a->description)
                            <p class="text-secondary small mt-2 mb-0">{{ Str::limit($a->description, 90) }}</p>
                        @endif
                    </div>
                    <div class="card-footer d-flex gap-1">
                        <a href="{{ route('albums.show', $a) }}" class="btn btn-sm btn-ghost-secondary">
                            <i class="ti ti-eye me-1"></i> Abrir
                        </a>
                        <a href="{{ route('albums.edit', $a) }}" class="btn btn-sm btn-ghost-secondary ms-auto"
                           aria-label="Editar {{ $a->name }}">
                            <i class="ti ti-edit"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
