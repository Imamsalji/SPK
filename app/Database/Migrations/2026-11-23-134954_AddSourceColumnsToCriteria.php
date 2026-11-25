<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSourceColumnsToCriteria extends Migration
{
    public function up()
    {
        $this->forge->addColumn('criteria', [
            'source_field' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'default'    => null,
                'after'      => 'unit',
            ],
            'require_source' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'source_field',
            ],
        ]);

        // Hanya satu kriteria yang boleh berstatus 'price' (NULL boleh banyak).
        $this->db->query('ALTER TABLE `criteria` ADD UNIQUE KEY `criteria_source_field_unique` (`source_field`)');

        // Data awal yang sudah ada.
        $this->db->table('criteria')->where('code', 'C1')->update(['source_field' => 'price']);
        $this->db->table('criteria')->where('code', 'C2')->update(['require_source' => 1]);

        $now = date('Y-m-d H:i:s');

        // Samakan nilai harga yang sudah ada dengan laptops.price.
        $this->db->query(
            'UPDATE laptop_scores ls
             JOIN criteria c ON c.id = ls.criterion_id
             JOIN laptops l ON l.id = ls.laptop_id
             SET ls.`value` = l.price, ls.updated_at = ?
             WHERE c.source_field = ?',
            [$now, 'price']
        );

        // Lengkapi laptop yang belum punya nilai harga.
        $this->db->query(
            'INSERT INTO laptop_scores (laptop_id, criterion_id, `value`, source_note, created_at, updated_at)
             SELECT l.id, c.id, l.price, ?, ?, ?
             FROM laptops l
             JOIN criteria c ON c.source_field = ?
             LEFT JOIN laptop_scores ls ON ls.laptop_id = l.id AND ls.criterion_id = c.id
             WHERE ls.id IS NULL',
            ['Otomatis dari harga laptop.', $now, $now, 'price']
        );
    }

    public function down()
    {
        $this->db->query('ALTER TABLE `criteria` DROP INDEX `criteria_source_field_unique`');
        $this->forge->dropColumn('criteria', ['source_field', 'require_source']);
    }
}
