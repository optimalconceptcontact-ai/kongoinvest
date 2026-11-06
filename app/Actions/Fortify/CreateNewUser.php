<?php

namespace App\Actions\Fortify;

use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Models\EntrepreneurProfile;
use App\Models\InvestorProfile;
use App\Models\KycProfile;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(private SubscriptionService $subscriptions) {}

    /**
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => $this->passwordRules(),
            // Only entrepreneur/investor may self-register — admin is
            // never assignable from this form (spec §9). "Professional"
            // is a subscription plan, not a role, so it never appears
            // here (corrected — an earlier version of this action
            // wrongly created a ProfessionalProfile for a fourth role).
            'role' => ['required', 'string', 'in:'.implode(',', array_map(
                fn (UserRole $r) => $r->value,
                UserRole::registrable(),
            ))],
        ])->validate();

        $role = UserRole::from($input['role']);

        return DB::transaction(function () use ($input, $role) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
            ]);

            $user->assignRole($role->value);

            // Every user gets an empty profile row for their role at
            // registration — later onboarding steps fill it in, but
            // downstream code (matching, dashboards) can always rely on
            // the relationship existing rather than checking for null.
            match ($role) {
                UserRole::Entrepreneur => EntrepreneurProfile::create([
                    'user_id' => $user->id,
                    // country_code is not mass-assignable; DB default
                    // ('CD') plus a CHECK constraint enforce it (spec §15).
                ]),
                UserRole::Investor => InvestorProfile::create([
                    'user_id' => $user->id,
                ]),
                default => null,
            };

            // Every user starts on Free (spec §29) — no separate "pick a
            // plan" step blocks getting into the product.
            $this->subscriptions->assignFreePlan($user);

            // Same principle as the profile row above: the KYC record
            // always exists (status=draft) so later code never has to
            // special-case "user has no KYC profile at all" — it just
            // checks the status.
            KycProfile::create(['user_id' => $user->id, 'status' => KycStatus::Draft->value]);

            return $user;
        });
    }
}
