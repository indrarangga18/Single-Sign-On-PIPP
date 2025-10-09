<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User Management
            'view users',
            'create users',
            'edit users',
            'delete users',
            
            // SSO Management
            'manage sso',
            'view audit logs',
            
            // Service Access
            'access sahbandar',
            'access spb',
            'access shti',
            'access epit',
            
            // Service Management
            'manage sahbandar',
            'manage spb',
            'manage shti',
            'manage epit',
            
            // System Administration
            'view system stats',
            'manage system settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        
        // Super Admin - Full access
        $superAdmin = Role::create(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - System administration
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
            'view users',
            'create users',
            'edit users',
            'view audit logs',
            'access sahbandar',
            'access spb',
            'access shti',
            'access epit',
            'view system stats',
        ]);

        // Sahbandar Officer
        $sahbandar = Role::create(['name' => 'sahbandar']);
        $sahbandar->givePermissionTo([
            'access sahbandar',
            'manage sahbandar',
        ]);

        // SPB Officer
        $spb = Role::create(['name' => 'spb-officer']);
        $spb->givePermissionTo([
            'access spb',
            'manage spb',
        ]);

        // SHTI Officer
        $shti = Role::create(['name' => 'shti-officer']);
        $shti->givePermissionTo([
            'access shti',
            'manage shti',
        ]);

        // EPIT Officer
        $epit = Role::create(['name' => 'epit-officer']);
        $epit->givePermissionTo([
            'access epit',
            'manage epit',
        ]);

        // Regular User - Basic access
        $user = Role::create(['name' => 'user']);
        $user->givePermissionTo([
            'access sahbandar',
            'access spb',
            'access shti',
            'access epit',
        ]);
    }
}