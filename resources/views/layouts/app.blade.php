{{--
    Compatibilidade com o componente <x-app-layout> herdado do Breeze.
    O shell de verdade é layouts/tabler.blade.php — mantenha as mudanças lá.
--}}
@extends('layouts.tabler')

@isset($header)
    @section('page-title', strip_tags($header))
@endisset

@section('content')
    {{ $slot }}
@endsection
