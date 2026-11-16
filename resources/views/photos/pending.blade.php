@extends('layouts.tabler')

@section('page-title', 'Fotos sem dados')
@section('page-pretitle', 'Preenchimento')
@section('page-subtitle', $photos->total() . ' ' . ($photos->total() === 1 ? 'foto sem título, descrição, local, pessoas ou álbum' : 'fotos sem título, descrição, local, pessoas ou álbum'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item active" aria-current="page"><a href="#">Fotos sem dados</a></li>
@endsection

@section('content')
@if($photos->isEmpty())
    @include('partials.empty', [
        'icon' => 'ti-circle-check',
        'title' => 'Tudo preenchido',
        'text' => 'Nenhuma foto sem dados por aqui. Fotos novas sem título, descrição, local, pessoas ou álbum aparecem nesta lista.',
    ])
@else
    <div class="card">
        <div class="list-group list-group-flush">
            @foreach($photos as $photo)
                <a href="{{ route('photos.show', $photo) }}" class="list-group-item list-group-item-action">
                    {{ $photo->original_filename }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="mt-4">{{ $photos->links() }}</div>
@endif
@endsection
