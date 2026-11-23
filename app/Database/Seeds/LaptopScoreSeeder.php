<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class LaptopScoreSeeder extends Seeder
{
    public function run(): void
    {
        $now      = date('Y-m-d H:i:s');
        $criteria = array_column($this->db->table('criteria')->get()->getResultArray(), 'id', 'code');
        $laptops  = array_column($this->db->table('laptops')->get()->getResultArray(), 'id', 'model');

        $demoNote = 'DATA DEMO (ilustrasi), bukan hasil penelitian.';
        $cpuNote  = 'DATA DEMO (ilustrasi). Ganti dengan skor benchmark nyata dan cantumkan sumber (nama benchmark + URL).';

        $rows = [];
        foreach (LaptopSeeder::DEMO_DATA as $item) {
            $laptopId = $laptops[$item['model']] ?? null;
            if ($laptopId === null) {
                continue;
            }

            $values = [
                'C1' => [$item['price'],   $demoNote],
                'C2' => [$item['cpu'],     $cpuNote],
                'C3' => [$item['ram'],     $demoNote],
                'C4' => [$item['ssd'],     $demoNote],
                'C5' => [$item['battery'], $demoNote],
            ];

            foreach ($values as $code => [$value, $note]) {
                $rows[] = [
                    'laptop_id'    => $laptopId,
                    'criterion_id' => $criteria[$code],
                    'value'        => $value,
                    'source_note'  => $note,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }
        }

        if ($rows !== []) {
            $this->db->table('laptop_scores')->ignore(true)->insertBatch($rows);
        }
    }
}
