<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        Permission::create(['name' => 'points.create']);

        // Create roles
        $userRole = Role::create(['name' => 'user']);
        $staffRole = Role::create(['name' => 'staff']);
        $adminRole = Role::create(['name' => 'admin']);

        // Assign permissions
        $adminRole->givePermissionTo('points.create');
    }
}
