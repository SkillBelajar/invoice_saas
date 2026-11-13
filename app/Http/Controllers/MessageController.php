<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    /**
     * Inbox ala chat: daftar klien di kiri, riwayat percakapan di kanan.
     * Placeholder pencatatan — belum terhubung ke API WhatsApp/Email asli.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $clientId = $request->query('client');
        $selected = $clientId
            ? $user->clients()->find($clientId)
            : $user->clients()->orderBy('name')->first();

        if ($selected) {
            // Tandai pesan masuk sebagai sudah dibaca saat percakapan dibuka
            Message::where('user_id', $user->id)
                ->where('client_id', $selected->id)
                ->where('direction', 'Masuk')
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        $clients = $user->clients()
            ->withCount(['messages as unread_count' => fn ($q) => $q->where('direction', 'Masuk')->where('is_read', false)])
            ->with(['messages' => fn ($q) => $q->latest()->limit(1)])
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'email'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'unread_count' => $c->unread_count,
                'last_message' => $c->messages->first()?->content,
                'last_at' => $c->messages->first()?->created_at?->toIso8601String(),
            ]);

        return Inertia::render('CRM/Messages/Index', [
            'clients' => $clients,
            'selectedClient' => $selected?->only(['id', 'name', 'phone', 'email']),
            'thread' => $selected
                ? Message::where('user_id', $user->id)->where('client_id', $selected->id)
                    ->orderBy('created_at')->get(['id', 'direction', 'channel', 'content', 'created_at'])
                : [],
            'channels' => Message::CHANNELS,
        ]);
    }

    public function store(Request $request, Client $client): RedirectResponse
    {
        abort_unless($client->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'channel' => ['required', Rule::in(Message::CHANNELS)],
            'content' => ['required', 'string', 'max:2000'],
        ]);

        Message::create([
            'user_id' => $request->user()->id,
            'client_id' => $client->id,
            'direction' => 'Keluar',
            'channel' => $validated['channel'],
            'content' => $validated['content'],
            'is_read' => true,
        ]);

        return redirect()->route('crm.messages.index', ['client' => $client->id]);
    }
}
