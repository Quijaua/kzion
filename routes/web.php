<?php

use App\Http\Controllers\AlbumController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TimelineController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('photos', PhotoController::class);
    Route::post('photos/upload', [PhotoController::class, 'upload'])->name('photos.upload');
    Route::get('photos/upload/status/{token}', [PhotoController::class, 'uploadStatus'])->name('photos.upload.status');
    Route::delete('photos/upload/{photo}', [PhotoController::class, 'uploadRevert'])->name('photos.upload.revert');
    Route::get('photos/{photo}/file', [PhotoController::class, 'file'])->name('photos.file');
    Route::get('photos/{photo}/thumbnail', [PhotoController::class, 'thumbnail'])->name('photos.thumbnail');

    Route::resource('people', PersonController::class);

    Route::get('/timeline', [TimelineController::class, 'index'])->name('timeline.index');
    Route::get('/timeline/{year}', [TimelineController::class, 'year'])->whereNumber('year')->name('timeline.year');

    Route::resource('locations', LocationController::class);

    Route::resource('albums', AlbumController::class);

    Route::resource('users', UserController::class);

    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::get('/settings/jschallenge', [SettingController::class, 'jsChallenge'])->name('settings.jschallenge');
    Route::put('/settings/jschallenge', [SettingController::class, 'updateJsChallenge'])->name('settings.jschallenge.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
