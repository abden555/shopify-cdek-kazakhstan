<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = config('admin.initial_user');

        if (blank($admin['name']) || blank($admin['email']) || blank($admin['password'])) {
            $this->command?->warn('Administrator user not seeded: set ADMIN_NAME, ADMIN_EMAIL, and ADMIN_PASSWORD first.');

            return;
        }

        $user = User::firstOrCreate(
            ['email' => $admin['email']],
            [
                'name' => $admin['name'],
                'password' => Hash::make($admin['password']),
            ],
        );

        $user->assignRole('administrator');
    }
}
