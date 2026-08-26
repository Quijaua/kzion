<p class="text-secondary">
    Use uma senha longa e única para manter a conta protegida.
</p>

<form method="post" action="{{ route('password.update') }}" novalidate>
    @csrf
    @method('put')

    <div class="mb-3">
        <label class="form-label" for="update_password_current_password">Senha atual</label>
        <input id="update_password_current_password" name="current_password" type="password"
               class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
               autocomplete="current-password">
        @error('current_password', 'updatePassword')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label class="form-label" for="update_password_password">Nova senha</label>
        <input id="update_password_password" name="password" type="password"
               class="form-control @error('password', 'updatePassword') is-invalid @enderror"
               autocomplete="new-password">
        @error('password', 'updatePassword')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label class="form-label" for="update_password_password_confirmation">Confirmar nova senha</label>
        <input id="update_password_password_confirmation" name="password_confirmation" type="password"
               class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror"
               autocomplete="new-password">
        @error('password_confirmation', 'updatePassword')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-flex align-items-center gap-3">
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i> Salvar
        </button>
        @if (session('status') === 'password-updated')
            <span class="text-green"><i class="ti ti-check"></i> Salvo</span>
        @endif
    </div>
</form>
