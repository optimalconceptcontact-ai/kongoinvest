<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class, // must run first — AdminUserSeeder assigns a role
            SubscriptionPlansSeeder::class, // must run before AdminUserSeeder/registration — CreateNewUser assigns Free
            AdminUserSeeder::class,
            SectorsSeeder::class,
            StagesSeeder::class,
            MatchingWeightsSeeder::class,
            FaqSeeder::class,
            // No PartnersSeeder / TestimonialsSeeder — spec §65/§69: these
            // are real-world content an admin publishes (Phase 7 CRUD),
            // never fabricated data. The tables exist and are ready.
        ]);
    }
}
