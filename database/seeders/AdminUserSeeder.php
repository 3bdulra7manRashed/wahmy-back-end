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
        $this->createBranchAdmin();
    }

    /**
     * Create the branch admin user if it doesn't exist.
     */
    private function createBranchAdmin(): void
    {
        $email = 'admin@branch.test';

        $user = User::where('email', $email)->first();

        if (! $user) {
            User::create([
                'name' => 'Branch Admin',
                'email' => $email,
                'password' => Hash::make('password123'),
                'role' => UserRole::ADMIN,
                'is_active' => true,
                'branch_id' => null,
            ]);

            $this->command->info("Branch Admin created: {$email}");
        } else {
            $this->command->info('Branch Admin already exists.');
        }
    }
}
