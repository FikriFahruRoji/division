<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if super admin already exists
        if (User::where('role', 'super_admin')->exists()) {
            $this->command->info('Super admin already exists.');
            return;
        }

        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@ttd.local',
            'password' => Hash::make('SuperAdmin123!'),
            'role' => 'super_admin',
            'department_id' => null,
            'position' => 'Super Administrator',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->command->info('Super admin created successfully.');
        $this->command->info('Email: superadmin@ttd.local');
        $this->command->info('Password: SuperAdmin123!');
    }
}
