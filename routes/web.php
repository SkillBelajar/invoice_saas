<?php

use App\Http\Controllers\BusinessProfileController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CrmDealController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\InteractionController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome'))->name('home');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Master data faktur: profil usaha 1-to-1 dengan user
    Route::get('/profile/business', [BusinessProfileController::class, 'edit'])->name('profile.business.edit');
    Route::post('/profile/business', [BusinessProfileController::class, 'update'])->name('profile.business.update');

    Route::resource('clients', ClientController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::resource('products', ProductController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::patch('/invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.update-status');
    Route::resource('invoices', InvoiceController::class)->only(['index', 'create', 'store', 'show', 'destroy']);

    // CRM Solo (Pipeline / Deals Kanban)
    Route::get('/crm/deals', [CrmDealController::class, 'index'])->name('crm.deals.index');
    Route::post('/crm/deals', [CrmDealController::class, 'store'])->name('crm.deals.store');
    Route::patch('/crm/deals/{deal}/stage', [CrmDealController::class, 'updateStage'])->name('crm.deals.update-stage');
    Route::put('/crm/deals/{deal}', [CrmDealController::class, 'update'])->name('crm.deals.update');
    Route::delete('/crm/deals/{deal}', [CrmDealController::class, 'destroy'])->name('crm.deals.destroy');

    // CRM Solo Slice 2 (Jadwal Pengingat & Pusat Tiket Keluhan)
    Route::get('/crm/interactions', [InteractionController::class, 'index'])->name('crm.interactions.index');
    Route::post('/crm/interactions', [InteractionController::class, 'store'])->name('crm.interactions.store');
    Route::patch('/crm/interactions/{interaction}/toggle', [InteractionController::class, 'toggleStatus'])->name('crm.interactions.toggle');
    Route::put('/crm/interactions/{interaction}', [InteractionController::class, 'update'])->name('crm.interactions.update');
    Route::delete('/crm/interactions/{interaction}', [InteractionController::class, 'destroy'])->name('crm.interactions.destroy');

    Route::get('/crm/tickets', [TicketController::class, 'index'])->name('crm.tickets.index');
    Route::post('/crm/tickets', [TicketController::class, 'store'])->name('crm.tickets.store');
    Route::patch('/crm/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('crm.tickets.update-status');
    Route::put('/crm/tickets/{ticket}', [TicketController::class, 'update'])->name('crm.tickets.update');
    Route::delete('/crm/tickets/{ticket}', [TicketController::class, 'destroy'])->name('crm.tickets.destroy');

    // CRM Slice 3 (Pusat Komunikasi, Promosi & Survei CSAT)
    Route::get('/crm/messages', [MessageController::class, 'index'])->name('crm.messages.index');
    Route::post('/crm/messages/{client}', [MessageController::class, 'store'])->name('crm.messages.store');
    Route::get('/crm/campaigns', [CampaignController::class, 'index'])->name('crm.campaigns.index');
    Route::post('/crm/campaigns', [CampaignController::class, 'store'])->name('crm.campaigns.store');
    Route::delete('/crm/campaigns/{campaign}', [CampaignController::class, 'destroy'])->name('crm.campaigns.destroy');
    Route::post('/crm/tickets/{ticket}/survey', [SurveyController::class, 'store'])->name('crm.surveys.store');

    // User Guide / Panduan Pengguna (Slice 8)
    Route::get('/guide', [GuideController::class, 'index'])->name('guide.index');
    Route::get('/panduan', fn () => redirect()->route('guide.index'));
});

// Verifikasi Keaslian Faktur Elektronik (Akses Publik via QR Code)
Route::get('/verify/invoice/{invoice_number}', [InvoiceController::class, 'verify'])->name('invoice.verify');

require __DIR__.'/auth.php';
