<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Permissions grouped by resource
        $permissions = [
            // properties
            'properties.view', 'properties.create', 'properties.update', 'properties.delete', 'properties.publish',
            // bookings
            'bookings.view', 'bookings.create', 'bookings.update', 'bookings.cancel', 'bookings.refund',
            // tasks
            'tasks.view', 'tasks.create', 'tasks.update', 'tasks.assign', 'tasks.complete',
            // users
            'users.view', 'users.create', 'users.update', 'users.delete', 'users.impersonate',
            // hosts
            'hosts.view', 'hosts.approve_kyc', 'hosts.suspend',
            // providers
            'providers.view', 'providers.approve_kyc', 'providers.suspend',
            // financial
            'commissions.view', 'payouts.view', 'payouts.process',
            // disputes
            'disputes.view', 'disputes.respond', 'disputes.resolve',
            // platform admin
            'admin.access_panel', 'admin.view_reports', 'admin.manage_plans', 'admin.manage_amenities',
            // reviews
            'reviews.view', 'reviews.respond', 'reviews.moderate',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // SuperAdmin — all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // Host — manages own properties/bookings/tasks
        $host = Role::firstOrCreate(['name' => 'Host', 'guard_name' => 'web']);
        $host->syncPermissions([
            'properties.view', 'properties.create', 'properties.update', 'properties.delete', 'properties.publish',
            'bookings.view', 'bookings.create', 'bookings.update', 'bookings.cancel',
            'tasks.view', 'tasks.create', 'tasks.update', 'tasks.assign',
            'commissions.view', 'payouts.view',
            'disputes.view', 'disputes.respond',
            'reviews.view', 'reviews.respond',
        ]);

        // Provider — receives + completes tasks
        $provider = Role::firstOrCreate(['name' => 'Provider', 'guard_name' => 'web']);
        $provider->syncPermissions([
            'tasks.view', 'tasks.update', 'tasks.complete',
            'reviews.view',
        ]);

        // Guest — books + reviews
        $guest = Role::firstOrCreate(['name' => 'Guest', 'guard_name' => 'web']);
        $guest->syncPermissions([
            'bookings.view', 'bookings.create',
            'reviews.view',
            'disputes.view',
        ]);
    }
}
