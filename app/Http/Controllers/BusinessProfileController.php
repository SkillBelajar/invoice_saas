<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BusinessProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $profile = $request->user()->businessProfile;

        return Inertia::render('Profile/BusinessSetup', [
            'profile' => $profile ? [
                ...$profile->only([
                    'company_name',
                    'address',
                    'tax_id',
                    'bank_name',
                    'bank_account_number',
                    'bank_account_name',
                ]),
                'logo_url' => $profile->logo_path ? Storage::disk('public')->url($profile->logo_path) : null,
                'signature_url' => $profile->signature_path ? Storage::disk('public')->url($profile->signature_path) : null,
            ] : null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:2000'],
            'tax_id' => ['nullable', 'string', 'max:32'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:64'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'signature' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
        ]);

        $profile = $request->user()->businessProfile;
        $userId = $request->user()->id;

        // Per-user directory keeps tenant assets isolated and makes cleanup trivial
        foreach (['logo' => 'logo_path', 'signature' => 'signature_path'] as $input => $column) {
            if ($request->hasFile($input)) {
                if ($profile?->{$column}) {
                    Storage::disk('public')->delete($profile->{$column});
                }
                $validated[$column] = $request->file($input)->store("business/{$userId}", 'public');
            }
        }

        unset($validated['logo'], $validated['signature']);

        $request->user()->businessProfile()->updateOrCreate(
            ['user_id' => $userId],
            $validated,
        );

        return back()->with('success', 'Profil usaha berhasil disimpan.');
    }
}
