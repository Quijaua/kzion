{{-- @param \App\Models\Album|null $album --}}
@php($album = $album ?? null)
@php($selectedPhotos = old('photo_ids', $album ? $album->photos->pluck('id')->all() : []))

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Dados do álbum</h3></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label required" for="name">Nome</label>
                    <input id="name" type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $album?->name) }}" placeholder="Ex.: Verão de 1998" required autofocus>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-0">
                    <label class="form-label" for="description">Descrição</label>
                    <textarea id="description" name="description" class="form-control" rows="4"
                              placeholder="Sobre o que é este álbum?">{{ old('description', $album?->description) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Fotos</h3></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="photo-ids">Fotos do álbum</label>
                    <select id="photo-ids" name="photo_ids[]" class="form-select" multiple
                            data-searchable data-placeholder="Busque e selecione as fotos">
                        @foreach($photos as $p)
                            <option value="{{ $p->id }}" @selected(in_array($p->id, (array) $selectedPhotos))>
                                {{ $p->title ?? $p->original_filename }}@if($p->taken_at) — {{ $p->taken_at_label }}@endif
                            </option>
                        @endforeach
                    </select>
                    <div class="form-hint">Digite para filtrar pelo título ou nome do arquivo.</div>
                </div>

                <div class="mb-0">
                    <label class="form-label" for="cover-photo-id">Foto de capa</label>
                    <select id="cover-photo-id" name="cover_photo_id" class="form-select"
                            data-searchable data-placeholder="Sem capa">
                        <option value=""></option>
                        @foreach($photos as $p)
                            <option value="{{ $p->id }}" @selected(old('cover_photo_id', $album?->cover_photo_id) == $p->id)>
                                {{ $p->title ?? $p->original_filename }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i> Salvar
                </button>
                <a href="{{ $album ? route('albums.show', $album) : route('albums.index') }}"
                   class="btn btn-ghost-secondary">Cancelar</a>
            </div>
        </div>
    </div>
</div>
