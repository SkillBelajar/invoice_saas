<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Clients/Index', [
            'clients' => $request->user()->clients()->withCount('invoices')->orderBy('name')->paginate(12)->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:2000'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', 'string', 'max:100'],
        ]);

        $request->user()->clients()->create($data);

        return back()->with('success', 'Pelanggan berhasil disimpan.');
    }

    public function show(Request $request, Client $client): Response
    {
        abort_unless($client->user_id === $request->user()->id, 404);

        $client->load([
            'invoices' => fn ($query) => $query->latest('issue_date')->withCount('items'),
            'deals' => fn ($query) => $query->latest(),
        ]);

        $stats = [
            'total_invoiced' => (float) $client->invoices->sum('grand_total'),
            'total_paid' => (float) $client->invoices->where('status', 'paid')->sum('grand_total'),
            'total_unpaid' => (float) $client->invoices->where('status', '!=', 'paid')->sum('grand_total'),
            'invoices_count' => $client->invoices->count(),
            'deals_count' => $client->deals->count(),
        ];

        return Inertia::render('Clients/Show', [
            'client' => $client,
            'stats' => $stats,
        ]);
    }

    public function update(Request $request, Client $client)
    {
        abort_unless($client->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:2000'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', 'string', 'max:100'],
        ]);

        $client->update($data);

        return back()->with('success', 'Pelanggan berhasil diperbarui.');
    }

    public function destroy(Request $request, Client $client)
    {
        abort_unless($client->user_id === $request->user()->id, 404);

        DB::transaction(function () use ($client) {
            foreach ($client->invoices as $invoice) {
                $invoice->items()->delete();
                $invoice->delete();
            }
            $client->delete();
        });

        return back()->with('success', 'Pelanggan dan faktur terkait berhasil dihapus.');
    }
}
