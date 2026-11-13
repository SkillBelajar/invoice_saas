<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public static function generateTicketNumber(): string
    {
        $lastTicket = Ticket::latest('id')->first();
        $nextSeq = $lastTicket ? ($lastTicket->id + 1) : 1;
        $ticketNumber = sprintf('TK-%03d', $nextSeq);

        while (Ticket::where('ticket_number', $ticketNumber)->exists()) {
            $nextSeq++;
            $ticketNumber = sprintf('TK-%03d', $nextSeq);
        }

        return $ticketNumber;
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $statusFilter = $request->query('status', 'Semua');
        $priorityFilter = $request->query('priority');
        $search = $request->query('search');

        $query = $user->tickets()->with('client:id,name,phone,email,source');

        if ($statusFilter && $statusFilter !== 'Semua') {
            if ($statusFilter === 'Overdue') {
                $query->whereIn('status', ['Buka', 'Diproses'])
                    ->where('due_date', '<', now());
            } else {
                $query->where('status', $statusFilter);
            }
        }

        if ($priorityFilter && in_array($priorityFilter, Ticket::PRIORITIES)) {
            $query->where('priority', $priorityFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($clientQ) use ($search) {
                        $clientQ->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Urutan: tiket aktif (Buka/Diproses) didahulukan, disusul tanggal SLA terdekat
        $query->orderByRaw("CASE WHEN status IN ('Buka', 'Diproses') THEN 0 ELSE 1 END")
            ->orderBy('due_date', 'asc');

        $now = now();
        $metrics = [
            'total' => $user->tickets()->count(),
            'open' => $user->tickets()->where('status', 'Buka')->count(),
            'in_progress' => $user->tickets()->where('status', 'Diproses')->count(),
            'overdue' => $user->tickets()
                ->whereIn('status', ['Buka', 'Diproses'])
                ->where('due_date', '<', $now)
                ->count(),
            'resolved' => $user->tickets()->whereIn('status', ['Selesai', 'Tutup'])->count(),
        ];

        return Inertia::render('CRM/Tickets/Index', [
            'tickets' => $query->paginate(12)->withQueryString(),
            'clients' => $user->clients()->orderBy('name')->get(['id', 'name', 'phone', 'email']),
            'nextTicketNumber' => self::generateTicketNumber(),
            'filters' => [
                'status' => $statusFilter,
                'priority' => $priorityFilter,
                'search' => $search,
            ],
            'metrics' => $metrics,
            'priorities' => Ticket::PRIORITIES,
            'statuses' => Ticket::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where('user_id', $request->user()->id),
            ],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', Rule::in(Ticket::PRIORITIES)],
            'status' => ['nullable', Rule::in(Ticket::STATUSES)],
            'due_date' => ['required', 'date'],
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['ticket_number'] = self::generateTicketNumber();
        $validated['status'] = $validated['status'] ?? 'Buka';

        Ticket::create($validated);

        return back()->with('success', "Tiket keluhan {$validated['ticket_number']} berhasil diterbitkan.");
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where('user_id', $request->user()->id),
            ],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', Rule::in(Ticket::PRIORITIES)],
            'status' => ['required', Rule::in(Ticket::STATUSES)],
            'due_date' => ['required', 'date'],
        ]);

        $ticket->update($validated);

        return back()->with('success', "Tiket {$ticket->ticket_number} berhasil diperbarui.");
    }

    public function updateStatus(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(Ticket::STATUSES)],
        ]);

        $ticket->update(['status' => $validated['status']]);

        return back()->with('success', "Status tiket {$ticket->ticket_number} berhasil diubah menjadi {$validated['status']}.");
    }

    public function destroy(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, 404);

        $ticketNumber = $ticket->ticket_number;
        $ticket->delete();

        return back()->with('success', "Tiket {$ticketNumber} berhasil dihapus.");
    }
}
