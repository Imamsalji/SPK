<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCalculationNormalizationsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'calculation_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'alternative_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'calculation_criterion_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'raw_value' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'normalized_value' => ['type' => 'DECIMAL', 'constraint' => '12,8'],
            'weighted_value' => ['type' => 'DECIMAL', 'constraint' => '12,8'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['alternative_id', 'calculation_criterion_id']);
        $this->forge->addKey('calculation_id');
        $this->forge->addKey('calculation_criterion_id');
        $this->forge->addForeignKey('calculation_id', 'calculations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('alternative_id', 'calculation_alternatives', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('calculation_criterion_id', 'calculation_criteria', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('calculation_normalizations', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('calculation_normalizations', true);
    }
}
