<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Interaction;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmInteractionTicketTest extends TestCase
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
            'name' => 'PT Mitra Sejahtera',
            'email' => 'contact@mitra.test',
            'phone' => '08123456789',
            'address' => 'Jl. Jend. Sudirman No. 10 Jakarta',
            'source' => 'Website',
        ]);
    }

    public function test_guest_cannot_access_interactions_or_tickets(): void
    {
        $this->get(route('crm.interactions.index'))->assertRedirect(route('login'));
        $this->get(route('crm.tickets.index'))->assertRedirect(route('login'));
    }

    public function test_user_can_view_interactions_page_with_defaults(): void
    {
        $response = $this->actingAs($this->user)->get(route('crm.interactions.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('CRM/Interactions/Index')
            ->has('interactions.data')
            ->has('metrics')
            ->has('clients')
        );
    }

    public function test_user_can_create_interaction(): void
    {
        $scheduledAt = now()->addDays(2)->toDateTimeString();

        $response = $this->actingAs($this->user)->post(route('crm.interactions.store'), [
            'client_id' => $this->client->id,
            'type' => 'Telepon',
            'scheduled_at' => $scheduledAt,
            'notes' => 'Follow up penawaran proposal ERP',
            'status' => 'Pending',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('interactions', [
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'type' => 'Telepon',
            'notes' => 'Follow up penawaran proposal ERP',
            'status' => 'Pending',
        ]);
    }

    public function test_user_can_toggle_interaction_status(): void
    {
        $interaction = Interaction::create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'type' => 'Meeting',
            'scheduled_at' => now()->addHour(),
            'notes' => 'Meeting koordinasi',
            'status' => 'Pending',
        ]);

        // Toggle ke Selesai
        $response = $this->actingAs($this->user)->patch(route('crm.interactions.toggle', $interaction->id));
        $response->assertRedirect();
        $this->assertEquals('Selesai', $interaction->fresh()->status);

        // Toggle kembali ke Pending
        $response = $this->actingAs($this->user)->patch(route('crm.interactions.toggle', $interaction->id));
        $response->assertRedirect();
        $this->assertEquals('Pending', $interaction->fresh()->status);
    }

    public function test_user_can_update_and_delete_interaction(): void
    {
        $interaction = Interaction::create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'type' => 'Email',
            'scheduled_at' => now()->addDay(),
            'notes' => 'Kirim draf kontrak',
            'status' => 'Pending',
        ]);

        $updateResponse = $this->actingAs($this->user)->put(route('crm.interactions.update', $interaction->id), [
            'client_id' => $this->client->id,
            'type' => 'Meeting',
            'scheduled_at' => now()->addDays(3)->toDateTimeString(),
            'notes' => 'Kirim draf kontrak dan diskusi tatap muka',
            'status' => 'Selesai',
        ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('interactions', [
            'id' => $interaction->id,
            'type' => 'Meeting',
            'status' => 'Selesai',
        ]);

        $deleteResponse = $this->actingAs($this->user)->delete(route('crm.interactions.destroy', $interaction->id));
        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('interactions', ['id' => $interaction->id]);
    }

    public function test_interaction_multi_tenant_isolation(): void
    {
        $otherUser = User::factory()->create();
        $otherClient = Client::create([
            'user_id' => $otherUser->id,
            'name' => 'Klien Luar',
            'address' => 'Jl. Luar No. 99',
        ]);

        $otherInteraction = Interaction::create([
            'user_id' => $otherUser->id,
            'client_id' => $otherClient->id,
            'type' => 'Telepon',
            'scheduled_at' => now()->addDay(),
            'status' => 'Pending',
        ]);

        // User tidak boleh bisa mengupdate atau menghapus interaksi milik orang lain
        $this->actingAs($this->user)
            ->put(route('crm.interactions.update', $otherInteraction->id), [
                'client_id' => $this->client->id,
                'type' => 'Telepon',
                'scheduled_at' => now()->addDay()->toDateTimeString(),
                'status' => 'Selesai',
            ])
            ->assertNotFound();

        $this->actingAs($this->user)
            ->delete(route('crm.interactions.destroy', $otherInteraction->id))
            ->assertNotFound();
    }

    public function test_user_can_view_tickets_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('crm.tickets.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('CRM/Tickets/Index')
            ->has('tickets.data')
            ->has('metrics')
            ->has('nextTicketNumber')
            ->where('nextTicketNumber', 'TK-001')
        );
    }

    public function test_ticket_auto_numbering_on_store(): void
    {
        $firstResponse = $this->actingAs($this->user)->post(route('crm.tickets.store'), [
            'client_id' => $this->client->id,
            'subject' => 'Kendala Download Faktur PDF',
            'description' => 'Link download tidak dapat dibuka pada browser mobile.',
            'priority' => 'Tinggi',
            'status' => 'Buka',
            'due_date' => now()->addHours(12)->toDateTimeString(),
        ]);

        $firstResponse->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'user_id' => $this->user->id,
            'ticket_number' => 'TK-001',
            'subject' => 'Kendala Download Faktur PDF',
            'priority' => 'Tinggi',
        ]);

        // Tiket kedua auto-increment menjadi TK-002
        $secondResponse = $this->actingAs($this->user)->post(route('crm.tickets.store'), [
            'client_id' => $this->client->id,
            'subject' => 'Permintaan Revisi NPWP',
            'description' => 'Perubahan format nomor NPWP pada faktur bulan November.',
            'priority' => 'Sedang',
            'status' => 'Buka',
            'due_date' => now()->addDays(2)->toDateTimeString(),
        ]);

        $secondResponse->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'user_id' => $this->user->id,
            'ticket_number' => 'TK-002',
            'subject' => 'Permintaan Revisi NPWP',
        ]);
    }

    public function test_user_can_quick_update_ticket_status(): void
    {
        $ticket = Ticket::create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'ticket_number' => 'TK-001',
            'subject' => 'Masalah Login',
            'description' => 'User lupa password',
            'priority' => 'Rendah',
            'status' => 'Buka',
            'due_date' => now()->addDay(),
        ]);

        $response = $this->actingAs($this->user)->patch(route('crm.tickets.update-status', $ticket->id), [
            'status' => 'Diproses',
        ]);

        $response->assertRedirect();
        $this->assertEquals('Diproses', $ticket->fresh()->status);

        $response2 = $this->actingAs($this->user)->patch(route('crm.tickets.update-status', $ticket->id), [
            'status' => 'Selesai',
        ]);

        $response2->assertRedirect();
        $this->assertEquals('Selesai', $ticket->fresh()->status);
    }

    public function test_ticket_is_overdue_helper_works(): void
    {
        $overdueTicket = Ticket::create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'ticket_number' => 'TK-001',
            'subject' => 'Keluhan Kritis',
            'description' => 'Kritis',
            'priority' => 'Tinggi',
            'status' => 'Buka',
            'due_date' => now()->subDay(), // Kemarin
        ]);

        $this->assertTrue($overdueTicket->isOverdue());

        // Jika sudah selesai, tidak lagi overdue
        $overdueTicket->update(['status' => 'Selesai']);
        $this->assertFalse($overdueTicket->isOverdue());
    }

    public function test_ticket_multi_tenant_isolation(): void
    {
        $otherUser = User::factory()->create();
        $otherClient = Client::create([
            'user_id' => $otherUser->id,
            'name' => 'Klien Lain',
            'address' => 'Jl. Lain No. 88',
        ]);

        $otherTicket = Ticket::create([
            'user_id' => $otherUser->id,
            'client_id' => $otherClient->id,
            'ticket_number' => 'TK-001',
            'subject' => 'Keluhan Luar',
            'description' => 'Deskripsi',
            'priority' => 'Sedang',
            'status' => 'Buka',
            'due_date' => now()->addDay(),
        ]);

        $this->actingAs($this->user)
            ->patch(route('crm.tickets.update-status', $otherTicket->id), ['status' => 'Diproses'])
            ->assertNotFound();

        $this->actingAs($this->user)
            ->delete(route('crm.tickets.destroy', $otherTicket->id))
            ->assertNotFound();
    }
}
