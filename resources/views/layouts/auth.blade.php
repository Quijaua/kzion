<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    @php($pageTitle = trim($__env->yieldContent('page-title')))
    <title>{{ $pageTitle ? $pageTitle.' · kzion' : 'kzion' }}</title>

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
<body class="d-flex flex-column bg-surface-secondary">
<div class="page page-center">
    <div class="container container-tight py-4">
        <div class="text-center mb-4">
            <a href="{{ url('/') }}" class="navbar-brand navbar-brand-autodark d-inline-flex align-items-center gap-2 text-decoration-none">
                <span class="avatar avatar-md bg-primary text-white"><i class="ti ti-photo-heart fs-2"></i></span>
                <span class="h1 mb-0">kzion - Acervo Familiar</span>
            </a>
        </div>

        <div class="card card-md">
            <div class="card-body">
                <h2 class="h2 text-center mb-1">@yield('heading')</h2>
                @hasSection('subheading')
                    <p class="text-secondary text-center mb-4">@yield('subheading')</p>
                @else
                    <div class="mb-4"></div>
                @endif

                @php($flash = session('status'))
                @if(is_string($flash) && ! in_array($flash, ['verification-link-sent', 'profile-updated', 'password-updated'], true))
                    <div class="alert alert-success" role="alert">
                        <div class="d-flex">
                            <div class="me-2"><i class="ti ti-circle-check icon alert-icon"></i></div>
                            <div>{{ $flash }}</div>
                        </div>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>

        @hasSection('footer')
            <div class="text-center text-secondary mt-3">@yield('footer')</div>
        @endif
    </div>
</div>
</body>
</html>
