<?php

namespace Tests\Feature;

use App\Http\Controllers\InvoiceController;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicingEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_numbering_generates_correct_format_and_increments(): void
    {
        $prefix = 'INV-' . now()->format('Ym') . '-';
        $num1 = InvoiceController::generateInvoiceNumber();
        $this->assertSame($prefix . '001', $num1);

        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'PT Mitra', 'address' => 'Jakarta']);

        Invoice::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'invoice_number' => $prefix . '001',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'subtotal' => 100000,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_rate' => 11,
            'tax_amount' => 11000,
            'grand_total' => 111000,
            'status' => 'unpaid',
        ]);

        $num2 = InvoiceController::generateInvoiceNumber();
        $this->assertSame($prefix . '002', $num2);
    }

    public function test_user_can_create_invoice_with_items_and_calculations(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Klien Alpha', 'address' => 'Jl. Merdeka']);
        $product = Product::create([
            'user_id' => $user->id,
            'name' => 'Web Dev',
            'price' => '500000.00',
            'unit' => 'Paket',
        ]);

        $response = $this->actingAs($user)->post(route('invoices.store'), [
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => 'unpaid',
            'notes' => 'Harap bayar tepat waktu',
            'discount_type' => 'percentage',
            'discount_value' => 10, // 10% diskon
            'tax_rate' => 11, // PPN 11%
            'items' => [
                [
                    'product_id' => $product->id,
                    'description' => 'Web Development',
                    'quantity' => 2,
                    'unit_price' => 500000,
                ],
                [
                    'product_id' => null,
                    'description' => 'Server Setup',
                    'quantity' => 1,
                    'unit_price' => 200000,
                ],
            ],
        ]);

        $response->assertRedirect(route('invoices.index'));

        // Subtotal: (2 * 500,000) + (1 * 200,000) = 1,200,000
        // Diskon 10%: 120,000 -> Sisa 1,080,000
        // PPN 11%: 1,080,000 * 0.11 = 118,800
        // Grand Total: 1,080,000 + 118,800 = 1,198,800
        $this->assertDatabaseHas('invoices', [
            'user_id' => $user->id,
            'client_id' => $client->id,
            'subtotal' => '1200000.00',
            'discount_amount' => '120000.00',
            'tax_rate' => '11.00',
            'tax_amount' => '118800.00',
            'grand_total' => '1198800.00',
            'status' => 'unpaid',
        ]);

        $invoice = Invoice::where('user_id', $user->id)->first();
        $this->assertNotNull($invoice);
        $this->assertCount(2, $invoice->items);
    }

    public function test_tenant_isolation_prevents_unauthorized_access(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $client1 = Client::create(['user_id' => $user1->id, 'name' => 'Klien 1', 'address' => 'Solo']);
        $client2 = Client::create(['user_id' => $user2->id, 'name' => 'Klien 2', 'address' => 'Jogja']);

        // User 1 tidak boleh buat invoice dengan client milik User 2
        $response = $this->actingAs($user1)->post(route('invoices.store'), [
            'client_id' => $client2->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => 'unpaid',
            'items' => [
                ['description' => 'Item X', 'quantity' => 1, 'unit_price' => 1000],
            ],
        ]);
        $response->assertSessionHasErrors(['client_id']);

        // Buat invoice milik User 2
        $inv2 = Invoice::create([
            'user_id' => $user2->id,
            'client_id' => $client2->id,
            'invoice_number' => 'INV-TEST-002',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'subtotal' => 1000,
            'grand_total' => 1000,
            'status' => 'unpaid',
        ]);

        // User 1 tidak boleh melihat atau menghapus invoice User 2
        $this->actingAs($user1)->get(route('invoices.show', $inv2))->assertNotFound();
        $this->actingAs($user1)->delete(route('invoices.destroy', $inv2))->assertNotFound();
    }
}
