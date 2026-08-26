{{-- @param \App\Models\User|null $user --}}
@php($user = $user ?? null)
@php($selectedPeople = old('person_ids', $user ? $user->people->pluck('id')->all() : []))

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Dados de acesso</h3></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label required" for="name">Nome</label>
                    <input id="name" type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $user?->name) }}" required autofocus autocomplete="name">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label required" for="email">E-mail</label>
                    <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email', $user?->email) }}" required autocomplete="username">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label @if(! $user) required @endif" for="password">Senha</label>
                    <input id="password" type="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           minlength="8" @required(! $user) autocomplete="new-password"
                           placeholder="{{ $user ? 'Deixe em branco para manter a senha atual' : 'Ao menos 8 caracteres' }}">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row g-2 mb-0">
                    <div class="col-md-6">
                        <label class="form-label" for="role">Papel</label>
                        <select id="role" name="role" class="form-select @error('role') is-invalid @enderror">
                            <option value="user" @selected(old('role', $user?->role) === 'user')>Usuário comum</option>
                            <option value="admin" @selected(old('role', $user?->role) === 'admin')>Administrador</option>
                        </select>
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="active">Situação</label>
                        <select id="active" name="active" class="form-select">
                            <option value="1" @selected(old('active', $user?->active ?? true) == 1)>Ativo</option>
                            <option value="0" @selected(old('active', $user?->active ?? true) == 0)>Inativo</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Permissões</h3></div>
            <div class="card-body">
                <label class="form-label" for="person-ids">Pessoas que pode visualizar</label>
                <select id="person-ids" name="person_ids[]" class="form-select" multiple
                        data-searchable data-placeholder="Busque e selecione as pessoas">
                    @foreach($people as $p)
                        <option value="{{ $p->id }}" @selected(in_array($p->id, (array) $selectedPeople))>{{ $p->name }}</option>
                    @endforeach
                </select>

                @if($user?->all_people_access)
                    <div class="alert alert-warning mt-3 mb-0" role="alert">
                        <div class="d-flex">
                            <div class="me-2"><i class="ti ti-eye icon alert-icon"></i></div>
                            <div class="text-secondary">
                                Este usuário está com <strong>lista completa de pessoas</strong> ligada em
                                <a href="{{ route('settings.edit') }}">Configurações</a>: ele enxerga todos
                                os nomes e pode marcá-los nas fotos. As fotos que ele vê continuam saindo
                                da lista abaixo.
                            </div>
                        </div>
                    </div>
                @endif

                <div class="alert alert-info mt-3 mb-0" role="alert">
                    <div class="d-flex">
                        <div class="me-2"><i class="ti ti-info-circle icon alert-icon"></i></div>
                        <div class="text-secondary">
                            O usuário vê uma foto se tiver acesso a <strong>pelo menos uma</strong>
                            das pessoas marcadas nela — mesmo que apareçam outras. O que ele não
                            pode é abrir a página das pessoas fora desta lista. Além disso, ele
                            sempre vê <strong>as fotos que ele mesmo enviou</strong>, com ou sem
                            pessoas marcadas. Administradores veem tudo.
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i> Salvar
                </button>
                <a href="{{ route('users.index') }}" class="btn btn-ghost-secondary">Cancelar</a>
            </div>
        </div>
    </div>
</div>
