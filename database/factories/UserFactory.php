<?php

namespace Database\Factories;

use App\Models\EntrepreneurProfile;
use App\Models\InvestorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'locale' => 'fr',
            'timezone' => 'Africa/Kinshasa',
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    /** Creates the user, assigns the entrepreneur role, and an empty profile forced to DRC. */
    public function entrepreneur(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('entrepreneur');
            EntrepreneurProfile::factory()->create(['user_id' => $user->id]);
        });
    }

    /** Creates the user, assigns the investor role, and an empty investor profile. */
    public function investor(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('investor');
            InvestorProfile::factory()->create(['user_id' => $user->id]);
        });
    }
}
