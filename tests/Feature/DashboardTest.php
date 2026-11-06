<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SubscriptionPlansSeeder::class);
    }

    public function test_guests_are_redirected_to_the_home_page()
    {
        // There is no /login page (auth is a modal, spec §6) — guests
        // bounce back to the home page with a flashed authModal payload,
        // and AuthModalProvider opens the login modal on page load.
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('home'));
        $response->assertSessionHas('authModal.modal', 'login');
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->entrepreneur()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }
}
