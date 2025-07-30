<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Réinitialiser le cache des permissions
        app()['cache']->forget('spatie.permission.cache');

        // Créer les permissions
        Permission::create(['name' => 'view payments']);
        Permission::create(['name' => 'create payments']);
        Permission::create(['name' => 'update payments']);
        Permission::create(['name' => 'delete payments']);
        Permission::create(['name' => 'confirm payments']);

        // Créer les rôles et assigner les permissions
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
            'view payments',
            'create payments',
            'update payments',
            'delete payments',
            'confirm payments'
        ]);

        $accountant = Role::create(['name' => 'accountant']);
        $accountant->givePermissionTo([
            'view payments',
            'create payments',
            'update payments',
            'delete payments',
            'confirm payments'
        ]);

        $parent = Role::create(['name' => 'parent']);
        $parent->givePermissionTo(['view payments']);

        $student = Role::create(['name' => 'student']);
        $student->givePermissionTo(['view payments']);

        $teacher = Role::create(['name' => 'teacher']);
        $teacher->givePermissionTo(['view payments']);

        // Créer un utilisateur admin par défaut
        $user = \App\Models\User::firstOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Admin User',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('admin');
    }
}