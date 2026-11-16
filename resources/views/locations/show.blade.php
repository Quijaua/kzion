@extends('layouts.tabler')

@section('page-title', $location->name)
@section('page-pretitle', 'Local')
@section('page-subtitle', $photos->total() . ' ' . ($photos->total() === 1 ? 'foto visível para você' : 'fotos visíveis para você'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('locations.index') }}">Locais</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">{{ Str::limit($location->name, 30) }}</a></li>
@endsection

@section('page-actions')
    <a href="{{ route('photos.index', ['location' => $location->name]) }}" class="btn">
        <i class="ti ti-search me-1"></i> Buscar neste local
    </a>
    @if($canManage)
        <a href="{{ route('locations.edit', $location) }}" class="btn btn-primary">
            <i class="ti ti-edit me-1"></i> Editar
        </a>
        <form method="POST" action="{{ route('locations.destroy', $location) }}" class="d-inline"
              data-confirm="Excluir “{{ $location->name }}”?"
              data-confirm-text="As fotos não são apagadas: elas só ficam sem local."
              data-confirm-action="Excluir local">
            @csrf @method('DELETE')
            <button class="btn btn-icon btn-ghost-danger" aria-label="Excluir local" title="Excluir local">
                <i class="ti ti-trash"></i>
            </button>
        </form>
    @endif
@endsection

@section('content')
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <span class="avatar avatar-xl bg-green-lt"><i class="ti ti-map-pin"></i></span>
            <div class="flex-fill min-w-0">
                <h2 class="mb-1">{{ $location->name }}</h2>
                <div class="text-secondary">
                    <i class="ti ti-photo me-1"></i>{{ $photos->total() }} foto(s)
                </div>
                @if($location->description)
                    <p class="mt-2 mb-0">{{ $location->description }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

@if($photos->isEmpty())
    @include('partials.empty', [
        'icon' => 'ti-photo-off',
        'title' => 'Nenhuma foto neste local',
        'text' => 'Ou nenhuma foto foi ligada a este local ainda, ou as fotos daqui estão fora do seu acesso.',
    ])
@else
    @include('photos.partials.grid', ['photos' => $photos])

    <div class="mt-4">{{ $photos->links() }}</div>
@endif
@endsection
