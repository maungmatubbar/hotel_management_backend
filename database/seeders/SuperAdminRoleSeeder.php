<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class SuperAdminRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::findOrCreate('super admin', 'web');

        $user = User::query()->updateOrCreate(
            ['email' => 'super-admin@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
            ],
        );

        $user->assignRole($role);
    }
}
