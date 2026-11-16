@extends('layouts.tabler')

@section('page-title', $person->name)
@section('page-pretitle', 'Pessoa')
@section('page-subtitle', $photos->total() . ' ' . ($photos->total() === 1 ? 'foto visível para você' : 'fotos visíveis para você'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('people.index') }}">Pessoas</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">{{ Str::limit($person->name, 30) }}</a></li>
@endsection

@section('page-actions')
    <a href="{{ route('people.create') }}" class="btn">
        <i class="ti ti-user-plus me-1"></i> Nova pessoa
    </a>
    <a href="{{ route('photos.index', ['people' => [$person->id]]) }}" class="btn">
        <i class="ti ti-search me-1"></i> Buscar com esta pessoa
    </a>
    <a href="{{ route('people.edit', $person) }}" class="btn btn-primary">
        <i class="ti ti-edit me-1"></i> Editar
    </a>
    <form method="POST" action="{{ route('people.destroy', $person) }}" class="d-inline"
          data-confirm="Excluir “{{ $person->name }}”?"
          data-confirm-text="As marcações desta pessoa nas fotos também serão removidas."
          data-confirm-action="Excluir pessoa">
        @csrf @method('DELETE')
        <button class="btn btn-icon btn-ghost-danger" aria-label="Excluir pessoa" title="Excluir pessoa">
            <i class="ti ti-trash"></i>
        </button>
    </form>
@endsection

@section('content')
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <span class="avatar avatar-xl bg-primary-lt">{{ Str::of($person->name)->substr(0, 2)->upper() }}</span>
            <div class="flex-fill min-w-0">
                <h2 class="mb-1">{{ $person->name }}</h2>
                <div class="text-secondary d-flex flex-wrap gap-3">
                    @if($person->birth_date)
                        <span><i class="ti ti-cake me-1"></i>{{ $person->birth_date->format('d/m/Y') }}</span>
                    @endif
                    @if($person->death_date)
                        <span><i class="ti ti-grave-2 me-1"></i>{{ $person->death_date->format('d/m/Y') }}</span>
                    @endif
                    <span><i class="ti ti-photo me-1"></i>{{ $photos->total() }} foto(s)</span>
                </div>
                @if($person->description)
                    <p class="mt-2 mb-0">{{ $person->description }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

@if($photos->isEmpty())
    @include('partials.empty', [
        'icon' => 'ti-photo-off',
        'title' => 'Nenhuma foto para esta pessoa',
        'text' => 'Ou ela ainda não foi marcada em nenhuma foto, ou as fotos onde ela aparece estão fora do seu acesso.',
    ])
@else
    @include('photos.partials.grid', ['photos' => $photos])

    <div class="mt-4">{{ $photos->links() }}</div>
@endif
@endsection
