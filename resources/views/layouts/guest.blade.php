{{--
    Compatibilidade com o componente <x-guest-layout> herdado do Breeze.
    O shell de verdade é layouts/auth.blade.php — mantenha as mudanças lá.
--}}
@extends('layouts.auth')

@section('content')
    {{ $slot }}
@endsection
