@extends('layouts.tabler')

@section('page-title', 'Linha do tempo')
@section('page-pretitle', 'Acervo')
@section('page-subtitle', $total . ' ' . ($total === 1 ? 'foto com data' : 'fotos com data') . ($undated ? ' · ' . $undated . ' sem data' : ''))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">Linha do tempo</a></li>
@endsection

@section('content')
@if($decades->isEmpty() && ! $undated)
    @include('partials.empty', [
        'icon' => 'ti-timeline',
        'title' => 'Nenhuma foto por aqui ainda',
        'text' => 'A linha do tempo usa a data de cada foto — a que você preenche na edição ou a que vem da câmera.',
    ])
@else
    @foreach($decades as $decade => $years)
        <h2 class="subheader mt-2 mb-3">Anos {{ $decade }}</h2>
        <div class="row row-cards mb-4">
            @foreach($years as $y)
                @php($cover = $covers[$y->cover_id] ?? null)
                <div class="col-6 col-sm-4 col-md-3 col-xl-2">
                    <a href="{{ route('timeline.year', $y->year) }}" class="card card-link card-link-pop text-decoration-none h-100">
                        <div class="timeline-cover">
                            @if($cover)
                                <img src="{{ route('photos.thumbnail', $cover) }}" alt="" loading="lazy">
                            @endif
                        </div>
                        <div class="card-body p-2 px-3">
                            <div class="h2 mb-0 text-reset">{{ $y->year }}</div>
                            <div class="text-secondary small">{{ $y->total }} {{ (int) $y->total === 1 ? 'foto' : 'fotos' }}</div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endforeach

    @if($undated)
        <h2 class="subheader mt-2 mb-3">Sem data</h2>
        <div class="row row-cards">
            <div class="col-6 col-sm-4 col-md-3 col-xl-2">
                <a href="{{ route('photos.index', ['undated' => 1]) }}" class="card card-link card-link-pop text-decoration-none h-100">
                    <div class="timeline-cover d-flex align-items-center justify-content-center">
                        <i class="ti ti-calendar-question text-secondary" style="font-size: 2.5rem"></i>
                    </div>
                    <div class="card-body p-2 px-3">
                        <div class="h3 mb-0 text-reset">Sem data</div>
                        <div class="text-secondary small">{{ $undated }} {{ $undated === 1 ? 'foto' : 'fotos' }}</div>
                    </div>
                </a>
            </div>
        </div>
    @endif
@endif
@endsection
