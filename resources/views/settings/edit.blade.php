@extends('settings.layout')

@section('settings-tab', 'Geral')

@section('settings-content')
<form method="POST" action="{{ route('settings.update') }}" class="d-flex flex-column flex-fill">
    @csrf @method('PUT')

    <div class="card-body">
        <h2 class="mb-4">Geral</h2>

        <h3 class="card-title"><i class="ti ti-door-enter me-1"></i> Acesso</h3>
        <input type="hidden" name="registration_enabled" value="0">
        <label class="form-check form-switch form-switch-lg mb-0">
            <input class="form-check-input" type="checkbox" name="registration_enabled"
                   value="1" role="switch" @checked($registrationEnabled)>
            <span class="form-check-label">
                Permitir que visitantes criem a própria conta
                <span class="badge {{ $registrationEnabled ? 'bg-green-lt' : 'bg-secondary-lt' }} ms-1">
                    {{ $registrationEnabled ? 'ligado agora' : 'desligado agora' }}
                </span>
            </span>
            <span class="form-check-description">
                Ligado, a tela de login mostra o link <strong>Criar conta</strong> e qualquer
                pessoa com o endereço do acervo pode se cadastrar — mas entra sem acesso a
                nenhuma pessoa, então não vê foto alguma até um administrador conceder.
                Desligado, o link some e <code>/register</code> passa a responder 404, mesmo
                para quem souber o endereço de cor. As contas continuam sendo criadas em
                <a href="{{ route('users.index') }}">Usuários</a>.
            </span>
        </label>

        <h3 class="card-title mt-5"><i class="ti ti-users me-1"></i> Lista completa de pessoas</h3>
        <p class="card-subtitle">
            Ligado, o usuário passa a <strong>enxergar todos os nomes</strong> em Pessoas e a poder
            marcar qualquer um deles ao editar uma foto — útil para quem ajuda a catalogar o acervo.
            Isto <strong>não</strong> amplia as fotos que ele vê: elas continuam saindo das pessoas
            concedidas a ele em <a href="{{ route('users.index') }}">Usuários</a>, mais as que ele
            mesmo enviou.
        </p>

        @if($commonUsers->isEmpty())
            <div class="text-center text-secondary py-3">
                Nenhum usuário comum cadastrado. Administradores já veem todas as pessoas.
            </div>
        @else
            <div class="table-responsive border rounded">
                <table class="table table-vcenter card-table mb-0">
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

    <div class="card-footer bg-transparent mt-auto">
        <div class="btn-list justify-content-end">
            <a href="{{ route('settings.edit') }}" class="btn">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy me-1"></i> Salvar configurações
            </button>
        </div>
    </div>
</form>
@endsection
