@extends('layouts.tabler')

@section('page-title', 'Meu perfil')
@section('page-pretitle', 'Conta')
@section('page-subtitle', 'Dados de acesso e segurança da sua conta')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">Meu perfil</a></li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <span class="avatar avatar-xl bg-primary-lt mb-3">
                    {{ Str::of($user->name)->substr(0, 2)->upper() }}
                </span>
                <h3 class="mb-0">{{ $user->name }}</h3>
                <div class="text-secondary">{{ $user->email }}</div>
                <div class="mt-3">
                    <span class="badge bg-{{ $user->isAdmin() ? 'azure' : 'secondary' }}-lt">
                        {{ $user->isAdmin() ? 'Administrador' : 'Usuário' }}
                    </span>
                    <span class="badge bg-{{ $user->active ? 'green' : 'red' }}-lt">
                        {{ $user->active ? 'Ativo' : 'Inativo' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a href="#tab-profile" class="nav-link active" data-bs-toggle="tab" role="tab">
                            <i class="ti ti-user me-1"></i> Dados
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="#tab-password" class="nav-link" data-bs-toggle="tab" role="tab">
                            <i class="ti ti-lock me-1"></i> Senha
                        </a>
                    </li>
                    <li class="nav-item ms-auto" role="presentation">
                        <a href="#tab-danger" class="nav-link text-danger" data-bs-toggle="tab" role="tab">
                            <i class="ti ti-alert-triangle me-1"></i> Excluir conta
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane active show" id="tab-profile" role="tabpanel">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                    <div class="tab-pane" id="tab-password" role="tabpanel">
                        @include('profile.partials.update-password-form')
                    </div>
                    <div class="tab-pane" id="tab-danger" role="tabpanel">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
