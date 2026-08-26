<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    @php($pageTitle = trim($__env->yieldContent('page-title')))
    <title>{{ $pageTitle ? $pageTitle.' · kzion' : 'kzion' }}</title>

    {{-- Aplica o tema antes da primeira pintura para não piscar a paleta errada. --}}
    <script>
        (function () {
            try {
                var choice = localStorage.getItem('kzion-theme') || 'auto';
                var dark = choice === 'dark' || (choice === 'auto' &&
                    window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.dataset.bsTheme = dark ? 'dark' : 'light';
                document.documentElement.dataset.themeChoice = choice;
            } catch (e) { /* storage bloqueado: segue no tema claro */ }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="page">
    @include('layouts.partials.sidebar')

    <div class="page-wrapper">
        <header class="navbar navbar-expand-md d-print-none sticky-top">
            <div class="container-xl">
                <div class="me-3 d-none d-md-block">
                    @hasSection('breadcrumbs')
                        <ol class="breadcrumb breadcrumb-arrows mb-0" aria-label="Trilha de navegação">
                            @yield('breadcrumbs')
                        </ol>
                    @endif
                </div>

                <div class="navbar-nav flex-row order-md-last ms-auto">
                    <div class="nav-item dropdown me-2">
                        <a href="#" class="nav-link px-0" data-bs-toggle="dropdown" aria-label="Tema"
                           title="Aparência" data-bs-auto-close="outside">
                            <i class="ti ti-sun-moon fs-2"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <h6 class="dropdown-header">Aparência</h6>
                            <button type="button" class="dropdown-item" data-theme-value="light">
                                <i class="ti ti-sun me-2"></i> Claro
                            </button>
                            <button type="button" class="dropdown-item" data-theme-value="dark">
                                <i class="ti ti-moon me-2"></i> Escuro
                            </button>
                            <button type="button" class="dropdown-item" data-theme-value="auto">
                                <i class="ti ti-device-desktop me-2"></i> Seguir o sistema
                            </button>
                        </div>
                    </div>

                    @auth
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link d-flex lh-1 p-0 px-2" data-bs-toggle="dropdown"
                               aria-label="Menu do usuário">
                                <span class="avatar avatar-sm bg-primary-lt">{{ Str::of(auth()->user()->name)->substr(0, 2)->upper() }}</span>
                                <div class="d-none d-xl-block ps-2">
                                    <div>{{ auth()->user()->name }}</div>
                                    <div class="mt-1 small text-secondary">
                                        {{ auth()->user()->isAdmin() ? 'Administrador' : 'Usuário' }}
                                    </div>
                                </div>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                                <a href="{{ route('profile.edit') }}" class="dropdown-item">
                                    <i class="ti ti-user me-2"></i> Meu perfil
                                </a>
                                <div class="dropdown-divider"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="ti ti-logout me-2"></i> Sair
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endauth
                </div>
            </div>
        </header>

        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        @hasSection('page-pretitle')
                            <div class="page-pretitle">@yield('page-pretitle')</div>
                        @endif
                        <h2 class="page-title">@yield('page-title', 'Acervo Familiar')</h2>
                        @hasSection('page-subtitle')
                            <div class="text-secondary mt-1">@yield('page-subtitle')</div>
                        @endif
                    </div>
                    @hasSection('page-actions')
                        <div class="col-auto ms-auto d-print-none">
                            <div class="btn-list">
                                @yield('page-actions')
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                @include('layouts.partials.flash')
                @yield('content')
            </div>
        </div>

        <footer class="footer footer-transparent d-print-none">
            <div class="container-xl">
                <div class="row text-center align-items-center flex-row-reverse">
                    <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                        <span class="text-secondary">Acervo Familiar &copy; {{ date('Y') }}</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>
@stack('scripts')
</body>
</html>
