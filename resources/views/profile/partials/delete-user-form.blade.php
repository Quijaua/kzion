<div class="alert alert-danger" role="alert">
    <div class="d-flex">
        <div class="me-2"><i class="ti ti-alert-triangle icon alert-icon"></i></div>
        <div>
            <h4 class="alert-title">Excluir a conta é definitivo</h4>
            <div class="text-secondary">
                Todos os dados vinculados a ela são apagados e não há como recuperar.
                Baixe antes o que quiser guardar.
            </div>
        </div>
    </div>
</div>

<button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#delete-account-modal">
    <i class="ti ti-trash me-1"></i> Excluir minha conta
</button>

<div class="modal modal-blur fade" id="delete-account-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')

                <div class="modal-header">
                    <h5 class="modal-title">Excluir a conta?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-secondary">
                        Confirme sua senha para excluir a conta permanentemente.
                    </p>
                    <div>
                        <label class="form-label" for="delete_password">Senha</label>
                        <input id="delete_password" name="password" type="password"
                               class="form-control @error('password', 'userDeletion') is-invalid @enderror"
                               placeholder="Sua senha" autocomplete="current-password">
                        @error('password', 'userDeletion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="ti ti-trash me-1"></i> Excluir conta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->userDeletion->isNotEmpty())
    @push('scripts')
        <script type="module">
            // A exclusão falhou (senha errada): reabre o modal já na aba correta.
            document.querySelector('[href="#tab-danger"]')?.click();
            window.bootstrap?.Modal.getOrCreateInstance('#delete-account-modal').show();
        </script>
    @endpush
@endif
