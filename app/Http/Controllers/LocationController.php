<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Locais das fotos. Diferente de pessoas, local não dá acesso a nada: a lista
 * de locais é a mesma para todos, e o que cada um vê dentro de um local passa
 * pelo mesmo `Photo::scopeAccessibleTo()` do resto do acervo.
 */
class LocationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Contagem pela mesma regra da página do local, senão o card promete
        // fotos que a página não mostra.
        $locations = Location::query()
            ->withCount(['photos as photos_count' => fn ($q) => $q->accessibleTo($user)])
            ->orderBy('name')
            ->get();

        return view('locations.index', compact('locations'));
    }

    public function create(): View
    {
        return view('locations.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $location = Location::create($this->validated($request));

        return redirect()->route('locations.show', $location)->with('status', 'Local criado.');
    }

    public function show(Request $request, Location $location): View
    {
        $photos = $location->accessiblePhotos($request->user())
            ->with(['people', 'exif'])
            ->latest('taken_at')->latest('id')
            ->paginate(24);

        $canManage = $location->canBeManagedBy($request->user());

        return view('locations.show', compact('location', 'photos', 'canManage'));
    }

    public function edit(Request $request, Location $location): View
    {
        abort_unless($location->canBeManagedBy($request->user()), 403);

        return view('locations.edit', compact('location'));
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        abort_unless($location->canBeManagedBy($request->user()), 403);

        $location->update($this->validated($request, $location));

        return redirect()->route('locations.show', $location)->with('status', 'Local atualizado.');
    }

    public function destroy(Request $request, Location $location): RedirectResponse
    {
        abort_unless($location->canBeManagedBy($request->user()), 403);

        // `nullOnDelete()` na FK: as fotos ficam, só perdem o local.
        $location->delete();

        return redirect()->route('locations.index')->with('status', 'Local excluído.');
    }

    private function validated(Request $request, ?Location $location = null): array
    {
        $request->merge(['name' => trim(preg_replace('/\s+/u', ' ', (string) $request->input('name')))]);

        // Sem diferenciar maiúsculas: no MariaDB a collation já faz isso, mas no
        // SQLite "Campinas" e "campinas" passariam como locais diferentes.
        $duplicado = function (string $attribute, mixed $value, \Closure $fail) use ($location) {
            $existe = Location::query()
                ->whereRaw('lower(name) = ?', [mb_strtolower($value)])
                ->when($location, fn ($q) => $q->whereKeyNot($location->id))
                ->exists();

            if ($existe) {
                $fail('Já existe um local com esse nome.');
            }
        };

        return $request->validate([
            'name' => ['required', 'string', 'max:255', $duplicado],
            'description' => ['nullable', 'string'],
        ]);
    }
}
