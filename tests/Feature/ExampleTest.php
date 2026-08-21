<?php

namespace Tests\Feature;

use Database\Seeders\FaqSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SectorsSeeder;
use Database\Seeders\StagesSeeder;
use Database\Seeders\SubscriptionPlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The homepage renders live counts via User::role('entrepreneur')
        // and the sector/stage/plan data — it needs the seeders present.
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SubscriptionPlansSeeder::class);
        $this->seed(SectorsSeeder::class);
        $this->seed(StagesSeeder::class);
        $this->seed(FaqSeeder::class);
    }

    public function test_returns_a_successful_response()
    {
        $response = $this->get(route('home'));

        $response->assertOk();
    }
}
