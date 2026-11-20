<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectLogController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'progress_after' => ['nullable', 'integer', 'min:0', 'max:100'],
            'logged_at' => ['nullable', 'date'],
        ]);

        $progressAfter = isset($validated['progress_after']) && $validated['progress_after'] !== ''
            ? (int) $validated['progress_after']
            : $project->progress_percentage;

        $project->logs()->create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'note' => $validated['note'] ?? null,
            'progress_after' => $progressAfter,
            'logged_at' => $validated['logged_at'] ?? now(),
        ]);

        if (isset($validated['progress_after']) && $validated['progress_after'] !== '') {
            $updateData = ['progress_percentage' => (int) $validated['progress_after']];
            if ((int) $validated['progress_after'] === 100 && $project->status !== Project::STATUS_COMPLETED) {
                $updateData['status'] = Project::STATUS_COMPLETED;
            }
            $project->update($updateData);
        }

        return back()->with('success', 'Catatan log progres proyek berhasil ditambahkan.');
    }

    public function destroy(ProjectLog $log): RedirectResponse
    {
        if ($log->user_id !== auth()->id()) {
            abort(403);
        }

        $log->delete();

        return back()->with('success', 'Catatan log proyek berhasil dihapus.');
    }
}
