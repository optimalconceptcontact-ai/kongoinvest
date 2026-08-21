<?php

namespace Tests\Feature\Auth;

use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // CreateNewUser assigns the chosen Spatie role and the Free plan,
        // so both seeders are required for registration to succeed.
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SubscriptionPlansSeeder::class);
    }

    public function test_registration_screen_is_not_a_dedicated_route()
    {
        // Auth is presented as React modals (spec §6) — there is no
        // dedicated GET /register page, only the POST endpoint.
        $response = $this->get(route('register.store'));

        $response->assertMethodNotAllowed();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123!',
            'password_confirmation' => 'password123!',
            'role' => 'entrepreneur',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
