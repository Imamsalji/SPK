<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCalculationsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'method' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'SAW'],
            'user_name_snapshot' => ['type' => 'VARCHAR', 'constraint' => 100],
            'total_alternatives' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'total_criteria' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'total_weight' => ['type' => 'DECIMAL', 'constraint' => '6,2', 'default' => 0],
            'best_alternative_name' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'best_score' => ['type' => 'DECIMAL', 'constraint' => '12,8', 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'calculated_at' => ['type' => 'DATETIME'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'calculated_at']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('calculations', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('calculations', true);
    }
}
