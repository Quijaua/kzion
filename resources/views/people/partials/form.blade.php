{{-- @param \App\Models\Person|null $person --}}
@php($person = $person ?? null)

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label required" for="name">Nome</label>
                    <input id="name" type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $person?->name) }}" placeholder="Nome completo" required autofocus>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="birth_date">Nascimento</label>
                        <input id="birth_date" type="date" name="birth_date"
                               class="form-control @error('birth_date') is-invalid @enderror"
                               value="{{ old('birth_date', $person?->birth_date?->format('Y-m-d')) }}">
                        @error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="death_date">Falecimento</label>
                        <input id="death_date" type="date" name="death_date"
                               class="form-control @error('death_date') is-invalid @enderror"
                               value="{{ old('death_date', $person?->death_date?->format('Y-m-d')) }}">
                        @error('death_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-0">
                    <label class="form-label" for="description">Descrição</label>
                    <textarea id="description" name="description" class="form-control" rows="4"
                              placeholder="Parentesco, apelidos, histórias…">{{ old('description', $person?->description) }}</textarea>
                </div>
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i> Salvar
                </button>
                <a href="{{ $person ? route('people.show', $person) : route('people.index') }}"
                   class="btn btn-ghost-secondary">Cancelar</a>
            </div>
        </div>
    </div>
</div>
