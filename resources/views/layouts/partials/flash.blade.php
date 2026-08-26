@if(session('status') && session('status') !== 'profile-updated' && session('status') !== 'password-updated' && session('status') !== 'verification-link-sent')
    <div class="alert alert-success alert-dismissible mb-3" role="alert">
        <div class="d-flex">
            <div class="me-2"><i class="ti ti-circle-check icon alert-icon"></i></div>
            <div>{{ session('status') }}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
@endif

@if($errors->any() && $errors->count() > 0)
    <div class="alert alert-danger alert-dismissible mb-3" role="alert">
        <div class="d-flex">
            <div class="me-2"><i class="ti ti-alert-circle icon alert-icon"></i></div>
            <div>
                <h4 class="alert-title">Não foi possível salvar</h4>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
@endif
