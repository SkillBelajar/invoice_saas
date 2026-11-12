<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_dashboard_with_inertia_props(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('metrics')
            ->has('aging')
            ->has('monthlyTrend')
            ->has('attentionInvoices')
            ->has('recentInvoices')
        );
    }

    public function test_dashboard_calculates_financial_metrics_and_tenant_isolation(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $clientA = Client::create([
            'user_id' => $userA->id,
            'name' => 'Klien PT User A',
            'address' => 'Jl. Jenderal Sudirman No. 10, Jakarta',
        ]);

        $clientB = Client::create([
            'user_id' => $userB->id,
            'name' => 'Klien PT User B',
            'address' => 'Jl. Gatot Subroto No. 20, Jakarta',
        ]);

        // Invoices for User A
        Invoice::create([
            'user_id' => $userA->id,
            'client_id' => $clientA->id,
            'invoice_number' => 'INV-202611-001',
            'issue_date' => Carbon::today(),
            'due_date' => Carbon::today()->addDays(7),
            'status' => 'paid',
            'subtotal' => 1000000,
            'discount_amount' => 0,
            'tax_amount' => 110000,
            'grand_total' => 1110000,
        ]);

        Invoice::create([
            'user_id' => $userA->id,
            'client_id' => $clientA->id,
            'invoice_number' => 'INV-202611-002',
            'issue_date' => Carbon::today(),
            'due_date' => Carbon::today()->subDays(5),
            'status' => 'unpaid',
            'subtotal' => 2000000,
            'discount_amount' => 0,
            'tax_amount' => 220000,
            'grand_total' => 2220000,
        ]);

        // Invoice for User B (Must NOT bleed into User A's dashboard)
        Invoice::create([
            'user_id' => $userB->id,
            'client_id' => $clientB->id,
            'invoice_number' => 'INV-202611-999',
            'issue_date' => Carbon::today(),
            'due_date' => Carbon::today()->addDays(3),
            'status' => 'paid',
            'subtotal' => 50000000,
            'discount_amount' => 0,
            'tax_amount' => 5500000,
            'grand_total' => 55500000,
        ]);

        $response = $this->actingAs($userA)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('metrics.total_revenue', 1110000)
            ->where('metrics.total_receivables', 2220000)
            ->where('metrics.total_invoices_count', 2)
            ->where('metrics.paid_count', 1)
            ->where('metrics.unpaid_count', 1)
            ->where('metrics.total_clients_count', 1)
        );
    }

    public function test_dashboard_categorizes_aging_piutang_accurately(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Klien Aging Test',
            'address' => 'Jl. Kebon Jeruk No. 5, Jakarta',
        ]);

        $today = Carbon::today();

        // 1. Current (Belum jatuh tempo: due in 5 days)
        Invoice::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'invoice_number' => 'INV-AGING-01',
            'issue_date' => $today,
            'due_date' => $today->copy()->addDays(5),
            'status' => 'unpaid',
            'subtotal' => 100000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 100000,
        ]);

        // 2. Overdue 1-30 days (10 days late)
        Invoice::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'invoice_number' => 'INV-AGING-02',
            'issue_date' => $today->copy()->subDays(20),
            'due_date' => $today->copy()->subDays(10),
            'status' => 'unpaid',
            'subtotal' => 200000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 200000,
        ]);

        // 3. Overdue 31-60 days (45 days late)
        Invoice::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'invoice_number' => 'INV-AGING-03',
            'issue_date' => $today->copy()->subDays(60),
            'due_date' => $today->copy()->subDays(45),
            'status' => 'unpaid',
            'subtotal' => 300000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 300000,
        ]);

        // 4. Overdue >60 days (80 days late)
        Invoice::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'invoice_number' => 'INV-AGING-04',
            'issue_date' => $today->copy()->subDays(90),
            'due_date' => $today->copy()->subDays(80),
            'status' => 'unpaid',
            'subtotal' => 400000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 400000,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('aging.total', 1000000)
            ->where('aging.current.amount', 100000)
            ->where('aging.current.count', 1)
            ->where('aging.overdue_1_30.amount', 200000)
            ->where('aging.overdue_1_30.count', 1)
            ->where('aging.overdue_31_60.amount', 300000)
            ->where('aging.overdue_31_60.count', 1)
            ->where('aging.overdue_60_plus.amount', 400000)
            ->where('aging.overdue_60_plus.count', 1)
        );
    }

    public function test_user_can_update_invoice_status_via_route(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Klien Update Status',
            'address' => 'Jl. Thamrin No. 8, Jakarta',
        ]);

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'invoice_number' => 'INV-STAT-01',
            'issue_date' => Carbon::today(),
            'due_date' => Carbon::today()->addDays(7),
            'status' => 'unpaid',
            'subtotal' => 500000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 500000,
        ]);

        $response = $this->actingAs($user)->patch("/invoices/{$invoice->id}/status", [
            'status' => 'paid',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'paid',
        ]);
    }

    public function test_user_cannot_update_status_of_other_tenants_invoice(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $clientB = Client::create([
            'user_id' => $userB->id,
            'name' => 'Klien User B',
            'address' => 'Jl. Asia Afrika No. 12, Bandung',
        ]);

        $invoiceB = Invoice::create([
            'user_id' => $userB->id,
            'client_id' => $clientB->id,
            'invoice_number' => 'INV-USERB-01',
            'issue_date' => Carbon::today(),
            'due_date' => Carbon::today()->addDays(7),
            'status' => 'unpaid',
            'subtotal' => 1000000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 1000000,
        ]);

        $response = $this->actingAs($userA)->patch("/invoices/{$invoiceB->id}/status", [
            'status' => 'paid',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('invoices', [
            'id' => $invoiceB->id,
            'status' => 'unpaid',
        ]);
    }
}
