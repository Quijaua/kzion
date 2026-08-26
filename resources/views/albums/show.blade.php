@extends('layouts.tabler')

@section('page-title', $album->name)
@section('page-pretitle', 'Álbum')
@section('page-subtitle', $photos->total() . ' ' . ($photos->total() === 1 ? 'foto' : 'fotos'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('albums.index') }}">Álbuns</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">{{ Str::limit($album->name, 30) }}</a></li>
@endsection

@section('page-actions')
    <a href="{{ route('photos.index', ['album_id' => $album->id]) }}" class="btn">
        <i class="ti ti-search me-1"></i> Buscar dentro do álbum
    </a>
    <a href="{{ route('albums.edit', $album) }}" class="btn btn-primary">
        <i class="ti ti-edit me-1"></i> Editar
    </a>
    <form method="POST" action="{{ route('albums.destroy', $album) }}" class="d-inline"
          data-confirm="Excluir o álbum “{{ $album->name }}”?"
          data-confirm-text="As fotos continuam no acervo; só o agrupamento é removido."
          data-confirm-action="Excluir álbum">
        @csrf @method('DELETE')
        <button class="btn btn-icon btn-ghost-danger" aria-label="Excluir álbum" title="Excluir álbum">
            <i class="ti ti-trash"></i>
        </button>
    </form>
@endsection

@section('content')
@if($album->description)
    <div class="card mb-3">
        <div class="card-body">{{ $album->description }}</div>
    </div>
@endif

@if($photos->isEmpty())
    @include('partials.empty', [
        'icon' => 'ti-photo-off',
        'title' => 'Este álbum ainda não tem fotos',
        'text' => 'Edite o álbum para escolher quais fotos entram nele.',
        'actionUrl' => route('albums.edit', $album),
        'actionLabel' => 'Escolher fotos',
        'actionIcon' => 'ti-edit',
    ])
@else
    @include('photos.partials.grid', ['photos' => $photos])

    <div class="mt-4">{{ $photos->links() }}</div>
@endif
@endsection
