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

        if ($year = (int) $request->input('year')) {
            $query->takenInYear($year);
        }

        // month=1..12 → fotos daquele mês (só as com mês conhecido);
        // month=unknown → as que só têm o ano.
        $month = $request->input('month');
        if ($month === 'unknown') {
            $query->where('taken_at_precision', Photo::PRECISION_YEAR);
        } elseif ((int) $month >= 1 && (int) $month <= 12) {
            $query->takenInMonth((int) $month);
        }

        if ($request->boolean('undated')) {
            $query->whereNull('taken_at');
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
     * Endpoint do FilePond no envio em lote. Grava cada imagem assim que entra
     * na fila (sem metadados; eles são preenchidos depois, foto a foto).
     *
     * O navegador manda um `upload_token` por arquivo. Se a resposta se perder,
     * ele consulta `uploadStatus()` com esse token para saber se a foto chegou;
     * e se reenviar o mesmo arquivo, recebe a foto já criada em vez de uma cópia.
     *
     * Respostas: 200 com o id da foto (texto); 409 em JSON quando a mesma imagem
     * já está no acervo e o usuário consegue vê-la.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'filepond' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'upload_token' => ['nullable', 'uuid'],
            'read_exif' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $token = $request->input('upload_token');

        if ($token) {
            $existente = Photo::where('upload_token', $token)->first();

            if ($existente && (int) $existente->uploaded_by === $user->id) {
                return response($existente->id, 200);
            }

            if ($existente) {
                $token = null; // token de outra pessoa: não reaproveita
            }
        }

        $file = $request->file('filepond');

        // Só conta como duplicada a foto que este usuário consegue ver. Avisar
        // de uma que ele não vê revelaria que ela existe — e ele ficaria sem a
        // própria cópia.
        $duplicada = Photo::accessibleTo($user)
            ->where('checksum', PhotoService::checksum($file))
            ->first();

        if ($duplicada) {
            return response()->json([
                'message' => 'Esta foto já está no acervo.',
                'id' => $duplicada->id,
                'url' => route('photos.show', $duplicada),
            ], 409);
        }

        $photo = $this->photos->store($file, ['upload_token' => $token], $request->boolean('read_exif', true));

        return response($photo->id, 200);
    }

    /**
     * "Esta foto chegou?" — consultado pelo envio em lote quando a resposta do
     * upload demora ou se perde. Só responde sobre envios do próprio usuário.
     */
    public function uploadStatus(Request $request, string $token)
    {
        $photo = Photo::where('upload_token', $token)
            ->where('uploaded_by', $request->user()->id)
            ->first();

        abort_unless($photo, 404);

        return response($photo->id, 200);
    }

    /**
     * Revert do FilePond: a foto foi tirada da fila antes de concluir, então é
     * apagada. Só quem enviou (ou um admin) — antes qualquer usuário logado
     * conseguia apagar qualquer foto chamando esta rota com outro id.
     */
    public function uploadRevert(Request $request, Photo $photo)
    {
        abort_unless(
            $request->user()->isAdmin() || (int) $photo->uploaded_by === $request->user()->id,
            403
        );

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
            ...$this->dateRules(),
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'read_exif' => ['sometimes', 'boolean'],
        ]);

        $files = collect($data['photos'])->filter(fn ($file) => $file instanceof UploadedFile);

        if ($files->isEmpty()) {
            return back()->withErrors(['photos' => 'Nenhuma imagem válida foi enviada.'])->withInput();
        }

        $enviadas = 0;
        $repetidas = 0;

        foreach ($files as $file) {
            $jaExiste = Photo::accessibleTo($request->user())
                ->where('checksum', PhotoService::checksum($file))
                ->exists();

            if ($jaExiste) {
                $repetidas++;
                continue;
            }

            $enviadas++;

            $photo = $this->photos->store($file, [
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                ...$this->dateFromRequest($data),
                'location_id' => $data['location_id'] ?? null,
            ], $request->boolean('read_exif', true));

            if (! empty($data['person_ids'])) {
                $photo->people()->syncWithoutDetaching($data['person_ids']);
            }
            if (! empty($data['album_ids'])) {
                $photo->albums()->syncWithoutDetaching($data['album_ids']);
            }
        }

        if ($enviadas === 0) {
            return back()
                ->withErrors(['photos' => $repetidas === 1
                    ? 'Esta foto já está no acervo.'
                    : 'Todas as fotos enviadas já estão no acervo.'])
                ->withInput();
        }

        $mensagem = 'Fotos enviadas com sucesso.';
        if ($repetidas > 0) {
            $mensagem .= $repetidas === 1
                ? ' 1 delas já estava no acervo e foi ignorada.'
                : " {$repetidas} delas já estavam no acervo e foram ignoradas.";
        }

        return redirect()->route('photos.index')->with('status', $mensagem);
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
            ...$this->dateRules(),
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
        ]);

        $photo->update([
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            ...$this->dateFromRequest($data, $photo),
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

    /**
     * Data da foto em três campos: ano (obrigatório se houver data), mês e dia
     * opcionais. A precisão sai do que foi preenchido.
     */
    private function dateRules(): array
    {
        return [
            // Mês ou dia sem ano não forma data.
            'taken_year' => ['nullable', 'required_with:taken_month,taken_day', 'integer', 'between:1800,'.(now()->year + 1)],
            'taken_month' => ['nullable', 'integer', 'between:1,12', 'required_with:taken_day'],
            'taken_day' => ['nullable', 'integer', 'between:1,31', function ($attribute, $value, $fail) {
                $year = (int) request('taken_year');
                $month = (int) request('taken_month');

                if ($year && $month && ! checkdate($month, (int) $value, $year)) {
                    $fail('Essa data não existe.');
                }
            }],
        ];
    }

    /** ['taken_at' => …, 'taken_at_precision' => …] a partir dos campos validados. */
    private function dateFromRequest(array $data, ?Photo $photo = null): array
    {
        [$date, $precision] = Photo::dateFromParts(
            isset($data['taken_year']) ? (int) $data['taken_year'] : null,
            isset($data['taken_month']) ? (int) $data['taken_month'] : null,
            isset($data['taken_day']) ? (int) $data['taken_day'] : null,
        );

        // Mesma data completa de antes: preserva a hora (que veio do EXIF).
        if ($photo && $date && $precision === Photo::PRECISION_DAY
            && $photo->datePrecision() === Photo::PRECISION_DAY
            && $photo->taken_at->isSameDay($date)) {
            $date = $photo->taken_at;
        }

        return ['taken_at' => $date, 'taken_at_precision' => $precision];
    }
}
