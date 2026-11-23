<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCalculationResultsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'calculation_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'alternative_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'preference_value' => ['type' => 'DECIMAL', 'constraint' => '12,8'],
            'rank' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'is_best' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['calculation_id', 'alternative_id']);
        $this->forge->addKey(['calculation_id', 'rank']);
        $this->forge->addForeignKey('calculation_id', 'calculations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('alternative_id', 'calculation_alternatives', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('calculation_results', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('calculation_results', true);
    }
}
