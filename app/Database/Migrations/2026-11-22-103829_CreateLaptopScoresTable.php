<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLaptopScoresTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'laptop_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'criterion_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'value' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'source_note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['laptop_id', 'criterion_id']);
        $this->forge->addKey('criterion_id');
        $this->forge->addForeignKey('laptop_id', 'laptops', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('criterion_id', 'criteria', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('laptop_scores', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('laptop_scores', true);
    }
}
