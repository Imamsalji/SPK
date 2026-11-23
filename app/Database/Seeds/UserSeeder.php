<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Akun DEMO. Ganti password sebelum aplikasi dipakai di luar localhost.
 * Password dapat di-override lewat .env:
 *   seeder.adminPassword
 *   seeder.studentPassword
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $now   = date('Y-m-d H:i:s');
        $roles = array_column($this->db->table('roles')->get()->getResultArray(), 'id', 'name');

        $adminPassword   = env('seeder.adminPassword', 'Admin@12345');
        $studentPassword = env('seeder.studentPassword', 'Mahasiswa@12345');

        $this->db->table('users')->ignore(true)->insertBatch([
            [
                'role_id'       => $roles['admin'],
                'name'          => 'Administrator (Demo)',
                'username'      => 'admin',
                'email'         => 'admin@laptop-dss.test',
                'password_hash' => password_hash($adminPassword, PASSWORD_DEFAULT),
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'role_id'       => $roles['mahasiswa'],
                'name'          => 'Mahasiswa (Demo)',
                'username'      => 'mahasiswa',
                'email'         => 'mahasiswa@laptop-dss.test',
                'password_hash' => password_hash($studentPassword, PASSWORD_DEFAULT),
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ]);
    }
}
