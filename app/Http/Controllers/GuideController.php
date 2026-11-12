<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuideController extends Controller
{
    /**
     * Tampilkan halaman Panduan Pengguna (User Guide) internal untuk Owner / Admin Usaha.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Guide/Index', [
            'appVersion' => config('app.version', '0.11'),
            'user' => [
                'name' => $request->user()->name,
                'email' => $request->user()->email,
            ],
        ]);
    }
}
