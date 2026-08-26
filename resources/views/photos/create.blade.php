@extends('layouts.tabler')

@section('page-title', 'Enviar fotos')
@section('page-pretitle', 'Acervo')
@section('page-subtitle', 'Escolha como quer enviar as imagens')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('photos.index') }}">Fotos</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">Enviar</a></li>
@endsection

@section('content')
<div class="row row-deck row-cards justify-content-center">
    <div class="col-md-6 col-lg-5">
        <a href="{{ route('photos.create', ['mode' => 'single']) }}" class="card card-link card-link-pop">
            <div class="card-body text-center py-4">
                <span class="empty-icon-circle"><i class="ti ti-photo-plus"></i></span>
                <h3 class="mb-2">Foto única</h3>
                <p class="text-secondary mb-0">
                    Envie uma foto e preencha título, descrição, pessoas e álbum agora mesmo.
                </p>
            </div>
            <div class="card-footer text-center text-primary">
                Começar <i class="ti ti-arrow-right ms-1"></i>
            </div>
        </a>
    </div>

    <div class="col-md-6 col-lg-5">
        <a href="{{ route('photos.create', ['mode' => 'bulk']) }}" class="card card-link card-link-pop">
            <div class="card-body text-center py-4">
                <span class="empty-icon-circle"><i class="ti ti-photos"></i></span>
                <h3 class="mb-2">Upload em lote</h3>
                <p class="text-secondary mb-0">
                    Arraste várias fotos de uma vez. Os metadados ficam para depois, foto a foto.
                </p>
            </div>
            <div class="card-footer text-center text-primary">
                Começar <i class="ti ti-arrow-right ms-1"></i>
            </div>
        </a>
    </div>
</div>
@endsection
