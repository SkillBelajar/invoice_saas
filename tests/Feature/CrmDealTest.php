<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Deal;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmDealTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_crm_deals(): void
    {
        $response = $this->get(route('crm.deals.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_own_crm_deals_kanban(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'PT Maju Bersama',
            'email' => 'maju@example.com',
            'phone' => '08123456789',
            'address' => 'Jl. Merdeka No. 1',
            'source' => 'Pameran Bisnis',
        ]);

        $deal = Deal::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'title' => 'Pengadaan Lisensi ERP',
            'amount' => 50000000,
            'stage' => 'Proposal',
            'expected_close_date' => now()->addDays(14)->toDateString(),
        ]);

        // Deal from another user should not leak
        $otherUser = User::factory()->create();
        $otherClient = Client::create([
            'user_id' => $otherUser->id,
            'name' => 'PT Lain',
            'address' => 'Jl. Lain',
        ]);
        Deal::create([
            'user_id' => $otherUser->id,
            'client_id' => $otherClient->id,
            'title' => 'Deal Rahasia Orang Lain',
            'amount' => 99000000,
            'stage' => 'Proposal',
        ]);

        $response = $this->actingAs($user)->get(route('crm.deals.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('CRM/Deals')
            ->has('stages', 5)
            ->has('dealsByStage.Proposal', 1)
            ->where('dealsByStage.Proposal.0.id', $deal->id)
            ->where('dealsByStage.Proposal.0.title', 'Pengadaan Lisensi ERP')
            ->where('metrics.total_deals', 1)
            ->where('metrics.total_value', 50000000)
            ->where('metrics.active_value', 50000000)
            ->has('clients', 1)
        );
    }

    public function test_user_can_create_deal_for_own_client(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'CV Mitra Sejahtera',
            'address' => 'Jl. Sudirman 45',
            'source' => 'Rujukan Klien',
        ]);

        $response = $this->actingAs($user)->post(route('crm.deals.store'), [
            'client_id' => $client->id,
            'title' => 'Instalasi Server Cloud',
            'amount' => 15000000,
            'stage' => 'Prospek',
            'expected_close_date' => now()->addMonth()->toDateString(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('deals', [
            'user_id' => $user->id,
            'client_id' => $client->id,
            'title' => 'Instalasi Server Cloud',
            'amount' => 15000000,
            'stage' => 'Prospek',
        ]);
    }

    public function test_user_cannot_create_deal_for_other_users_client(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherClient = Client::create([
            'user_id' => $otherUser->id,
            'name' => 'Client Orang Lain',
            'address' => 'Jl. Lain',
        ]);

        $response = $this->actingAs($user)->post(route('crm.deals.store'), [
            'client_id' => $otherClient->id,
            'title' => 'Peluang Ilegal',
            'amount' => 1000000,
            'stage' => 'Prospek',
        ]);

        $response->assertSessionHasErrors('client_id');
        $this->assertDatabaseMissing('deals', [
            'title' => 'Peluang Ilegal',
        ]);
    }

    public function test_user_can_update_deal_stage(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'PT Sukses Mandiri',
            'address' => 'Jl. Gatot Subroto',
        ]);
        $deal = Deal::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'title' => 'Pengadaan Laptop',
            'amount' => 25000000,
            'stage' => 'Proposal',
        ]);

        $response = $this->actingAs($user)->patch(route('crm.deals.update-stage', $deal->id), [
            'stage' => 'Negosiasi',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('deals', [
            'id' => $deal->id,
            'stage' => 'Negosiasi',
        ]);
    }

    public function test_user_cannot_update_other_users_deal_stage(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $client = Client::create([
            'user_id' => $otherUser->id,
            'name' => 'Client Lain',
            'address' => 'Jl. Lain',
        ]);
        $deal = Deal::create([
            'user_id' => $otherUser->id,
            'client_id' => $client->id,
            'title' => 'Deal Orang Lain',
            'amount' => 10000000,
            'stage' => 'Prospek',
        ]);

        $response = $this->actingAs($user)->patch(route('crm.deals.update-stage', $deal->id), [
            'stage' => 'Deal Menang',
        ]);

        $response->assertNotFound();
    }

    public function test_user_can_update_deal_details(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'PT Pelanggan 1',
            'address' => 'Jl. Mawar',
        ]);
        $deal = Deal::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'title' => 'Deal Awal',
            'amount' => 5000000,
            'stage' => 'Prospek',
        ]);

        $response = $this->actingAs($user)->put(route('crm.deals.update', $deal->id), [
            'client_id' => $client->id,
            'title' => 'Deal Diperbarui',
            'amount' => 7500000,
            'stage' => 'Negosiasi',
            'expected_close_date' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('deals', [
            'id' => $deal->id,
            'title' => 'Deal Diperbarui',
            'amount' => 7500000,
            'stage' => 'Negosiasi',
        ]);
    }

    public function test_user_can_delete_own_deal(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'PT Pelanggan 2',
            'address' => 'Jl. Melati',
        ]);
        $deal = Deal::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'title' => 'Deal Batal Hapus',
            'amount' => 3000000,
            'stage' => 'Deal Batal',
        ]);

        $response = $this->actingAs($user)->delete(route('crm.deals.destroy', $deal->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('deals', [
            'id' => $deal->id,
        ]);
    }

    public function test_user_cannot_delete_other_users_deal(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $client = Client::create([
            'user_id' => $otherUser->id,
            'name' => 'PT Pelanggan 3',
            'address' => 'Jl. Cempaka',
        ]);
        $deal = Deal::create([
            'user_id' => $otherUser->id,
            'client_id' => $client->id,
            'title' => 'Deal Orang Lain',
            'amount' => 3000000,
            'stage' => 'Deal Batal',
        ]);

        $response = $this->actingAs($user)->delete(route('crm.deals.destroy', $deal->id));

        $response->assertNotFound();
        $this->assertDatabaseHas('deals', [
            'id' => $deal->id,
        ]);
    }

    public function test_user_can_view_client_profile_with_transaction_history(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'PT Global Solusindo',
            'email' => 'contact@global.co.id',
            'phone' => '021-987654',
            'address' => 'Gedung Wisma Lt. 12',
            'tax_id' => '01.234.567.8-999.000',
            'source' => 'LinkedIn Ads',
        ]);

        // 1 Paid Invoice
        Invoice::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'invoice_number' => 'INV-202611-001',
            'issue_date' => '2026-11-01',
            'due_date' => '2026-11-15',
            'subtotal' => 10000000,
            'tax_rate' => 11,
            'tax_amount' => 1100000,
            'discount_amount' => 0,
            'grand_total' => 11100000,
            'status' => 'paid',
        ]);

        // 1 Unpaid Invoice
        Invoice::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'invoice_number' => 'INV-202611-002',
            'issue_date' => '2026-11-10',
            'due_date' => '2026-11-25',
            'subtotal' => 5000000,
            'tax_rate' => 11,
            'tax_amount' => 550000,
            'discount_amount' => 0,
            'grand_total' => 5550000,
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($user)->get(route('clients.show', $client->id));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Clients/Show')
            ->where('client.id', $client->id)
            ->where('client.name', 'PT Global Solusindo')
            ->where('client.source', 'LinkedIn Ads')
            ->has('client.invoices', 2)
            ->where('stats.total_invoiced', 16650000)
            ->where('stats.total_paid', 11100000)
            ->where('stats.total_unpaid', 5550000)
            ->where('stats.invoices_count', 2)
        );
    }
}
