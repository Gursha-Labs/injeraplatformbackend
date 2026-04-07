<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed the application's roles and permissions.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'web';

        $permissions = [
            'view_withdrawals',
            'create_withdrawals',
            'review_withdrawals',
            'process_withdrawals',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => $guard,
            ]);
        }

        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => $guard,
        ]);

        $userRole = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => $guard,
        ]);

        $advertiserRole = Role::firstOrCreate([
            'name' => 'advertiser',
            'guard_name' => $guard,
        ]);

        $paymentProcessorRole = Role::firstOrCreate([
            'name' => 'payment_processor',
            'guard_name' => $guard,
        ]);

        // Admin gets all current permissions.
        $adminRole->syncPermissions(Permission::where('guard_name', $guard)->get());

        // User can request and view withdrawals.
        $userRole->syncPermissions([
            'create_withdrawals',
            'view_withdrawals',
        ]);

        // Advertiser has no permissions for now.
        $advertiserRole->syncPermissions([]);

        // Payment processor reviews and processes payouts.
        $paymentProcessorRole->syncPermissions([
            'view_withdrawals',
            'review_withdrawals',
            'process_withdrawals',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
