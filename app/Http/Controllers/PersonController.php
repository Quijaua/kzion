<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PersonController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // A contagem PRECISA usar a mesma regra da listagem em /people/{id}.
        // Com `withCount('photos')` puro, o card prometia "3 fotos" e a página
        // da pessoa abria vazia: o total incluía fotos em que ela aparece ao
        // lado de alguém que o usuário não pode ver.
        $people = $user->visiblePeople()
            ->withCount(['photos as photos_count' => fn ($q) => $q->accessibleTo($user)])
            ->orderBy('name')
            ->get();

        return view('people.index', compact('people'));
    }

    public function create(): View
    {
        return view('people.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'death_date' => ['nullable', 'date', 'after_or_equal:birth_date'],
            'description' => ['nullable', 'string'],
        ]);

        $person = Person::create($data);

        // Sem isto a pessoa some da vista de quem acabou de criá-la: um usuário
        // comum só lista o que está no pivô `user_people`. Quem já vê a lista
        // completa não precisa do vínculo.
        if (! $request->user()->seesAllPeople()) {
            $request->user()->people()->syncWithoutDetaching([$person->id]);
        }

        return redirect()->route('people.show', $person)->with('status', 'Pessoa criada.');
    }

    public function show(Request $request, Person $person): View
    {
        abort_unless($request->user()->canSeePerson($person), 403);

        $photos = $person->accessiblePhotos($request->user())
            ->with(['people', 'exif'])
            ->latest('taken_at')->latest('id')
            ->paginate(24);

        return view('people.show', compact('person', 'photos'));
    }

    public function edit(Request $request, Person $person): View
    {
        abort_unless($request->user()->canSeePerson($person), 403);

        return view('people.edit', compact('person'));
    }

    public function update(Request $request, Person $person): RedirectResponse
    {
        abort_unless($request->user()->canSeePerson($person), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'death_date' => ['nullable', 'date', 'after_or_equal:birth_date'],
            'description' => ['nullable', 'string'],
        ]);

        $person->update($data);

        return redirect()->route('people.show', $person)->with('status', 'Pessoa atualizada.');
    }

    public function destroy(Request $request, Person $person): RedirectResponse
    {
        abort_unless($request->user()->canSeePerson($person), 403);

        $person->delete();

        return redirect()->route('people.index')->with('status', 'Pessoa excluída.');
    }
}
