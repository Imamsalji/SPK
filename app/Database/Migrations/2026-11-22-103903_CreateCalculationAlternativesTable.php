<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCalculationAlternativesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'calculation_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'laptop_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'brand_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'laptop_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'model' => ['type' => 'VARCHAR', 'constraint' => 100],
            'price' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('calculation_id');
        $this->forge->addKey('laptop_id');
        $this->forge->addForeignKey('calculation_id', 'calculations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('laptop_id', 'laptops', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('calculation_alternatives', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('calculation_alternatives', true);
    }
}
