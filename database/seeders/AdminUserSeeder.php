<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@kongoinvestment.app'],
            [
                'name' => 'KongoInvestment Admin',
                'password' => Hash::make('password'), // change immediately outside local/dev
                'email_verified_at' => now(),
            ],
        );

        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }
    }
}
