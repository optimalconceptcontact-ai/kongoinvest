<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'projects.view', 'projects.create', 'projects.update', 'projects.delete',
            'projects.publish', 'projects.view-all',
            'matches.view', 'matches.create',
            'connections.create', 'connections.accept',
            'messages.view', 'messages.send',
            'payments.view', 'payments.create',
            'escrow.view', 'escrow.create', 'escrow.release', 'escrow.refund',
            'kyc.submit', 'kyc.review',
            'contracts.view', 'contracts.accept',
            'subscriptions.view', 'subscriptions.manage',
            'users.view', 'users.manage',
            'admin.dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $roles = [
            'admin' => $permissions, // full access
            'entrepreneur' => [
                'projects.view', 'projects.create', 'projects.update', 'projects.delete',
                'projects.publish', 'matches.view', 'connections.accept', 'messages.view',
                'messages.send', 'escrow.view', 'kyc.submit',
                'contracts.view', 'contracts.accept', 'subscriptions.view', 'subscriptions.manage',
            ],
            'investor' => [
                'projects.view', 'matches.view', 'connections.create', 'messages.view',
                'messages.send', 'payments.create', 'escrow.view',
                'escrow.create', 'escrow.release', 'escrow.refund', 'kyc.submit',
                'contracts.view', 'contracts.accept', 'subscriptions.view', 'subscriptions.manage',
            ],
            // No 'professional' role — corrected. Exactly three roles
            // exist (admin, entrepreneur, investor); "Professional" is
            // only a SubscriptionPlanKey either of the other two roles
            // may subscribe to (see SubscriptionPlansSeeder). An earlier
            // version of this seeder incorrectly created a fourth role
            // here with its own permission set — that was a design
            // error, not an intentional fourth persona.
        ];

        foreach ($roles as $role => $rolePermissions) {
            $roleModel = Role::firstOrCreate(['name' => $role]);
            $roleModel->syncPermissions($rolePermissions);
        }
    }
}
