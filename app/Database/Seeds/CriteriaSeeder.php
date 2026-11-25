<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CriteriaSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // [kode, nama, deskripsi, jenis, satuan, bobot, source_field, require_source]
        $criteria = [
            ['C1', 'Harga',              'Harga laptop dalam rupiah. Semakin murah semakin baik.', 'cost',    'Rp',   30, 'price', 0],
            ['C2', 'Performa prosesor',  'Skor benchmark prosesor. Semakin tinggi semakin baik.',  'benefit', 'poin', 25, null,    1],
            ['C3', 'Kapasitas RAM',      'Kapasitas memori RAM dalam GB.',                         'benefit', 'GB',   20, null,    0],
            ['C4', 'Kapasitas SSD',      'Kapasitas penyimpanan SSD dalam GB.',                    'benefit', 'GB',   15, null,    0],
            ['C5', 'Daya tahan baterai', 'Perkiraan daya tahan baterai dalam jam.',                'benefit', 'jam',  10, null,    0],
        ];

        $rows = [];
        foreach ($criteria as $i => [$code, $name, $desc, $type, $unit, $weight, $sourceField, $requireSource]) {
            $rows[] = [
                'code'           => $code,
                'name'           => $name,
                'description'    => $desc,
                'type'           => $type,
                'unit'           => $unit,
                'source_field'   => $sourceField,
                'require_source' => $requireSource,
                'default_weight' => $weight,
                'sort_order'     => $i + 1,
                'is_active'      => 1,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        $this->db->table('criteria')->ignore(true)->insertBatch($rows);
    }
}
