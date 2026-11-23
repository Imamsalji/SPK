<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $now  = date('Y-m-d H:i:s');
        $rows = [];

        foreach (['Acer', 'ASUS', 'Lenovo', 'HP', 'Dell'] as $name) {
            $rows[] = ['name' => $name, 'created_at' => $now, 'updated_at' => $now];
        }

        $this->db->table('brands')->ignore(true)->insertBatch($rows);
    }
}
