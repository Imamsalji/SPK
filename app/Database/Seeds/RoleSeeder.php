<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $this->db->table('roles')->ignore(true)->insertBatch([
            ['name' => 'admin',     'label' => 'Administrator', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'mahasiswa', 'label' => 'Mahasiswa',     'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
