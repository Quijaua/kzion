@extends('layouts.tabler')

@section('page-title', 'Upload em lote')
@section('page-pretitle', 'Enviar fotos')
@section('page-subtitle', 'Cada imagem é enviada assim que entra na fila')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('photos.index') }}">Fotos</a></li>
    <li class="breadcrumb-item"><a href="{{ route('photos.create') }}">Enviar</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">Em lote</a></li>
@endsection

@section('page-actions')
    <a href="{{ route('photos.create') }}" class="btn btn-ghost-secondary">
        <i class="ti ti-arrow-left me-1"></i> Voltar
    </a>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form id="bulk-form">
                    @csrf
                    <label class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="bulk-read-exif" checked>
                        <span class="form-check-label">Ler dados EXIF das imagens</span>
                        <span class="form-check-description">
                            Data, câmera e GPS gravados pela câmera. Desligue para scans, fotos de
                            WhatsApp ou imagens editadas, que normalmente não têm EXIF. Vale para as
                            imagens que entrarem na fila a partir de agora.
                        </span>
                    </label>
                    <input type="file" name="photos[]" id="bulk-upload" class="filepond" multiple
                           accept="image/jpeg,image/png,image/webp">
                </form>
            </div>
            <div class="card-footer d-flex align-items-center gap-2">
                <span class="text-secondary" data-upload-summary hidden>
                    <i class="ti ti-circle-check text-green me-1"></i>
                    <strong data-upload-count>0</strong> foto(s) enviada(s)
                </span>
                <span class="text-secondary" data-upload-duplicates-summary hidden>
                    <i class="ti ti-copy text-orange me-1"></i>
                    <strong data-upload-duplicates>0</strong> já estava(m) no acervo
                </span>
                <a href="{{ route('photos.index') }}" class="btn btn-primary ms-auto" id="bulk-done">
                    <i class="ti ti-circle-check me-1"></i> Concluir
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-borderless bg-surface-secondary">
            <div class="card-body">
                <h3 class="card-title"><i class="ti ti-info-circle me-1"></i> Como funciona</h3>
                <ul class="list-unstyled text-secondary mb-0 d-grid gap-2">
                    <li><i class="ti ti-point text-primary me-1"></i> Cada imagem sobe imediatamente e vira um registro de foto.</li>
                    <li><i class="ti ti-point text-primary me-1"></i> Título, pessoas e álbuns ficam para depois — edite foto a foto.</li>
                    <li><i class="ti ti-point text-primary me-1"></i> Data e câmera são lidas do EXIF automaticamente, quando existirem (pode desligar acima).</li>
                    <li><i class="ti ti-point text-primary me-1"></i> Removeu da fila antes de concluir? A foto é apagada do servidor.</li>
                    <li><i class="ti ti-point text-primary me-1"></i> <strong>Processando…</strong> quer dizer que o arquivo já chegou e o servidor está terminando. Só conta como enviada quando aparece <strong>Enviada</strong>; se a resposta demorar, o sistema confere sozinho se a foto foi salva.</li>
                    <li><i class="ti ti-point text-primary me-1"></i> <strong>Já está no acervo</strong>: essa mesma imagem já foi enviada antes e não foi duplicada.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
