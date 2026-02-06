<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DepartmentUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = Department::all();
        $password = 'password';
        $accounts = [];

        foreach ($departments as $dept) {
            $code = strtolower($dept->code);
            
            // Create Admin
            $adminEmail = "admin.{$code}@example.com";
            $admin = User::firstOrCreate(
                ['email' => $adminEmail],
                [
                    'name' => "Admin {$dept->name}",
                    'password' => Hash::make($password),
                    'role' => 'admin',
                    'department_id' => $dept->id,
                    'position' => "Admin {$dept->name}",
                    'status' => 'active',
                ]
            );
            
            // Unconditionally attach the department to managed departments if valid
            if ($admin->wasRecentlyCreated || !$admin->managedDepartments()->where('department_id', $dept->id)->exists()) {
                 $admin->managedDepartments()->syncWithoutDetaching([$dept->id]);
            }
            
            $accounts[] = ['Admin', $dept->code, $adminEmail, $password];

            // Create Signer
            $signerEmail = "signer.{$code}@example.com";
            User::firstOrCreate(
                ['email' => $signerEmail],
                [
                    'name' => "Signer {$dept->name}",
                    'password' => Hash::make($password),
                    'role' => 'signer',
                    'department_id' => $dept->id,
                    'position' => "Signer {$dept->name}",
                    'status' => 'active',
                ]
            );
            $accounts[] = ['Signer', $dept->code, $signerEmail, $password];

             // Create Operator
            $operatorEmail = "operator.{$code}@example.com";
            User::firstOrCreate(
                ['email' => $operatorEmail],
                [
                    'name' => "Operator {$dept->name}",
                    'password' => Hash::make($password),
                    'role' => 'operator',
                    'department_id' => $dept->id,
                    'position' => "Operator {$dept->name}",
                    'status' => 'active',
                ]
            );
            $accounts[] = ['Operator', $dept->code, $operatorEmail, $password];

            // Create extra dummy users (Staff/Signers)
            $faker = \Faker\Factory::create('id_ID');
            $extraUsers = rand(2, 4); // 2-4 extra users per department
            
            for ($i = 0; $i < $extraUsers; $i++) {
                $role = rand(0, 1) ? 'signer' : 'operator'; // Randomly assign role
                $name = $faker->name;
                $email = strtolower(str_replace(' ', '.', $name)) . '.' . $code . '@example.com';
                
                // Ensure unique email
                while (User::where('email', $email)->exists()) {
                    $email = 'u' . rand(100, 999) . '.' . $email;
                }

                User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'role' => $role,
                    'department_id' => $dept->id,
                    'position' => $role === 'signer' ? $faker->jobTitle : 'Staff Administrasi',
                    'status' => 'active',
                ]);
            }
        }

        $this->command->info('Users seeded successfully.');
        $this->command->table(['Role', 'Dept', 'Email', 'Password'], $accounts);
    }
}
