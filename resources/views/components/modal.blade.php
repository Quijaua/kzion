@props(['name', 'show' => false, 'maxWidth' => 'lg'])

@php
    $sizes = ['sm' => 'modal-sm', 'md' => '', 'lg' => 'modal-lg', 'xl' => 'modal-xl', '2xl' => 'modal-xl'];
@endphp

<div class="modal modal-blur fade" id="{{ $name }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered {{ $sizes[$maxWidth] ?? '' }}" role="document">
        <div class="modal-content">
            {{ $slot }}
        </div>
    </div>
</div>

@if($show)
    @push('scripts')
        <script type="module">
            window.bootstrap?.Modal.getOrCreateInstance('#{{ $name }}').show();
        </script>
    @endpush
@endif
