<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createSuperAdmin();
    }

    /**
     * Create the super admin user if it doesn't exist.
     */
    private function createSuperAdmin(): void
    {
        $email = 'admin@wahmy.com';
        $password = 'password123'; // TODO: Change in production!

        $user = User::where('email', $email)->first();

        if (! $user) {
            User::create([
                'name' => 'Super Admin',
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'role' => UserRole::SUPER_ADMIN,
            ]);

            $this->command->info("Super Admin created: {$email} / {$password}");
        } else {
            // Ensure existing user has super_admin role
            if ($user->role !== UserRole::SUPER_ADMIN) {
                $user->update(['role' => UserRole::SUPER_ADMIN]);
                $this->command->info("Super Admin role updated for: {$email}");
            } else {
                $this->command->info('Super Admin already exists with correct role.');
            }
        }
    }
}
