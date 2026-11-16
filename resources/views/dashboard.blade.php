@extends('layouts.tabler')

@section('page-title', 'Painel')
@section('page-pretitle', 'Visão geral')
@section('page-subtitle', 'Bem-vindo de volta, ' . auth()->user()->name . '.')

@section('page-actions')
    <a href="{{ route('photos.pending') }}" class="btn" title="Fotos sem título, descrição, local, pessoas ou álbum">
        <i class="ti ti-forms me-1"></i> Fotos sem dados
        <span class="badge {{ $pendingCount ? 'bg-orange' : 'bg-green' }} text-white ms-2">{{ number_format($pendingCount, 0, ',', '.') }}</span>
    </a>
    <a href="{{ route('photos.create') }}" class="btn btn-primary">
        <i class="ti ti-upload me-1"></i> Enviar fotos
    </a>
@endsection

@section('content')
@php
    $cards = [
        ['url' => route('photos.index'), 'icon' => 'ti-photo', 'color' => 'primary', 'count' => $photoCount,  'label' => 'Fotos'],
        ['url' => route('people.index'), 'icon' => 'ti-users', 'color' => 'green',   'count' => $personCount, 'label' => 'Pessoas'],
        ['url' => route('albums.index'), 'icon' => 'ti-album', 'color' => 'yellow',  'count' => $albumCount,  'label' => 'Álbuns'],
    ];

    if (auth()->user()->isAdmin()) {
        $cards[] = ['url' => route('users.index'), 'icon' => 'ti-user-shield', 'color' => 'azure', 'count' => $userCount, 'label' => 'Usuários'];
    }
@endphp

<div class="row row-deck row-cards mb-3">
    @foreach($cards as $card)
        <div class="col-sm-6 col-lg-{{ count($cards) === 4 ? '3' : '4' }}">
            <a href="{{ $card['url'] }}" class="card card-sm card-link card-link-pop">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-{{ $card['color'] }}-lt avatar avatar-lg">
                                <i class="ti {{ $card['icon'] }} fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="h1 mb-0">{{ number_format($card['count'], 0, ',', '.') }}</div>
                            <div class="text-secondary">{{ $card['label'] }}</div>
                        </div>
                        <div class="col-auto text-secondary">
                            <i class="ti ti-chevron-right"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-clock me-1"></i> Fotos recentes</h3>
        <div class="card-actions">
            <a href="{{ route('photos.index') }}" class="btn btn-ghost-secondary btn-sm">
                Ver todas <i class="ti ti-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
    <div class="card-body">
        @if($recentPhotos->isEmpty())
            <div class="text-center py-4">
                <span class="empty-icon-circle"><i class="ti ti-photo-off"></i></span>
                <h3 class="mb-1">Nenhuma foto ainda</h3>
                <p class="text-secondary">Comece enviando as primeiras fotos do acervo.</p>
                <a href="{{ route('photos.create') }}" class="btn btn-primary">
                    <i class="ti ti-upload me-1"></i> Enviar fotos
                </a>
            </div>
        @else
            @include('photos.partials.grid', ['photos' => $recentPhotos])
        @endif
    </div>
</div>
@endsection
