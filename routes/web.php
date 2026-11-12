<?php

use App\Http\Controllers\BusinessProfileController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
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

    Route::resource('clients', ClientController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('products', ProductController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::resource('invoices', InvoiceController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
});

// Verifikasi Keaslian Faktur Elektronik (Akses Publik via QR Code)
Route::get('/verify/invoice/{invoice_number}', [InvoiceController::class, 'verify'])->name('invoice.verify');

require __DIR__.'/auth.php';
