<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_manage_own_clients_and_products_but_not_other_users_records(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($owner)->post(route('clients.store'), [
            'name' => 'Klien A', 'address' => 'Jakarta', 'email' => null,
            'phone' => null, 'tax_id' => null,
        ])->assertRedirect();

        $this->actingAs($owner)->post(route('products.store'), [
            'name' => 'Jasa audit', 'price' => '250000.00',
            'description' => null, 'unit' => 'Paket',
        ])->assertRedirect();

        $this->assertDatabaseHas('clients', ['user_id' => $owner->id, 'name' => 'Klien A']);
        $this->assertDatabaseHas('products', ['user_id' => $owner->id, 'name' => 'Jasa audit']);
        $this->assertCount(1, $owner->clients);
        $this->assertCount(1, $owner->products);

        $client = Client::create(['user_id' => $other->id, 'name' => 'Klien B', 'address' => 'Bandung']);
        $product = Product::create(['user_id' => $other->id, 'name' => 'Produk B', 'price' => '100.00']);

        $this->actingAs($owner)->put(route('clients.update', $client), [
            'name' => 'Disusupi', 'address' => 'Jakarta',
        ])->assertNotFound();
        $this->actingAs($owner)->delete(route('products.destroy', $product))->assertNotFound();
        $this->assertDatabaseHas('clients', ['id' => $client->id, 'name' => 'Klien B']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Produk B']);
    }
}
