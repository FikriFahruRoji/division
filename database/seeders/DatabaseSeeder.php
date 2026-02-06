<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        // User::create([
        //     'name' => 'Administrator',
        //     'email' => 'admin@example.com',
        //     'password' => Hash::make('password'),
        //     'role' => 'admin',
        //     'position' => 'System Administrator',
        //     'status' => 'active',
        // ]);

        // // Create sample operator
        // User::create([
        //     'name' => 'Operator Staff',
        //     'email' => 'operator@example.com',
        //     'password' => Hash::make('password'),
        //     'role' => 'operator',
        //     'position' => 'Staff Admin',
        //     'status' => 'active',
        // ]);

        // // Create sample signer
        // User::create([
        //     'name' => 'Dr. Ahmad Fauzi',
        //     'email' => 'signer@example.com',
        //     'password' => Hash::make('password'),
        //     'role' => 'signer',
        //     'position' => 'Kepala Bagian',
        //     'status' => 'active',
        // ]);

        // // Create second signer for multi-signer testing
        // User::create([
        //     'name' => 'Prof. Siti Nurhaliza',
        //     'email' => 'signer2@example.com',
        //     'password' => Hash::make('password'),
        //     'role' => 'signer',
        //     'position' => 'Direktur',
        //     'status' => 'active',
        // ]);

        $this->call([
            SuperAdminSeeder::class, // Ensure superadmin is created
            // DepartmentSeeder::class,
            // DepartmentUserSeeder::class,
        ]);
    }
}
