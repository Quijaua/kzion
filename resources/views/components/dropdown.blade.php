@props(['align' => 'end', 'trigger' => null])

<div {{ $attributes->merge(['class' => 'dropdown']) }}>
    <button type="button" class="btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
        {{ $trigger }}
    </button>
    <div class="dropdown-menu dropdown-menu-{{ $align }}">
        {{ $content ?? $slot }}
    </div>
</div>
