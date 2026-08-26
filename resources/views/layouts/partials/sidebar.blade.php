@php
    $nav = [
        ['route' => 'dashboard',      'pattern' => 'dashboard',  'icon' => 'ti-layout-dashboard', 'label' => 'Painel'],
        ['route' => 'photos.index',   'pattern' => 'photos.*',   'icon' => 'ti-photo',            'label' => 'Fotos'],
        ['route' => 'people.index',   'pattern' => 'people.*',   'icon' => 'ti-users',            'label' => 'Pessoas'],
        ['route' => 'albums.index',   'pattern' => 'albums.*',   'icon' => 'ti-album',            'label' => 'Álbuns'],
    ];

    if (auth()->user()?->isAdmin()) {
        $nav[] = ['route' => 'users.index',    'pattern' => 'users.*',    'icon' => 'ti-user-shield', 'label' => 'Usuários'];
        $nav[] = ['route' => 'settings.edit',  'pattern' => 'settings.*', 'icon' => 'ti-settings',    'label' => 'Configurações'];
    }
@endphp

<aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu"
                aria-controls="sidebar-menu" aria-expanded="false" aria-label="Abrir menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="navbar-brand navbar-brand-autodark">
            <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none">
                <span class="avatar avatar-sm bg-primary text-white"><i class="ti ti-photo-heart"></i></span>
                <span class="fw-bold">Acervo Familiar</span>
            </a>
        </div>

        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                <li class="nav-item mb-2 px-2 d-none d-lg-block">
                    <a href="{{ route('photos.create') }}" class="btn btn-primary w-100">
                        <i class="ti ti-upload me-1"></i> Enviar fotos
                    </a>
                </li>

                @foreach($nav as $item)
                    <li class="nav-item {{ request()->routeIs($item['pattern']) ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route($item['route']) }}"
                           @if(request()->routeIs($item['pattern'])) aria-current="page" @endif>
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti {{ $item['icon'] }}"></i>
                            </span>
                            <span class="nav-link-title">{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach

                <li class="nav-item d-lg-none mt-2">
                    <a class="nav-link" href="{{ route('photos.create') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-upload"></i></span>
                        <span class="nav-link-title">Enviar fotos</span>
                    </a>
                </li>

                <li class="nav-item d-lg-none">
                    <a class="nav-link" href="{{ route('profile.edit') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-user"></i></span>
                        <span class="nav-link-title">Meu perfil</span>
                    </a>
                </li>

                <li class="nav-item d-lg-none">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="nav-link border-0 bg-transparent w-100 text-start">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="ti ti-logout"></i></span>
                            <span class="nav-link-title">Sair</span>
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</aside>
