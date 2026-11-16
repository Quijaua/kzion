@extends('layouts.tabler')

@php
    // ATENÇÃO: nesta view use SEMPRE o bloco de PHP com abertura e fechamento,
    // nunca a forma curta de uma linha. O Blade extrai os blocos de PHP com uma
    // regex não-gulosa antes de compilar; uma abertura curta solta se
    // emparelharia com o fechamento deste bloco e metade da view viraria PHP
    // cru. Pelo mesmo motivo, não escreva as diretivas literalmente nem dentro
    // de comentários.
    $filtrando = request()->hasAny(['name', 'people', 'year', 'month', 'undated', 'location', 'description', 'camera', 'album_id']);

    $advancedKeys = ['year', 'month', 'location', 'description', 'camera'];
    $advancedActive = collect($advancedKeys)->contains(fn ($k) => filled(request($k)));

    // Chips dos filtros ativos: cada um remove só o próprio filtro.
    $chips = [];

    if (filled(request('name'))) {
        $chips[] = ['label' => 'Nome', 'value' => request('name'), 'url' => request()->fullUrlWithQuery(['name' => null, 'page' => null])];
    }

    foreach ((array) request('people', []) as $selectedId) {
        $person = $people->firstWhere('id', (int) $selectedId);
        if (! $person) {
            continue;
        }
        $remaining = array_values(array_diff((array) request('people', []), [$selectedId]));
        $chips[] = [
            'label' => 'Pessoa',
            'value' => $person->name,
            'url' => request()->fullUrlWithQuery(['people' => $remaining ?: null, 'page' => null]),
        ];
    }

    if (filled(request('album_id')) && ($album = $albums->firstWhere('id', (int) request('album_id')))) {
        $chips[] = ['label' => 'Álbum', 'value' => $album->name, 'url' => request()->fullUrlWithQuery(['album_id' => null, 'page' => null])];
    }

    foreach (['year' => 'Ano', 'location' => 'Local', 'description' => 'Descrição', 'camera' => 'Câmera'] as $key => $label) {
        if (filled(request($key))) {
            $chips[] = ['label' => $label, 'value' => request($key), 'url' => request()->fullUrlWithQuery([$key => null, 'page' => null])];
        }
    }

    // Mês (da linha do tempo): 1..12, ou "unknown" = fotos que só têm o ano.
    $monthFilter = request('month');
    if ($monthFilter === 'unknown') {
        $chips[] = ['label' => 'Mês', 'value' => 'não informado', 'url' => request()->fullUrlWithQuery(['month' => null, 'page' => null])];
    } elseif (isset(\App\Models\Photo::MONTHS[(int) $monthFilter])) {
        $chips[] = ['label' => 'Mês', 'value' => \App\Models\Photo::MONTHS[(int) $monthFilter], 'url' => request()->fullUrlWithQuery(['month' => null, 'page' => null])];
    }

    if (request()->boolean('undated')) {
        $chips[] = ['label' => 'Data', 'value' => 'sem data', 'url' => request()->fullUrlWithQuery(['undated' => null, 'page' => null])];
    }
@endphp

@section('page-title', 'Fotos')
@section('page-pretitle', 'Acervo')
@section('page-subtitle')
    {{ $photos->total() }}
    @if($filtrando)
        {{ $photos->total() === 1 ? 'foto encontrada' : 'fotos encontradas' }} com os filtros atuais
    @else
        {{ $photos->total() === 1 ? 'foto' : 'fotos' }} no acervo
    @endif
@endsection

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">Fotos</a></li>
@endsection

@section('page-actions')
    <a href="{{ route('photos.create') }}" class="btn btn-primary">
        <i class="ti ti-upload me-1"></i> Enviar fotos
    </a>
@endsection

@section('content')

<form method="GET" action="{{ route('photos.index') }}" class="card mb-3">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-12 col-lg-4">
                <label class="form-label" for="filter-name">Nome da foto</label>
                <div class="input-icon">
                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                    <input id="filter-name" type="text" name="name" class="form-control"
                           value="{{ request('name') }}" placeholder="Buscar pelo título…">
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <label class="form-label" for="people-filter">Pessoas na foto</label>
                <select id="people-filter" name="people[]" class="form-select" multiple
                        data-searchable data-placeholder="Todas as pessoas">
                    @foreach($people as $p)
                        <option value="{{ $p->id }}" @selected(in_array($p->id, (array) request('people', [])))>{{ $p->name }}</option>
                    @endforeach
                </select>
                <div class="form-hint">A foto precisa conter <strong>todas</strong> as pessoas escolhidas.</div>
            </div>

            <div class="col-12 col-lg-4">
                <label class="form-label" for="album-filter">Álbum</label>
                <select id="album-filter" name="album_id" class="form-select" data-searchable data-placeholder="Todos os álbuns">
                    <option value=""></option>
                    @foreach($albums as $a)
                        <option value="{{ $a->id }}" @selected(request('album_id') == $a->id)>{{ $a->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="collapse mt-3 @if($advancedActive) show @endif" id="advanced-filters"
             data-expanded="{{ $advancedActive ? '1' : '0' }}">
            <div class="row g-2">
                <div class="col-6 col-md-3">
                    <label class="form-label" for="filter-year">Ano e mês</label>
                    <div class="input-group">
                        <input id="filter-year" type="text" inputmode="numeric" name="year" class="form-control"
                               value="{{ request('year') }}" placeholder="1987">
                        <select name="month" class="form-select" aria-label="Mês">
                            <option value="">Mês</option>
                            @foreach(\App\Models\Photo::MONTHS as $n => $nome)
                                <option value="{{ $n }}" @selected((string) request('month') === (string) $n)>{{ ucfirst($nome) }}</option>
                            @endforeach
                            <option value="unknown" @selected(request('month') === 'unknown')>Não informado</option>
                        </select>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="filter-location">Local</label>
                    <input id="filter-location" type="text" name="location" class="form-control"
                           value="{{ request('location') }}" placeholder="Cidade, praia, casa…">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="filter-description">Descrição</label>
                    <input id="filter-description" type="text" name="description" class="form-control"
                           value="{{ request('description') }}" placeholder="Palavra na descrição">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="filter-camera">Câmera</label>
                    <input id="filter-camera" type="text" name="camera" class="form-control"
                           value="{{ request('camera') }}" placeholder="Canon, iPhone…">
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-search me-1"></i> Pesquisar
            </button>
            <button type="button" class="btn" data-bs-toggle="collapse" data-bs-target="#advanced-filters"
                    aria-controls="advanced-filters" aria-expanded="{{ $advancedActive ? 'true' : 'false' }}">
                <i class="ti ti-adjustments-horizontal me-1"></i> Filtros avançados
            </button>
            @if($chips)
                <a href="{{ route('photos.index') }}" class="btn btn-ghost-secondary ms-auto">
                    <i class="ti ti-x me-1"></i> Limpar tudo
                </a>
            @endif
        </div>
    </div>
</form>

@if($chips)
    <div class="d-flex flex-wrap gap-2 mb-3" aria-label="Filtros ativos">
        @foreach($chips as $chip)
            <a href="{{ $chip['url'] }}" class="filter-chip" title="Remover este filtro">
                <span class="filter-chip__label">{{ $chip['label'] }}:</span>
                <span>{{ $chip['value'] }}</span>
                <i class="ti ti-x"></i>
            </a>
        @endforeach
    </div>
@endif

@if($photos->isEmpty())
    @include('partials.empty', [
        'icon' => $chips ? 'ti-mood-search' : 'ti-photo-off',
        'title' => $chips ? 'Nenhuma foto com esses filtros' : 'O acervo ainda está vazio',
        'text' => $chips
            ? 'Tente remover algum filtro ou ampliar o período da busca.'
            : 'Envie as primeiras fotos para começar o acervo da família.',
        'actionUrl' => $chips ? route('photos.index') : route('photos.create'),
        'actionLabel' => $chips ? 'Limpar filtros' : 'Enviar fotos',
        'actionIcon' => $chips ? 'ti-x' : 'ti-upload',
    ])
@else
    @include('photos.partials.grid', ['photos' => $photos])

    <div class="mt-4">{{ $photos->withQueryString()->links() }}</div>
@endif
@endsection
