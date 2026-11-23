<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCalculationCriteriaTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'calculation_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'criterion_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 10],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'type' => ['type' => 'ENUM', 'constraint' => ['benefit', 'cost']],
            'unit' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'weight_percent' => ['type' => 'DECIMAL', 'constraint' => '6,2'],
            'weight_normalized' => ['type' => 'DECIMAL', 'constraint' => '10,8'],
            'sort_order' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['calculation_id', 'code']);
        $this->forge->addKey('criterion_id');
        $this->forge->addForeignKey('calculation_id', 'calculations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('criterion_id', 'criteria', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('calculation_criteria', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('calculation_criteria', true);
    }
}
