<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    /**
     * Simpan rating CSAT (1-5) untuk tiket yang sudah ditutup.
     * Satu tiket hanya boleh disurvei sekali.
     */
    public function store(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, 404);

        abort_unless(in_array($ticket->status, ['Selesai', 'Tutup'], true), 422, 'Survei hanya untuk tiket yang sudah selesai/ditutup.');

        if (Survey::where('ticket_id', $ticket->id)->exists()) {
            return back()->with('error', 'Tiket ini sudah pernah disurvei.');
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'feedback' => ['nullable', 'string', 'max:1000'],
        ]);

        Survey::create([
            'user_id' => $request->user()->id,
            'client_id' => $ticket->client_id,
            'ticket_id' => $ticket->id,
            'rating' => $validated['rating'],
            'feedback' => $validated['feedback'] ?? null,
        ]);

        return back()->with('success', 'Terima kasih, rating kepuasan berhasil disimpan.');
    }
}
