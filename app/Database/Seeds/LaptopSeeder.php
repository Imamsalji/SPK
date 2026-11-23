<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * DATA DEMO / ILUSTRASI.
 * Nama, model, harga, dan nilai di bawah BUKAN spesifikasi produk nyata
 * dan BUKAN hasil penelitian. Ganti dengan data nyata beserta sumbernya
 * sebelum dipakai untuk skripsi/laporan.
 *
 * Skala 'cpu' bersifat ilustrasi (mirip skala benchmark CPU umum).
 */
class LaptopSeeder extends Seeder
{
    public const DEMO_DATA = [
        ['brand' => 'Acer',   'name' => '[DEMO] Acer Seri A',   'model' => 'DEMO-A1', 'price' => 7999000,  'processor' => 'Prosesor demo A (ilustrasi)', 'cpu' => 9500,  'ram' => 8,  'ssd' => 256,  'battery' => 7.0],
        ['brand' => 'Acer',   'name' => '[DEMO] Acer Seri B',   'model' => 'DEMO-A2', 'price' => 10499000, 'processor' => 'Prosesor demo B (ilustrasi)', 'cpu' => 14200, 'ram' => 16, 'ssd' => 512,  'battery' => 10.0],
        ['brand' => 'ASUS',   'name' => '[DEMO] ASUS Seri C',   'model' => 'DEMO-B1', 'price' => 8999000,  'processor' => 'Prosesor demo C (ilustrasi)', 'cpu' => 11800, 'ram' => 8,  'ssd' => 512,  'battery' => 8.0],
        ['brand' => 'ASUS',   'name' => '[DEMO] ASUS Seri D',   'model' => 'DEMO-B2', 'price' => 13999000, 'processor' => 'Prosesor demo D (ilustrasi)', 'cpu' => 18500, 'ram' => 16, 'ssd' => 512,  'battery' => 12.0],
        ['brand' => 'Lenovo', 'name' => '[DEMO] Lenovo Seri E', 'model' => 'DEMO-C1', 'price' => 7499000,  'processor' => 'Prosesor demo E (ilustrasi)', 'cpu' => 8900,  'ram' => 8,  'ssd' => 256,  'battery' => 6.5],
        ['brand' => 'Lenovo', 'name' => '[DEMO] Lenovo Seri F', 'model' => 'DEMO-C2', 'price' => 11999000, 'processor' => 'Prosesor demo F (ilustrasi)', 'cpu' => 16300, 'ram' => 16, 'ssd' => 1024, 'battery' => 11.0],
        ['brand' => 'HP',     'name' => '[DEMO] HP Seri G',     'model' => 'DEMO-D1', 'price' => 9999000,  'processor' => 'Prosesor demo G (ilustrasi)', 'cpu' => 12700, 'ram' => 16, 'ssd' => 512,  'battery' => 9.0],
        ['brand' => 'Dell',   'name' => '[DEMO] Dell Seri H',   'model' => 'DEMO-E1', 'price' => 12499000, 'processor' => 'Prosesor demo H (ilustrasi)', 'cpu' => 15100, 'ram' => 16, 'ssd' => 512,  'battery' => 8.5],
    ];

    public function run(): void
    {
        $now    = date('Y-m-d H:i:s');
        $brands = array_column($this->db->table('brands')->get()->getResultArray(), 'id', 'name');

        $rows = [];
        foreach (self::DEMO_DATA as $item) {
            $rows[] = [
                'brand_id'       => $brands[$item['brand']],
                'name'           => $item['name'],
                'model'          => $item['model'],
                'price'          => $item['price'],
                'processor_name' => $item['processor'],
                'image'          => null,
                'description'    => 'DATA DEMO (ilustrasi). Bukan spesifikasi produk nyata.',
                'is_active'      => 1,
                'is_demo'        => 1,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        $this->db->table('laptops')->ignore(true)->insertBatch($rows);
    }
}
