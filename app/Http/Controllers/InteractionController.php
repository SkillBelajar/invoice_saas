<?php

namespace App\Http\Controllers;

use App\Models\Interaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InteractionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $statusFilter = $request->query('status', 'Pending');
        $typeFilter = $request->query('type');
        $search = $request->query('search');

        $query = $user->interactions()->with('client:id,name,phone,email,source');

        if ($statusFilter && $statusFilter !== 'Semua') {
            $query->where('status', $statusFilter);
        }

        if ($typeFilter && in_array($typeFilter, Interaction::TYPES)) {
            $query->where('type', $typeFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($clientQ) use ($search) {
                        $clientQ->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Urutan: bila filter Pending, urutkan dari jadwal terdekat (ASC)
        if ($statusFilter === 'Pending') {
            $query->orderBy('scheduled_at', 'asc');
        } else {
            $query->orderBy('scheduled_at', 'desc');
        }

        $now = now();
        $startOfDay = $now->copy()->startOfDay();
        $endOfDay = $now->copy()->endOfDay();

        $metrics = [
            'total' => $user->interactions()->count(),
            'pending' => $user->interactions()->where('status', 'Pending')->count(),
            'today' => $user->interactions()
                ->where('status', 'Pending')
                ->whereBetween('scheduled_at', [$startOfDay, $endOfDay])
                ->count(),
            'overdue' => $user->interactions()
                ->where('status', 'Pending')
                ->where('scheduled_at', '<', $now)
                ->count(),
            'completed' => $user->interactions()->where('status', 'Selesai')->count(),
        ];

        return Inertia::render('CRM/Interactions/Index', [
            'interactions' => $query->paginate(15)->withQueryString(),
            'clients' => $user->clients()->orderBy('name')->get(['id', 'name', 'phone', 'email']),
            'filters' => [
                'status' => $statusFilter,
                'type' => $typeFilter,
                'search' => $search,
            ],
            'metrics' => $metrics,
            'interactionTypes' => Interaction::TYPES,
            'interactionStatuses' => Interaction::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where('user_id', $request->user()->id),
            ],
            'type' => ['required', Rule::in(Interaction::TYPES)],
            'scheduled_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(Interaction::STATUSES)],
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = $validated['status'] ?? 'Pending';

        Interaction::create($validated);

        return back()->with('success', 'Jadwal pengingat interaksi berhasil ditambahkan.');
    }

    public function update(Request $request, Interaction $interaction): RedirectResponse
    {
        abort_unless($interaction->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where('user_id', $request->user()->id),
            ],
            'type' => ['required', Rule::in(Interaction::TYPES)],
            'scheduled_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(Interaction::STATUSES)],
        ]);

        $interaction->update($validated);

        return back()->with('success', 'Jadwal pengingat interaksi berhasil diperbarui.');
    }

    public function toggleStatus(Request $request, Interaction $interaction): RedirectResponse
    {
        abort_unless($interaction->user_id === $request->user()->id, 404);

        $newStatus = $request->has('status') && in_array($request->input('status'), Interaction::STATUSES)
            ? $request->input('status')
            : ($interaction->status === 'Pending' ? 'Selesai' : 'Pending');

        $interaction->update(['status' => $newStatus]);

        return back()->with('success', "Status pengingat berhasil diubah menjadi {$newStatus}.");
    }

    public function destroy(Request $request, Interaction $interaction): RedirectResponse
    {
        abort_unless($interaction->user_id === $request->user()->id, 404);

        $interaction->delete();

        return back()->with('success', 'Jadwal pengingat interaksi berhasil dihapus.');
    }
}
