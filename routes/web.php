<?php

use App\Http\Controllers\BusinessProfileController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome'))->name('home');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Master data faktur: profil usaha 1-to-1 dengan user
    Route::get('/profile/business', [BusinessProfileController::class, 'edit'])->name('profile.business.edit');
    Route::post('/profile/business', [BusinessProfileController::class, 'update'])->name('profile.business.update');
});

require __DIR__.'/auth.php';
