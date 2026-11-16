@extends('layouts.tabler')

@section('page-title', (string) $year)
@section('page-pretitle', 'Linha do tempo')
@section('page-subtitle', $total . ' ' . ($total === 1 ? 'foto visível para você' : 'fotos visíveis para você'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('timeline.index') }}">Linha do tempo</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">{{ $year }}</a></li>
@endsection

@section('page-actions')
    @if($prevYear)
        <a href="{{ route('timeline.year', $prevYear) }}" class="btn" title="Ano anterior com fotos">
            <i class="ti ti-chevron-left me-1"></i> {{ $prevYear }}
        </a>
    @endif
    @if($total)
        <a href="{{ route('photos.index', ['year' => $year]) }}" class="btn btn-primary">
            <i class="ti ti-photo me-1"></i> Todas as fotos de {{ $year }}
        </a>
    @endif
    @if($nextYear)
        <a href="{{ route('timeline.year', $nextYear) }}" class="btn" title="Próximo ano com fotos">
            {{ $nextYear }} <i class="ti ti-chevron-right ms-1"></i>
        </a>
    @endif
@endsection

@section('content')
@if(! $total)
    @include('partials.empty', [
        'icon' => 'ti-calendar-off',
        'title' => 'Nenhuma foto de ' . $year,
        'text' => 'Ou não há fotos deste ano no acervo, ou elas estão fora do seu acesso.',
    ])
@else
    <div class="row row-cards">
        @foreach(\App\Models\Photo::MONTHS as $n => $nome)
            @php($m = $months[$n] ?? null)
            <div class="col-6 col-sm-4 col-lg-3 col-xl-2">
                @if($m)
                    @php($cover = $covers[$m->cover_id] ?? null)
                    <a href="{{ route('photos.index', ['year' => $year, 'month' => $n]) }}"
                       class="card card-link card-link-pop text-decoration-none h-100">
                        <div class="timeline-cover">
                            @if($cover)
                                <img src="{{ route('photos.thumbnail', $cover) }}" alt="" loading="lazy">
                            @endif
                        </div>
                        <div class="card-body p-2 px-3">
                            <div class="h3 mb-0 text-reset">{{ ucfirst($nome) }}</div>
                            <div class="text-secondary small">{{ $m->total }} {{ (int) $m->total === 1 ? 'foto' : 'fotos' }}</div>
                        </div>
                    </a>
                @else
                    <div class="card h-100 opacity-50" aria-disabled="true">
                        <div class="timeline-cover"></div>
                        <div class="card-body p-2 px-3">
                            <div class="h3 mb-0">{{ ucfirst($nome) }}</div>
                            <div class="text-secondary small">sem fotos</div>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach

        @if($unknownMonth)
            <div class="col-6 col-sm-4 col-lg-3 col-xl-2">
                <a href="{{ route('photos.index', ['year' => $year, 'month' => 'unknown']) }}"
                   class="card card-link card-link-pop text-decoration-none h-100">
                    <div class="timeline-cover d-flex align-items-center justify-content-center">
                        <i class="ti ti-calendar-question text-secondary" style="font-size: 2.5rem"></i>
                    </div>
                    <div class="card-body p-2 px-3">
                        <div class="h3 mb-0 text-reset">Mês não informado</div>
                        <div class="text-secondary small">{{ $unknownMonth }} {{ $unknownMonth === 1 ? 'foto' : 'fotos' }} só com o ano</div>
                    </div>
                </a>
            </div>
        @endif
    </div>
@endif
@endsection
