{{--
    Moldura das Configurações no modelo do settings.html do Tabler: um card
    com a navegação das abas à esquerda e o conteúdo da aba à direita.
    Cada aba é uma página própria que estende esta e preenche
    "settings-tab" (nome na trilha) e "settings-content" (a coluna direita).
--}}
@extends('layouts.tabler')

@section('page-title', 'Configurações')
@section('page-pretitle', 'Administração')
@section('page-subtitle', 'Ajustes que valem para todo o acervo')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('settings.edit') }}">Configurações</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">@yield('settings-tab')</a></li>
@endsection

@section('content')
<div class="card">
    <div class="row g-0">
        <div class="col-12 col-md-3 border-end">
            <div class="card-body">
                <h4 class="subheader">Acervo</h4>
                <div class="list-group list-group-transparent">
                    <a href="{{ route('settings.edit') }}"
                       class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('settings.edit') ? 'active' : '' }}">
                        <i class="ti ti-adjustments me-2"></i> Geral
                    </a>
                </div>

                <h4 class="subheader mt-4">Segurança</h4>
                <div class="list-group list-group-transparent">
                    <a href="{{ route('settings.jschallenge') }}"
                       class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('settings.jschallenge') ? 'active' : '' }}">
                        <i class="ti ti-shield-check me-2"></i> Desafio JS
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-9 d-flex flex-column">
            @yield('settings-content')
        </div>
    </div>
</div>
@endsection
