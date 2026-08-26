{{--
    Estado vazio padronizado.

    @param string      $icon      classe do ícone Tabler (ex.: 'ti-photo-off')
    @param string      $title
    @param string|null $text
    @param string|null $actionUrl
    @param string|null $actionLabel
    @param string|null $actionIcon
--}}
<div class="card card-borderless">
    <div class="card-body text-center py-5">
        <span class="empty-icon-circle"><i class="ti {{ $icon ?? 'ti-inbox' }}"></i></span>
        <h3 class="mb-1">{{ $title }}</h3>
        @isset($text)
            <p class="text-secondary mb-0">{{ $text }}</p>
        @endisset
        @isset($actionUrl)
            <div class="mt-3">
                <a href="{{ $actionUrl }}" class="btn btn-primary">
                    <i class="ti {{ $actionIcon ?? 'ti-plus' }} me-1"></i> {{ $actionLabel }}
                </a>
            </div>
        @endisset
    </div>
</div>
