<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Deal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CrmDealController extends Controller
{
    /**
     * Display a listing of the deals in a Kanban pipeline board.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $rawDeals = $user->deals()
            ->with(['client:id,name,email,phone,source'])
            ->latest()
            ->get();

        // Group deals by stage
        $groupedDeals = [];
        foreach (Deal::STAGES as $stage) {
            $groupedDeals[$stage] = [];
        }

        foreach ($rawDeals as $deal) {
            if (isset($groupedDeals[$deal->stage])) {
                $groupedDeals[$deal->stage][] = $deal;
            } else {
                $groupedDeals['Prospek'][] = $deal;
            }
        }

        // Summary metrics
        $metrics = [
            'total_deals' => $rawDeals->count(),
            'total_value' => (float) $rawDeals->sum('amount'),
            'won_value' => (float) $rawDeals->where('stage', 'Deal Menang')->sum('amount'),
            'active_value' => (float) $rawDeals->whereNotIn('stage', ['Deal Menang', 'Deal Batal'])->sum('amount'),
        ];

        // Client options for creating a new deal
        $clients = $user->clients()
            ->select('id', 'name', 'source')
            ->orderBy('name')
            ->get();

        return Inertia::render('CRM/Deals', [
            'stages' => Deal::STAGES,
            'dealsByStage' => $groupedDeals,
            'metrics' => $metrics,
            'clients' => $clients,
        ]);
    }

    /**
     * Store a newly created deal in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where('user_id', $request->user()->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'stage' => ['required', Rule::in(Deal::STAGES)],
            'expected_close_date' => ['nullable', 'date'],
        ]);

        $request->user()->deals()->create($validated);

        return back()->with('success', 'Peluang (Deal) berhasil ditambahkan ke pipeline.');
    }

    /**
     * Update the stage of a deal (drag-and-drop Kanban).
     */
    public function updateStage(Request $request, Deal $deal): RedirectResponse
    {
        abort_unless($deal->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'stage' => ['required', Rule::in(Deal::STAGES)],
        ]);

        $deal->update([
            'stage' => $validated['stage'],
        ]);

        return back()->with('success', "Tahap deal berhasil diperbarui ke '{$validated['stage']}'.");
    }

    /**
     * Update deal details.
     */
    public function update(Request $request, Deal $deal): RedirectResponse
    {
        abort_unless($deal->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where('user_id', $request->user()->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'stage' => ['required', Rule::in(Deal::STAGES)],
            'expected_close_date' => ['nullable', 'date'],
        ]);

        $deal->update($validated);

        return back()->with('success', 'Data deal berhasil diperbarui.');
    }

    /**
     * Remove the specified deal from storage.
     */
    public function destroy(Request $request, Deal $deal): RedirectResponse
    {
        abort_unless($deal->user_id === $request->user()->id, 404);

        $deal->delete();

        return back()->with('success', 'Deal berhasil dihapus dari pipeline.');
    }
}
