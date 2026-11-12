<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_guide(): void
    {
        $response = $this->get('/guide');

        $response->assertRedirect('/login');
    }

    public function test_guest_is_redirected_to_login_when_accessing_panduan_alias(): void
    {
        $response = $this->get('/panduan');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_guide_with_inertia_props(): void
    {
        $user = User::factory()->create([
            'name' => 'Owner Testing',
            'email' => 'owner@example.com',
        ]);

        $response = $this->actingAs($user)->get('/guide');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Guide/Index')
            ->where('appVersion', '0.11')
            ->has('user', fn (Assert $userProps) => $userProps
                ->where('name', 'Owner Testing')
                ->where('email', 'owner@example.com')
            )
        );
    }

    public function test_panduan_alias_redirects_to_guide_route_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/panduan');

        $response->assertRedirect('/guide');
    }
}
