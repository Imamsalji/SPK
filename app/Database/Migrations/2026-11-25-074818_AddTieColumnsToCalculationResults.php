<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menyimpan status seri dan keterangan tie-break per alternatif,
 * agar hasil yang dibuka dari riwayat sama persis dengan saat perhitungan.
 */
class AddTieColumnsToCalculationResults extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('calculation_results', [
            'is_tied' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'is_best',
            ],
            'tie_break' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'is_tied',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('calculation_results', ['is_tied', 'tie_break']);
    }
}
