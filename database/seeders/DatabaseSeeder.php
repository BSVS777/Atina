<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            AcademicManagementDemoSeeder::class,
            AffinityDemoSeeder::class,
        ]);

        $superadminUser = User::factory()->create([
            'name' => 'prueba ISW-521',
            'email' => 'prueba@gmail.com',
            'password' => bcrypt('12345678'),
        ]);

        $superadminUser->roles()->sync(
            Role::query()->where('name', 'Superadmin')->pluck('id')
        );

        $adminUser = User::factory()->create([
            'name' => 'admin prueba ISW-521',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('12345678'),
        ]);

        $adminUser->roles()->sync(
            Role::query()->where('name', 'Administrador')->pluck('id')
        );

        $coordinatorUser = User::factory()->create([
            'name' => 'coordinadora prueba ISW-521',
            'email' => 'coordinadora@gmail.com',
            'password' => bcrypt('12345678'),
        ]);

        $coordinatorUser->roles()->sync(
            Role::query()->where('name', 'Coordinadora de Docencia')->pluck('id')
        );
    }
}
