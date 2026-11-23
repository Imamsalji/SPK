<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(BrandSeeder::class);
        $this->call(CriteriaSeeder::class);
        $this->call(LaptopSeeder::class);
        $this->call(LaptopScoreSeeder::class);
    }
}
