<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->client = Client::create([
            'user_id' => $this->user->id,
            'name' => 'PT Solusi Bangun Digital',
            'email' => 'halo@solusidigital.test',
            'phone' => '081122334455',
            'address' => 'Jl. Merdeka No. 45',
            'source' => 'Website',
        ]);
    }

    public function test_user_can_view_client_show_with_projects_and_logs(): void
    {
        $project = Project::create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'name' => 'Pengembangan Web Portal',
            'description' => 'Redesign portal internal',
            'status' => Project::STATUS_IN_PROGRESS,
            'progress_percentage' => 45,
            'budget' => 35000000,
            'start_date' => now()->toDateString(),
            'deadline' => now()->addMonth()->toDateString(),
        ]);

        ProjectLog::create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'title' => 'Slicing UI Dashboard',
            'note' => 'Tahap 1 selesai dan di-review klien',
            'progress_after' => 45,
            'logged_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get(route('clients.show', $this->client->id));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Clients/Show')
            ->where('client.id', $this->client->id)
            ->has('client.projects', 1)
            ->where('client.projects.0.logs.0.title', 'Slicing UI Dashboard')
            ->has('projectStatuses')
        );
    }

    public function test_user_can_create_project_with_optional_initial_log(): void
    {
        $response = $this->actingAs($this->user)->post(route('projects.store', $this->client->id), [
            'name' => 'Implementasi ERP Cloud',
            'description' => 'Migrasi database dan setup modul akuntansi',
            'status' => Project::STATUS_PLANNING,
            'progress_percentage' => 10,
            'budget' => 50000000,
            'start_date' => now()->toDateString(),
            'deadline' => now()->addMonths(3)->toDateString(),
            'initial_log' => 'Kickoff meeting dengan stakeholder selesai',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('projects', [
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'name' => 'Implementasi ERP Cloud',
            'progress_percentage' => 10,
            'status' => Project::STATUS_PLANNING,
        ]);

        $project = Project::where('name', 'Implementasi ERP Cloud')->first();
        $this->assertNotNull($project);

        $this->assertDatabaseHas('project_logs', [
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'title' => 'Inisiasi Proyek',
            'note' => 'Kickoff meeting dengan stakeholder selesai',
            'progress_after' => 10,
        ]);
    }

    public function test_user_cannot_create_project_for_other_users_client(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->post(route('projects.store', $this->client->id), [
            'name' => 'Proyek Ilegal',
            'status' => Project::STATUS_PLANNING,
            'progress_percentage' => 0,
            'budget' => 1000000,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('projects', ['name' => 'Proyek Ilegal']);
    }

    public function test_user_can_update_project_details(): void
    {
        $project = Project::create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'name' => 'Mobile App iOS',
            'status' => Project::STATUS_PLANNING,
            'progress_percentage' => 15,
            'budget' => 20000000,
        ]);

        $response = $this->actingAs($this->user)->put(route('projects.update', $project->id), [
            'name' => 'Mobile App iOS & Android',
            'description' => 'Ekspansi ke Flutter multiplatform',
            'status' => Project::STATUS_IN_PROGRESS,
            'progress_percentage' => 30,
            'budget' => 30000000,
            'start_date' => now()->toDateString(),
            'deadline' => now()->addMonth()->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Mobile App iOS & Android',
            'progress_percentage' => 30,
            'status' => Project::STATUS_IN_PROGRESS,
        ]);
    }

    public function test_user_can_update_project_status_and_auto_logs(): void
    {
        $project = Project::create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'name' => 'Setup Infrastruktur Server',
            'status' => Project::STATUS_IN_PROGRESS,
            'progress_percentage' => 70,
            'budget' => 12000000,
        ]);

        $response = $this->actingAs($this->user)->patch(route('projects.update-status', $project->id), [
            'status' => Project::STATUS_COMPLETED,
        ]);

        $response->assertRedirect();
        $project->refresh();

        $this->assertEquals(Project::STATUS_COMPLETED, $project->status);
        $this->assertEquals(100, $project->progress_percentage);

        $this->assertDatabaseHas('project_logs', [
            'project_id' => $project->id,
            'title' => 'Update Status: Selesai',
            'progress_after' => 100,
        ]);
    }

    public function test_user_can_add_project_progress_log(): void
    {
        $project = Project::create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'name' => 'Integrasi Payment Gateway',
            'status' => Project::STATUS_IN_PROGRESS,
            'progress_percentage' => 25,
            'budget' => 15000000,
        ]);

        $response = $this->actingAs($this->user)->post(route('project-logs.store', $project->id), [
            'title' => 'Webhook QRIS Teruji',
            'note' => 'Pengujian sandbox webhook QRIS berhasil dengan 10 transaksi sukses',
            'progress_after' => 60,
            'logged_at' => now()->toDateTimeString(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('project_logs', [
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'title' => 'Webhook QRIS Teruji',
            'progress_after' => 60,
        ]);

        $project->refresh();
        $this->assertEquals(60, $project->progress_percentage);
    }

    public function test_user_can_delete_project_and_logs_cascade(): void
    {
        $project = Project::create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'name' => 'Proyek Dibatalkan',
            'status' => Project::STATUS_CANCELLED,
            'progress_percentage' => 10,
            'budget' => 5000000,
        ]);

        ProjectLog::create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'title' => 'Log Awal',
            'progress_after' => 10,
            'logged_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->delete(route('projects.destroy', $project->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertDatabaseMissing('project_logs', ['project_id' => $project->id]);
    }
}
