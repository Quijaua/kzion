@extends('layouts.tabler')

@section('page-title', 'Configurações')
@section('page-pretitle', 'Administração')
@section('page-subtitle', 'Ajustes que valem para todo o acervo')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">Configurações</a></li>
@endsection

@section('content')
<form method="POST" action="{{ route('settings.update') }}">
    @csrf @method('PUT')

    <div class="row justify-content-center g-3">
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="ti ti-door-enter me-1"></i> Acesso</h3>
                </div>
                <div class="card-body">
                    <input type="hidden" name="registration_enabled" value="0">
                    <label class="form-check form-switch form-switch-lg mb-0">
                        <input class="form-check-input" type="checkbox" name="registration_enabled"
                               value="1" role="switch" @checked($registrationEnabled)>
                        <span class="form-check-label">Permitir que visitantes criem a própria conta</span>
                        <span class="form-check-description">
                            Ligado, a tela de login mostra o link <strong>Criar conta</strong> e qualquer
                            pessoa com o endereço do acervo pode se cadastrar — mas entra sem acesso a
                            nenhuma pessoa, então não vê foto alguma até um administrador conceder.
                            Desligado, o link some e <code>/register</code> passa a responder 404, mesmo
                            para quem souber o endereço de cor. As contas continuam sendo criadas em
                            <a href="{{ route('users.index') }}">Usuários</a>.
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="ti ti-users me-1"></i> Lista completa de pessoas</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info" role="alert">
                        <div class="d-flex">
                            <div class="me-2"><i class="ti ti-info-circle icon alert-icon"></i></div>
                            <div class="text-secondary">
                                Ligado, o usuário passa a <strong>enxergar todos os nomes</strong> em
                                Pessoas e a poder marcar qualquer um deles ao editar uma foto — útil para
                                quem ajuda a catalogar o acervo. Isto <strong>não</strong> amplia as fotos
                                que ele vê: elas continuam saindo das pessoas concedidas a ele em
                                <a href="{{ route('users.index') }}">Usuários</a>, mais as que ele mesmo enviou.
                            </div>
                        </div>
                    </div>

                    @if($commonUsers->isEmpty())
                        <div class="text-center text-secondary py-3">
                            Nenhum usuário comum cadastrado. Administradores já veem todas as pessoas.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-vcenter">
                                <thead>
                                    <tr>
                                        <th>Usuário</th>
                                        <th>Pessoas concedidas</th>
                                        <th class="w-1 text-nowrap">Vê a lista completa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($commonUsers as $u)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="avatar avatar-sm bg-primary-lt">
                                                        {{ Str::of($u->name)->substr(0, 2)->upper() }}
                                                    </span>
                                                    <div class="min-w-0">
                                                        <div class="fw-medium text-truncate">
                                                            <a href="{{ route('users.edit', $u) }}"
                                                               class="text-reset text-decoration-none">{{ $u->name }}</a>
                                                        </div>
                                                        <div class="text-secondary small text-truncate">{{ $u->email }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-secondary">
                                                {{ $u->people()->count() }}
                                            </td>
                                            <td>
                                                <input type="hidden" name="all_people_access[{{ $u->id }}]" value="0">
                                                <label class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                           name="all_people_access[{{ $u->id }}]" value="1"
                                                           aria-label="Lista completa de pessoas para {{ $u->name }}"
                                                           @checked($u->all_people_access)>
                                                </label>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="card">
                <div class="card-body d-flex align-items-center gap-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i> Salvar configurações
                    </button>
                    <span class="text-secondary">
                        Cadastro público está
                        <strong class="{{ $registrationEnabled ? 'text-green' : 'text-secondary' }}">
                            {{ $registrationEnabled ? 'ligado' : 'desligado' }}
                        </strong>
                        agora.
                    </span>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
