<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function store(Request $request, Client $client): RedirectResponse
    {
        if ($client->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(Project::STATUSES)],
            'progress_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'budget' => ['required', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date'],
            'initial_log' => ['nullable', 'string'],
        ]);

        $project = Project::create([
            'user_id' => auth()->id(),
            'client_id' => $client->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'progress_percentage' => $validated['progress_percentage'],
            'budget' => $validated['budget'],
            'start_date' => $validated['start_date'] ?? null,
            'deadline' => $validated['deadline'] ?? null,
        ]);

        if (!empty($validated['initial_log'])) {
            $project->logs()->create([
                'user_id' => auth()->id(),
                'title' => 'Inisiasi Proyek',
                'note' => $validated['initial_log'],
                'progress_after' => $validated['progress_percentage'],
                'logged_at' => now(),
            ]);
        }

        return back()->with('success', 'Proyek berhasil dibuat.');
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(Project::STATUSES)],
            'progress_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'budget' => ['required', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date'],
        ]);

        $project->update($validated);

        return back()->with('success', 'Data proyek berhasil diperbarui.');
    }

    public function updateStatus(Request $request, Project $project): RedirectResponse
    {
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(Project::STATUSES)],
            'progress_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $updateData = ['status' => $validated['status']];
        if (isset($validated['progress_percentage'])) {
            $updateData['progress_percentage'] = $validated['progress_percentage'];
        } elseif ($validated['status'] === Project::STATUS_COMPLETED) {
            $updateData['progress_percentage'] = 100;
        }

        $project->update($updateData);

        $project->logs()->create([
            'user_id' => auth()->id(),
            'title' => 'Update Status: ' . $validated['status'],
            'note' => 'Status proyek diperbarui ke "' . $validated['status'] . '"' . (isset($updateData['progress_percentage']) ? " ({$updateData['progress_percentage']}%)" : ''),
            'progress_after' => $project->progress_percentage,
            'logged_at' => now(),
        ]);

        return back()->with('success', 'Status proyek berhasil diperbarui.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        $project->delete();

        return back()->with('success', 'Proyek berhasil dihapus.');
    }
}
