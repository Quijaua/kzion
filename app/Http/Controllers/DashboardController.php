<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Person;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $photoCount = Photo::accessibleTo($user)->count();
        $personCount = $user->visiblePeople()->count();
        $userCount = User::count();
        $albumCount = Album::count();
        $pendingCount = Photo::accessibleTo($user)->withoutDetails()->count();

        $recentPhotos = Photo::accessibleTo($user)
            ->with(['people', 'exif'])
            ->latest('taken_at')->latest('id')
            ->limit(8)
            ->get();

        return view('dashboard', compact('photoCount', 'personCount', 'userCount', 'albumCount', 'pendingCount', 'recentPhotos'));
    }
}
