{{--
    Data da foto em três campos: ano, mês e dia. Só o ano é obrigatório para
    haver data; mês e dia ficam em branco quando não se sabe.
    @param array $parts ['year' => ?int, 'month' => ?int, 'day' => ?int]
    @param string|null $hint
--}}
<label class="form-label" for="taken_year">Data da foto</label>
<div class="row g-1">
    <div class="col-3">
        <input id="taken_year" type="number" name="taken_year" min="1800" max="{{ now()->year + 1 }}" step="1"
               class="form-control @error('taken_year') is-invalid @enderror" placeholder="Ano"
               aria-label="Ano" value="{{ old('taken_year', $parts['year']) }}">
    </div>
    <div class="col-5">
        <select name="taken_month" class="form-select @error('taken_month') is-invalid @enderror" aria-label="Mês">
            <option value="">Mês</option>
            @foreach(\App\Models\Photo::MONTHS as $n => $nome)
                <option value="{{ $n }}" @selected((string) old('taken_month', $parts['month']) === (string) $n)>{{ ucfirst($nome) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-4">
        <select name="taken_day" class="form-select @error('taken_day') is-invalid @enderror" aria-label="Dia">
            <option value="">Dia</option>
            @for($d = 1; $d <= 31; $d++)
                <option value="{{ $d }}" @selected((string) old('taken_day', $parts['day']) === (string) $d)>{{ $d }}</option>
            @endfor
        </select>
    </div>
</div>
@foreach(['taken_year', 'taken_month', 'taken_day'] as $campo)
    @error($campo)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
@endforeach
<div class="form-hint">
    Só sabe o ano? Deixe mês e dia em branco — a foto entra na linha do tempo só pelo ano.
    @isset($hint) {{ $hint }} @endisset
</div>
