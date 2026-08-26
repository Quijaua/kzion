<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>

<form method="post" action="{{ route('profile.update') }}" novalidate>
    @csrf
    @method('patch')

    <div class="mb-3">
        <label class="form-label" for="name">Nome</label>
        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}"
               class="form-control @error('name') is-invalid @enderror"
               required autofocus autocomplete="name">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label class="form-label" for="email">E-mail</label>
        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}"
               class="form-control @error('email') is-invalid @enderror"
               required autocomplete="username">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="form-hint">
                Seu e-mail ainda não foi verificado.
                <button form="send-verification" class="btn btn-link p-0 align-baseline">
                    Reenviar o e-mail de verificação
                </button>
            </div>
            @if (session('status') === 'verification-link-sent')
                <div class="text-green mt-1">Um novo link de verificação foi enviado.</div>
            @endif
        @endif
    </div>

    <div class="d-flex align-items-center gap-3">
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i> Salvar
        </button>
        @if (session('status') === 'profile-updated')
            <span class="text-green"><i class="ti ti-check"></i> Salvo</span>
        @endif
    </div>
</form>
