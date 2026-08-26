<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Person;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlbumController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Tanto a capa quanto a contagem passam pelo acesso do usuário: sem
        // isso o card anuncia fotos que o álbum não vai mostrar, e a capa vira
        // uma imagem quebrada (a rota do thumbnail devolve 404).
        $albums = Album::query()
            ->withCount(['photos as photos_count' => fn ($q) => $q->accessibleTo($user)])
            ->with(['coverPhoto' => fn ($q) => $q->accessibleTo($user)])
            ->orderBy('name')
            ->get();

        return view('albums.index', compact('albums'));
    }

    public function create(Request $request): View
    {
        $photos = Photo::accessibleTo($request->user())->orderBy('taken_at')->get();

        return view('albums.create', compact('photos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'photo_ids' => ['array'],
            'photo_ids.*' => ['integer', 'exists:photos,id'],
            'cover_photo_id' => ['nullable', 'integer', 'exists:photos,id'],
        ]);

        $album = Album::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'cover_photo_id' => $data['cover_photo_id'] ?? null,
        ]);

        if (! empty($data['photo_ids'])) {
            $album->photos()->sync($data['photo_ids']);
        }

        return redirect()->route('albums.index')->with('status', 'Álbum criado.');
    }

    public function show(Request $request, Album $album): View
    {
        // O álbum é uma camada independente das pessoas, mas não pode servir de
        // atalho para contornar a permissão: a mesma regra das fotos vale aqui.
        $photos = $album->photos()
            ->accessibleTo($request->user())
            ->with(['people', 'exif'])
            ->latest('taken_at')->latest('id')
            ->paginate(24);

        return view('albums.show', compact('album', 'photos'));
    }

    public function edit(Request $request, Album $album): View
    {
        $photos = Photo::accessibleTo($request->user())->orderBy('taken_at')->get();

        return view('albums.edit', compact('album', 'photos'));
    }

    public function update(Request $request, Album $album): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'photo_ids' => ['array'],
            'photo_ids.*' => ['integer', 'exists:photos,id'],
            'cover_photo_id' => ['nullable', 'integer', 'exists:photos,id'],
        ]);

        // O formulário só oferece fotos que este usuário pode ver. Se o álbum
        // contiver outras, elas precisam ser preservadas no sync — senão salvar
        // o álbum apagaria em silêncio justamente as fotos que ele não enxerga.
        $ocultas = $album->photos()
            ->whereNotIn('photos.id', Photo::accessibleTo($request->user())->select('photos.id'))
            ->pluck('photos.id')
            ->all();

        // Mesma ideia para a capa: capa invisível não aparece no <select>, e
        // sem isto o campo voltaria vazio e limparia a capa do álbum.
        $capa = $data['cover_photo_id'] ?? null;
        if ($capa === null && in_array($album->cover_photo_id, $ocultas, true)) {
            $capa = $album->cover_photo_id;
        }

        $album->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'cover_photo_id' => $capa,
        ]);

        $album->photos()->sync(array_merge($data['photo_ids'] ?? [], $ocultas));

        return redirect()->route('albums.show', $album)->with('status', 'Álbum atualizado.');
    }

    public function destroy(Album $album): RedirectResponse
    {
        $album->delete();

        return redirect()->route('albums.index')->with('status', 'Álbum excluído.');
    }
}
