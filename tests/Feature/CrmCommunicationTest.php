<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Client;
use App\Models\Message;
use App\Models\Survey;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Tests\TestCase;

class CrmCommunicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    private function makeTicket(User $user, Client $client, string $status = 'Tutup'): Ticket
    {
        return Ticket::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'ticket_number' => 'TK-' . str_pad((string) (Ticket::count() + 1), 3, '0', STR_PAD_LEFT),
            'subject' => 'Keluhan uji',
            'description' => 'Deskripsi uji',
            'priority' => 'Sedang',
            'status' => $status,
            'due_date' => now()->addDay(),
        ]);
    }

    public function test_inbox_shows_only_own_clients_and_marks_incoming_as_read(): void
    {
        $this->markTestSkipped('Fitur dinonaktifkan sementara.');
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $client = Client::create(['user_id' => $owner->id, 'name' => 'Budi', 'address' => 'x']);
        $foreign = Client::create(['user_id' => $other->id, 'name' => 'Orang Lain', 'address' => 'y']);

        Message::create(['user_id' => $owner->id, 'client_id' => $client->id, 'direction' => 'Masuk', 'channel' => 'WhatsApp', 'content' => 'Halo', 'is_read' => false]);

        $this->actingAs($owner)->get(route('crm.messages.index', ['client' => $client->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('CRM/Messages/Index')
                ->has('clients', 1)
                ->where('selectedClient.id', $client->id)
                ->has('thread', 1));

        $this->assertDatabaseHas('messages', ['client_id' => $client->id, 'is_read' => true]);

        // Klien milik user lain tidak boleh dibuka
        $this->actingAs($owner)->get(route('crm.messages.index', ['client' => $foreign->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('selectedClient', null));
    }

    public function test_user_can_send_outgoing_message_to_own_client_only(): void
    {
        $this->markTestSkipped('Fitur dinonaktifkan sementara.');
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $client = Client::create(['user_id' => $owner->id, 'name' => 'Budi', 'address' => 'x']);
        $foreign = Client::create(['user_id' => $other->id, 'name' => 'Lain', 'address' => 'y']);

        $this->actingAs($owner)->post(route('crm.messages.store', $client), [
            'channel' => 'WhatsApp',
            'content' => 'Faktur sudah terbit',
        ])->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'user_id' => $owner->id,
            'client_id' => $client->id,
            'direction' => 'Keluar',
            'channel' => 'WhatsApp',
        ]);

        $this->actingAs($owner)->post(route('crm.messages.store', $foreign), [
            'channel' => 'Email',
            'content' => 'Tidak boleh',
        ])->assertNotFound();
    }

    public function test_campaign_draft_is_saved_as_draft_and_scoped_to_user(): void
    {
        $this->markTestSkipped('Fitur dinonaktifkan sementara.');
        $owner = User::factory()->create();

        $this->actingAs($owner)->post(route('crm.campaigns.store'), [
            'title' => 'Diskon Akhir Tahun',
            'channel' => 'Email',
            'content' => 'Diskon 20% sampai 31 Desember',
            'status' => 'Terkirim', // harus diabaikan, selalu Draft
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('campaigns', ['user_id' => $owner->id, 'title' => 'Diskon Akhir Tahun', 'status' => 'Draft']);

        $campaign = Campaign::first();
        $other = User::factory()->create();
        $this->actingAs($other)->delete(route('crm.campaigns.destroy', $campaign))->assertNotFound();
        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id]);
    }

    public function test_campaign_rejects_invalid_channel(): void
    {
        $this->markTestSkipped('Fitur dinonaktifkan sementara.');
        $owner = User::factory()->create();

        $this->actingAs($owner)->post(route('crm.campaigns.store'), [
            'title' => 'X',
            'channel' => 'SMS',
            'content' => 'Y',
        ])->assertSessionHasErrors('channel');
    }

    public function test_user_can_rate_closed_ticket_with_valid_score(): void
    {
        $owner = User::factory()->create();
        $client = Client::create(['user_id' => $owner->id, 'name' => 'Budi', 'address' => 'x']);
        $ticket = $this->makeTicket($owner, $client, 'Tutup');

        $this->actingAs($owner)->post(route('crm.surveys.store', $ticket), [
            'rating' => 5,
            'feedback' => 'Cepat tanggap',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('surveys', [
            'ticket_id' => $ticket->id,
            'client_id' => $client->id,
            'rating' => 5,
        ]);
    }

    public function test_rating_is_rejected_outside_1_to_5(): void
    {
        $owner = User::factory()->create();
        $client = Client::create(['user_id' => $owner->id, 'name' => 'Budi', 'address' => 'x']);
        $ticket = $this->makeTicket($owner, $client, 'Tutup');

        $this->actingAs($owner)->post(route('crm.surveys.store', $ticket), ['rating' => 0])
            ->assertSessionHasErrors('rating');
        $this->actingAs($owner)->post(route('crm.surveys.store', $ticket), ['rating' => 6])
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('surveys', 0);
    }

    public function test_survey_is_refused_for_open_ticket(): void
    {
        $owner = User::factory()->create();
        $client = Client::create(['user_id' => $owner->id, 'name' => 'Budi', 'address' => 'x']);
        $ticket = $this->makeTicket($owner, $client, 'Buka');

        $this->actingAs($owner)->post(route('crm.surveys.store', $ticket), ['rating' => 4])
            ->assertStatus(422);

        $this->assertDatabaseCount('surveys', 0);
    }

    public function test_ticket_can_only_be_surveyed_once(): void
    {
        $owner = User::factory()->create();
        $client = Client::create(['user_id' => $owner->id, 'name' => 'Budi', 'address' => 'x']);
        $ticket = $this->makeTicket($owner, $client, 'Tutup');

        $this->actingAs($owner)->post(route('crm.surveys.store', $ticket), ['rating' => 4]);
        $this->actingAs($owner)->post(route('crm.surveys.store', $ticket), ['rating' => 1])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('surveys', 1);
        $this->assertDatabaseHas('surveys', ['ticket_id' => $ticket->id, 'rating' => 4]);
    }

    public function test_user_cannot_survey_other_users_ticket(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $client = Client::create(['user_id' => $owner->id, 'name' => 'Budi', 'address' => 'x']);
        $ticket = $this->makeTicket($owner, $client, 'Tutup');

        $this->actingAs($intruder)->post(route('crm.surveys.store', $ticket), ['rating' => 5])
            ->assertNotFound();

        $this->assertDatabaseCount('surveys', 0);
    }
}
