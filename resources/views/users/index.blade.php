@extends('layouts.tabler')

@section('page-title', 'Usuários')
@section('page-pretitle', 'Administração')
@section('page-subtitle', 'Quem entra no acervo e quais pessoas cada um enxerga')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">Usuários</a></li>
@endsection

@section('page-actions')
    <a href="{{ route('users.create') }}" class="btn btn-primary">
        <i class="ti ti-user-plus me-1"></i> Novo usuário
    </a>
@endsection

@section('content')
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Papel</th>
                    <th>Status</th>
                    <th>Pessoas visíveis</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar avatar-sm bg-primary-lt">
                                    {{ Str::of($u->name)->substr(0, 2)->upper() }}
                                </span>
                                <div class="min-w-0">
                                    <div class="fw-medium text-truncate">{{ $u->name }}</div>
                                    <div class="text-secondary small text-truncate">{{ $u->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-{{ $u->isAdmin() ? 'azure' : 'secondary' }}-lt">
                                {{ $u->isAdmin() ? 'Administrador' : 'Usuário' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $u->active ? 'green' : 'red' }}-lt">
                                {{ $u->active ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="text-secondary">
                            @if($u->isAdmin())
                                <span title="Administradores veem todas as fotos">Todas</span>
                            @elseif($u->people->isEmpty())
                                <span class="text-danger">Nenhuma</span>
                            @else
                                {{ $u->people->count() }} pessoa(s)
                            @endif
                            @if(! $u->isAdmin() && $u->all_people_access)
                                <span class="badge bg-azure-lt ms-1"
                                      title="Lista todos os nomes e pode marcá-los, mas isso não amplia as fotos que ele vê">
                                    lista completa
                                </span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-list flex-nowrap justify-content-end">
                                <a href="{{ route('users.edit', $u) }}" class="btn btn-sm btn-ghost-secondary"
                                   aria-label="Editar {{ $u->name }}">
                                    <i class="ti ti-edit"></i>
                                </a>
                                @if($u->id !== auth()->id())
                                    <form method="POST" action="{{ route('users.destroy', $u) }}" class="d-inline"
                                          data-confirm="Excluir o usuário “{{ $u->name }}”?"
                                          data-confirm-action="Excluir usuário">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-ghost-danger" aria-label="Excluir {{ $u->name }}">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
