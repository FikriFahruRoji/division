<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Real departments
        $departments = [
            ['code' => 'IT', 'name' => 'Bagian Teknologi Informasi', 'description' => 'Teknologi dan sistem informasi'],
            ['code' => 'HR', 'name' => 'Bagian Sumber Daya Manusia', 'description' => 'SDM dan kepegawaian'],
            ['code' => 'FIN', 'name' => 'Bagian Keuangan', 'description' => 'Keuangan dan akuntansi'],
            ['code' => 'LEGAL', 'name' => 'Bagian Hukum', 'description' => 'Hukum dan kepatuhan'],
            ['code' => 'ADM', 'name' => 'Bagian Administrasi', 'description' => 'Administrasi umum'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(
                ['code' => $dept['code']],
                array_merge($dept, ['status' => 'active'])
            );
        }

        // Generate Dummy Departments
        $faker = \Faker\Factory::create('id_ID');
        for ($i = 0; $i < 25; $i++) {
            $code = strtoupper($faker->bothify('DEP###'));
            Department::firstOrCreate(
                ['code' => $code],
                [
                    'name' => 'Departemen ' . $faker->company,
                    'description' => $faker->sentence,
                    'status' => 'active',
                ]
            );
        }

        $this->command->info('Departments seeded successfully (including dummies).');
    }
}
