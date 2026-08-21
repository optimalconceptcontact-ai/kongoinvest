<?php

namespace Database\Seeders;

use App\Enums\SubscriptionFeature as F;
use App\Enums\SubscriptionPlanKey;
use App\Enums\UserRole;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'key' => SubscriptionPlanKey::Free->value,
                'name' => 'Free',
                'role_scope' => null, // any role — spec §29
                'price_minor' => 0,
                'limits' => [
                    'max_active_projects' => 1,
                    'ai_monthly_token_quota' => 20000,
                ],
                'features' => [],
                'order' => 0,
            ],
            [
                'key' => SubscriptionPlanKey::Entrepreneur->value,
                'name' => 'Entrepreneur',
                'role_scope' => UserRole::Entrepreneur->value, // spec §30
                'price_minor' => 2900, // $29.00
                'limits' => [
                    'max_active_projects' => 3,
                    'ai_monthly_token_quota' => 200000,
                ],
                'features' => [
                    F::AdvancedMatching->value,
                    F::AiCoach->value,
                    F::ContactInfoVisible->value,
                    F::NegotiationTools->value,
                    F::DealRoomAccess->value,
                ],
                'order' => 1,
            ],
            [
                'key' => SubscriptionPlanKey::Investor->value,
                'name' => 'Investor',
                'role_scope' => UserRole::Investor->value, // spec §31
                'price_minor' => 4900, // $49.00
                'limits' => [
                    'ai_monthly_token_quota' => 200000,
                ],
                'features' => [
                    F::AdvancedMatching->value,
                    F::AiInvestmentAdvisor->value,
                    F::ContactInfoVisible->value,
                    F::PortfolioTracking->value,
                    F::DealRoomAccess->value,
                ],
                'order' => 2,
            ],
            [
                'key' => SubscriptionPlanKey::Professional->value,
                'name' => 'Professional',
                // Role-agnostic, same as Free — corrected. "Professional"
                // is a plan tier for accompaniment/advisory features
                // (Escrow, KYC, contracts, Deal Room), not a fourth user
                // role. An entrepreneur AND an investor may both
                // subscribe to it; an earlier version of this seeder
                // wrongly scoped it to a non-existent
                // UserRole::Professional, which would have made the plan
                // unreachable by anyone (spec §32 never actually
                // describes Professional as its own persona — §130's
                // "Professional" role refers to accompanying experts,
                // a separate concept this scaffold doesn't model as a
                // registrable account type).
                'role_scope' => null,
                'price_minor' => 0, // "Sur devis" — no fixed self-service price
                'limits' => [],
                'features' => [
                    F::EscrowAccess->value,
                    F::KycAccess->value,
                    F::ContractsAccess->value,
                    F::DealRoomAccess->value,
                ],
                'order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['key' => $plan['key']],
                $plan + ['currency' => 'USD', 'billing_period' => 'monthly', 'is_active' => true],
            );
        }
    }
}
