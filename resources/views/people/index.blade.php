@extends('layouts.tabler')

@section('page-title', 'Pessoas')
@section('page-pretitle', 'Acervo')
@section('page-subtitle', $people->count() . ' ' . ($people->count() === 1 ? 'pessoa cadastrada' : 'pessoas cadastradas'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">Pessoas</a></li>
@endsection

@section('page-actions')
    <a href="{{ route('people.create') }}" class="btn btn-primary">
        <i class="ti ti-user-plus me-1"></i> Nova pessoa
    </a>
@endsection

@section('content')
@if($people->isEmpty())
    @include('partials.empty', [
        'icon' => 'ti-users',
        'title' => 'Nenhuma pessoa cadastrada',
        'text' => 'Pessoas são as etiquetas das fotos e também controlam quem pode ver o quê.',
        'actionUrl' => route('people.create'),
        'actionLabel' => 'Cadastrar a primeira pessoa',
        'actionIcon' => 'ti-user-plus',
    ])
@else
    <div class="row row-cards">
        @foreach($people as $p)
            <div class="col-sm-6 col-lg-4 col-xl-3">
                <div class="card card-link card-link-pop h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <span class="avatar avatar-lg bg-primary-lt">
                                {{ Str::of($p->name)->substr(0, 2)->upper() }}
                            </span>
                            <div class="min-w-0">
                                <h3 class="card-title mb-0 text-truncate">
                                    <a href="{{ route('people.show', $p) }}" class="text-reset text-decoration-none">{{ $p->name }}</a>
                                </h3>
                                <div class="text-secondary">
                                    {{ $p->photos_count }} {{ $p->photos_count === 1 ? 'foto' : 'fotos' }}
                                </div>
                            </div>
                        </div>

                        @if($p->birth_date || $p->death_date)
                            <div class="text-secondary small mt-3">
                                <i class="ti ti-calendar me-1"></i>
                                {{ $p->birth_date?->format('Y') ?? '?' }}
                                @if($p->death_date) – {{ $p->death_date->format('Y') }} @endif
                            </div>
                        @endif
                    </div>
                    <div class="card-footer d-flex gap-1">
                        <a href="{{ route('people.show', $p) }}" class="btn btn-sm btn-ghost-secondary">
                            <i class="ti ti-photo me-1"></i> Ver fotos
                        </a>
                        <a href="{{ route('people.edit', $p) }}" class="btn btn-sm btn-ghost-secondary ms-auto"
                           aria-label="Editar {{ $p->name }}">
                            <i class="ti ti-edit"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
