<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTodoItemsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type'       => 'VARCHAR',
                'constraint' => 36,
                'null'       => false,
            ],
            'user_uuid' => [
                'type'       => 'VARCHAR',
                'constraint' => 36,
                'null'       => false,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['todo', 'complete'],
                'default'    => 'todo',
                'null'       => false,
            ],
            'markdown' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'html' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'category' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'is_pinned' => [
                'type'     => 'TINYINT',
                'constraint' => 1,
                'unsigned' => true,
                'default'  => 0,
            ],
            'completed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey('user_uuid');

        $this->forge->createTable('todo_items');
    }

    public function down(): void
    {
        $this->forge->dropTable('todo_items');
    }
}
