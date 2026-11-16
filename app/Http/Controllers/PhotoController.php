<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Location;
use App\Models\Person;
use App\Models\Photo;
use App\Models\User;
use App\Services\PhotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PhotoController extends Controller
{
    public function __construct(protected PhotoService $photos) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $query = Photo::accessibleTo($user)->with(['people', 'exif']);

        $this->applyFilters($query, $request);

        $photos = $query->orderBy('id', 'desc')->paginate(24);

        $people = $user->visiblePeople()->orderBy('name')->get();
        $albums = Album::orderBy('name')->get();

        return view('photos.index', compact('photos', 'people', 'albums'));
    }

    protected function applyFilters($query, Request $request): void
    {
        if ($people = $request->input('people', [])) {
            foreach ((array) $people as $personId) {
                $query->whereHas('people', fn ($q) => $q->where('person_id', $personId));
            }
        }

        if ($year = $request->input('year')) {
            $query->whereYear('taken_at', $year);
        }

        if ($location = $request->input('location')) {
            $query->whereHas('location', fn ($q) => $q->where('name', 'like', "%{$location}%"));
        }

        if ($name = $request->input('name')) {
            $query->where('title', 'like', "%{$name}%");
        }

        if ($description = $request->input('description')) {
            $query->where(function ($q) use ($description) {
                $q->where('description', 'like', "%{$description}%")
                  ->orWhere('title', 'like', "%{$description}%");
            });
        }

        if ($albumId = $request->input('album_id')) {
            $query->whereHas('albums', fn ($q) => $q->where('album_id', $albumId));
        }

        if ($camera = $request->input('camera')) {
            $query->whereHas('exif', function ($q) use ($camera) {
                $q->where('make', 'like', "%{$camera}%")
                  ->orWhere('model', 'like', "%{$camera}%");
            });
        }
    }

    public function create(Request $request): View
    {
        $mode = $request->query('mode', 'choose');

        if ($mode === 'single') {
            // Só as pessoas que este usuário pode ver: listar as outras vazaria
            // nomes do acervo e ainda deixaria ele marcar alguém que faria a
            // própria foto sumir da vista dele assim que fosse salva.
            $people = $request->user()->visiblePeople()->orderBy('name')->get();
            $albums = Album::orderBy('name')->get();
            $locations = Location::orderBy('name')->get();

            return view('photos.single', compact('people', 'albums', 'locations'));
        }

        if ($mode === 'bulk') {
            return view('photos.bulk');
        }

        return view('photos.create');
    }

    /**
     * FilePond process endpoint for bulk uploads. Stores each dropped image
     * immediately (no metadata yet); metadata is filled in individually later.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'filepond' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
        ]);

        $photo = $this->photos->store($request->file('filepond'), []);

        return response($photo->id, 200);
    }

    /**
     * FilePond revert endpoint: a previously uploaded (bulk) photo was removed
     * from the pond before finishing, so delete it.
     */
    public function uploadRevert(Photo $photo)
    {
        Storage::delete($photo->storage_path);
        $thumb = 'thumbnails/'.substr($photo->storage_path, strlen('photos/'));
        Storage::delete($thumb);

        $photo->delete();

        return response('', 200);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'person_ids' => ['array'],
            'person_ids.*' => ['integer', Rule::in($request->user()->visiblePersonIds())],
            'album_ids' => ['array'],
            'album_ids.*' => ['integer', 'exists:albums,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'taken_at' => ['nullable', 'date'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
        ]);

        $files = collect($data['photos'])->filter(fn ($file) => $file instanceof UploadedFile);

        if ($files->isEmpty()) {
            return back()->withErrors(['photos' => 'Nenhuma imagem válida foi enviada.'])->withInput();
        }

        foreach ($files as $file) {
            $photo = $this->photos->store($file, [
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                'taken_at' => $data['taken_at'] ?? null,
                'location_id' => $data['location_id'] ?? null,
            ]);

            if (! empty($data['person_ids'])) {
                $photo->people()->syncWithoutDetaching($data['person_ids']);
            }
            if (! empty($data['album_ids'])) {
                $photo->albums()->syncWithoutDetaching($data['album_ids']);
            }
        }

        return redirect()->route('photos.index')->with('status', 'Fotos enviadas com sucesso.');
    }

    public function show(Request $request, Photo $photo): View
   
    {
        $photo = Photo::accessibleTo($request->user())
            ->with(['people', 'exif', 'albums', 'uploader', 'location'])
            ->findOrFail($photo->id);

        return view('photos.show', compact('photo'));
    }

    public function edit(Request $request, Photo $photo): View
    {
        $photo = Photo::accessibleTo($request->user())->with(['people', 'albums'])->findOrFail($photo->id);

        // Só as pessoas que este usuário pode ver. As que já estão marcadas na
        // foto e estão fora do acesso dele ficam de fora do seletor de
        // propósito — `update()` as preserva no sync.
        $people = $request->user()->visiblePeople()->orderBy('name')->get();
        $albums = Album::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();

        return view('photos.edit', compact('photo', 'people', 'albums', 'locations'));
    }

    public function update(Request $request, Photo $photo): RedirectResponse
    {
        $photo = Photo::accessibleTo($request->user())->findOrFail($photo->id);

        $data = $request->validate([
            'person_ids' => ['array'],
            'person_ids.*' => ['integer', Rule::in($request->user()->visiblePersonIds())],
            'album_ids' => ['array'],
            'album_ids.*' => ['integer', 'exists:albums,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'taken_at' => ['nullable', 'date'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
        ]);

        $photo->update([
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'taken_at' => $data['taken_at'] ?? null,
            'location_id' => $data['location_id'] ?? null,
        ]);

        // Com a regra permissiva, o usuário pode abrir uma foto que também tem
        // gente fora do acesso dele — e essas pessoas não aparecem no seletor.
        // Sem mesclá-las de volta, salvar a foto as removeria em silêncio.
        $ocultas = $photo->people()
            ->whereNotIn('person_id', $request->user()->visiblePersonIds())
            ->pluck('people.id')
            ->all();

        $photo->people()->sync(array_merge($data['person_ids'] ?? [], $ocultas));
        $photo->albums()->sync($data['album_ids'] ?? []);

        return redirect()->route('photos.show', $photo)->with('status', 'Foto atualizada.');
    }

    public function destroy(Request $request, Photo $photo): RedirectResponse
    {
        $photo = Photo::accessibleTo($request->user())->findOrFail($photo->id);

        Storage::delete($photo->storage_path);
        $thumb = 'thumbnails/'.substr($photo->storage_path, strlen('photos/')); // best effort
        Storage::delete($thumb);

        $photo->delete();

        return redirect()->route('photos.index')->with('status', 'Foto excluída.');
    }

    public function file(Photo $photo, Request $request)
    {
        $photo = Photo::accessibleTo($request->user())->findOrFail($photo->id);

        abort_unless(Storage::exists($photo->storage_path), 404);

        return Response::file(Storage::path($photo->storage_path));
    }

    public function thumbnail(Photo $photo, Request $request)
    {
        $photo = Photo::accessibleTo($request->user())->findOrFail($photo->id);

        $thumb = 'thumbnails/'.substr($photo->storage_path, strlen('photos/'));

        if (! Storage::exists($thumb)) {
            abort_unless(Storage::exists($photo->storage_path), 404);
            $thumb = $photo->storage_path;
        }

        return Response::file(Storage::path($thumb));
    }
}
