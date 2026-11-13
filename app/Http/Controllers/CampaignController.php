<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('CRM/Campaigns/Index', [
            'campaigns' => $request->user()->campaigns()->latest('id')->paginate(10)->withQueryString(),
            'channels' => Campaign::CHANNELS,
            'statuses' => Campaign::STATUSES,
        ]);
    }

    /**
     * Simpan draft broadcast. Status selalu 'Draft' — pengiriman massal
     * belum diimplementasi (menunggu integrasi API WhatsApp/Email).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'channel' => ['required', Rule::in(Campaign::CHANNELS)],
            'content' => ['required', 'string', 'max:4000'],
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = 'Draft';

        Campaign::create($validated);

        return back()->with('success', 'Draft promosi berhasil disimpan.');
    }

    public function destroy(Request $request, Campaign $campaign): RedirectResponse
    {
        abort_unless($campaign->user_id === $request->user()->id, 404);

        $campaign->delete();

        return back()->with('success', 'Draft promosi berhasil dihapus.');
    }
}
