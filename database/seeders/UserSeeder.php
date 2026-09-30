<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure default department exists
        $department = Department::firstOrCreate(
            ['code' => 'TI'],
            [
                'name' => 'Teknologi Informasi',
                'description' => 'Departemen Teknologi Informasi dan Sistem Digital',
                'status' => 'active',
            ]
        );

        // 2. Create or update Signer account
        $signer = User::updateOrCreate(
            ['email' => 'signer@uniga.ac.id'],
            [
                'name' => 'Signer User',
                'password' => Hash::make('Signer123!'),
                'role' => 'signer',
                'department_id' => $department->id,
                'position' => 'Penandatangan / Kepala Bagian',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Signer account ready: ' . $signer->email . ' (Password: Signer123!)');

        // 3. Create or update Staff (Operator) account
        $staff = User::updateOrCreate(
            ['email' => 'staff@uniga.ac.id'],
            [
                'name' => 'Staff Operator',
                'password' => Hash::make('Staff123!'),
                'role' => 'operator',
                'department_id' => $department->id,
                'position' => 'Staff Administrasi',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Staff account ready: ' . $staff->email . ' (Password: Staff123!)');
    }
}
