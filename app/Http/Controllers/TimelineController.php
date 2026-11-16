<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Linha do tempo: anos → meses → fotos, pela DATA DA FOTO (`taken_at`), nunca
 * pela data de envio. Toda contagem passa pelo mesmo `accessibleTo()` da grade,
 * para o card não prometer fotos que a página não mostra. O último nível é a
 * própria /photos com ?year=&month=, que ainda combina com os outros filtros.
 */
class TimelineController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $years = Photo::accessibleTo($user)
            ->whereNotNull('taken_at')
            ->selectRaw(Photo::yearSql().' as year, count(*) as total, max(photos.id) as cover_id')
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        $covers = Photo::whereIn('id', $years->pluck('cover_id'))->get()->keyBy('id');

        // Agrupado por década, para listas longas (1940 … 2026) ficarem legíveis.
        $decades = $years->groupBy(fn ($y) => intdiv((int) $y->year, 10) * 10);

        $undated = Photo::accessibleTo($user)->whereNull('taken_at')->count();

        return view('timeline.index', compact('decades', 'covers', 'undated') + [
            'total' => $years->sum('total'),
        ]);
    }

    public function year(Request $request, int $year): View
    {
        $user = $request->user();

        $months = Photo::accessibleTo($user)
            ->takenInYear($year)
            ->whereKnownMonth()
            ->selectRaw(Photo::monthSql().' as month, count(*) as total, max(photos.id) as cover_id')
            ->groupBy('month')
            ->get()
            ->keyBy(fn ($m) => (int) $m->month);

        $covers = Photo::whereIn('id', $months->pluck('cover_id'))->get()->keyBy('id');

        $unknownMonth = Photo::accessibleTo($user)
            ->takenInYear($year)
            ->where('taken_at_precision', Photo::PRECISION_YEAR)
            ->count();

        // Anos vizinhos COM fotos, para navegar sem voltar à lista.
        $allYears = Photo::accessibleTo($user)
            ->whereNotNull('taken_at')
            ->selectRaw(Photo::yearSql().' as year')
            ->groupBy('year')
            ->pluck('year')
            ->map(fn ($y) => (int) $y);

        return view('timeline.year', [
            'year' => $year,
            'months' => $months,
            'covers' => $covers,
            'unknownMonth' => $unknownMonth,
            'total' => $months->sum('total') + $unknownMonth,
            'prevYear' => $allYears->filter(fn ($y) => $y < $year)->max(),
            'nextYear' => $allYears->filter(fn ($y) => $y > $year)->min(),
        ]);
    }
}
