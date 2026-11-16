@extends('layouts.tabler')

@section('page-title', 'Locais')
@section('page-pretitle', 'Acervo')
@section('page-subtitle', $locations->count() . ' ' . ($locations->count() === 1 ? 'local cadastrado' : 'locais cadastrados'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">Locais</a></li>
@endsection

@section('page-actions')
    <a href="{{ route('locations.create') }}" class="btn btn-primary">
        <i class="ti ti-map-pin-plus me-1"></i> Novo local
    </a>
@endsection

@section('content')
@if($locations->isEmpty())
    @include('partials.empty', [
        'icon' => 'ti-map-pin',
        'title' => 'Nenhum local cadastrado',
        'text' => 'Cadastre cidades, casas, praias… e escolha o local ao enviar ou editar cada foto.',
        'actionUrl' => route('locations.create'),
        'actionLabel' => 'Cadastrar o primeiro local',
        'actionIcon' => 'ti-map-pin-plus',
    ])
@else
    <div class="row row-cards">
        @foreach($locations as $l)
            <div class="col-sm-6 col-lg-4 col-xl-3">
                <div class="card card-link card-link-pop h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <span class="avatar avatar-lg bg-green-lt"><i class="ti ti-map-pin"></i></span>
                            <div class="min-w-0">
                                <h3 class="card-title mb-0 text-truncate">
                                    <a href="{{ route('locations.show', $l) }}" class="text-reset text-decoration-none">{{ $l->name }}</a>
                                </h3>
                                <div class="text-secondary">
                                    {{ $l->photos_count }} {{ $l->photos_count === 1 ? 'foto' : 'fotos' }}
                                </div>
                            </div>
                        </div>

                        @if($l->description)
                            <div class="text-secondary small mt-3 text-truncate" title="{{ $l->description }}">
                                {{ $l->description }}
                            </div>
                        @endif
                    </div>
                    <div class="card-footer d-flex gap-1">
                        <a href="{{ route('locations.show', $l) }}" class="btn btn-sm btn-ghost-secondary">
                            <i class="ti ti-photo me-1"></i> Ver fotos
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
