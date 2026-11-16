{{-- @param \App\Models\Location|null $location --}}
@php($location = $location ?? null)

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label required" for="name">Nome</label>
                    <input id="name" type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $location?->name) }}" placeholder="Ex.: Campinas, Sítio do vô, Praia Grande"
                           maxlength="255" required autofocus>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-0">
                    <label class="form-label" for="description">Descrição</label>
                    <textarea id="description" name="description" class="form-control" rows="4"
                              placeholder="Endereço, época, histórias do lugar…">{{ old('description', $location?->description) }}</textarea>
                </div>
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i> Salvar
                </button>
                <a href="{{ $location ? route('locations.show', $location) : route('locations.index') }}"
                   class="btn btn-ghost-secondary">Cancelar</a>
            </div>
        </div>
    </div>
</div>
