<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        Permission::create(['name' => 'points.create']);

        // Idea permissions
        Permission::create(['name' => 'idea.edit']);
        Permission::create(['name' => 'idea.submit']);
        Permission::create(['name' => 'idea.delete']);
        Permission::create(['name' => 'idea.review']);
        Permission::create(['name' => 'idea.implement']);
        Permission::create(['name' => 'idea.manage_collaborators']);
        Permission::create(['name' => 'idea.enable_collaboration']);
        Permission::create(['name' => 'idea.disable_collaboration']);

        // Create roles
        $userRole = Role::create(['name' => 'user']);
        $staffRole = Role::create(['name' => 'staff']);
        $adminRole = Role::create(['name' => 'admin']);

        // Assign permissions
        $adminRole->givePermissionTo('points.create');
        $adminRole->givePermissionTo(['idea.review', 'idea.implement']);

        $userRole->givePermissionTo([
            'idea.edit',
            'idea.submit',
            'idea.delete',
            'idea.enable_collaboration',
            'idea.disable_collaboration',
        ]);

        $staffRole->givePermissionTo([
            'idea.edit',
            'idea.submit',
            'idea.delete',
            'idea.enable_collaboration',
            'idea.disable_collaboration',
        ]);
    }
}
