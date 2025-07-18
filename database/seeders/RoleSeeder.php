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
            // Créer les rôles
        $admin = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $teacher = Role::create(['name' => 'teacher', 'guard_name' => 'api']);
        $parent = Role::create(['name' => 'parent', 'guard_name' => 'api']);
        $student = Role::create(['name' => 'student', 'guard_name' => 'api']);

        // Créer les permissions
        Permission::create(['name' => 'manage-students', 'guard_name' => 'api']);
        Permission::create(['name' => 'manage-notes', 'guard_name' => 'api']);
        Permission::create(['name' => 'manage-payments', 'guard_name' => 'api']);
        Permission::create(['name' => 'manage-schedules', 'guard_name' => 'api']);
        Permission::create(['name' => 'send-messages', 'guard_name' => 'api']);
        Permission::create(['name' => 'view-own-data', 'guard_name' => 'api']);

        // Assigner les permissions aux rôles
        $admin->syncPermissions(['manage-students', 'manage-notes', 'manage-payments', 'manage-schedules', 'send-messages']);
        $teacher->syncPermissions(['manage-notes', 'manage-schedules', 'send-messages']);
        $parent->syncPermissions(['view-own-data', 'send-messages']);
        $student->syncPermissions(['view-own-data']);
    
    }
}
