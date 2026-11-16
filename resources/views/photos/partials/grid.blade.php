{{--
    Grade de fotos reutilizável.

    @param iterable $photos
    @param bool     $lightbox  liga o visualizador em tela cheia (padrão: true)
--}}
@php($lightbox = $lightbox ?? true)

<div class="row g-3" @if($lightbox) data-lightbox-gallery @endif>
    @foreach($photos as $photo)
        @php($label = $photo->title ?? $photo->original_filename)
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="photo-tile-wrapper position-relative">
                <a href="{{ route('photos.show', $photo) }}" class="photo-tile" title="{{ $label }}">
                    <img src="{{ route('photos.thumbnail', $photo) }}" alt="{{ $label }}" loading="lazy">
                    <span class="photo-tile__overlay">
                        <span class="photo-tile__title d-block">{{ $label }}</span>
                        <span class="photo-tile__meta d-block">
                            @if($photo->taken_at)
                                {{ $photo->taken_at_label }}
                            @endif
                            @if($photo->relationLoaded('people') && $photo->people->isNotEmpty())
                                · {{ $photo->people->pluck('name')->join(', ') }}
                            @endif
                        </span>
                    </span>
                </a>

                @if($lightbox)
                    <button type="button" class="photo-tile__zoom"
                            data-lightbox="{{ route('photos.file', $photo) }}"
                            data-lightbox-caption="{{ $label }}"
                            aria-label="Ampliar {{ $label }}">
                        <i class="ti ti-maximize"></i>
                    </button>
                @endif
            </div>
        </div>
    @endforeach
</div>
