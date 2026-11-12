<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Clients/Index', [
            'clients' => $request->user()->clients()->orderBy('name')->paginate(12)->withQueryString(),
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
        ]);

        $request->user()->clients()->create($data);

        return back()->with('success', 'Pelanggan berhasil disimpan.');
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
        ]);

        $client->update($data);

        return back()->with('success', 'Pelanggan berhasil diperbarui.');
    }

    public function destroy(Request $request, Client $client)
    {
        abort_unless($client->user_id === $request->user()->id, 404);
        $client->delete();

        return back()->with('success', 'Pelanggan berhasil dihapus.');
    }
}
