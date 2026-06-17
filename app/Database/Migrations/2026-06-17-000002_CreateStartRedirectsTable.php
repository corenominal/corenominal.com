<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStartRedirectsTable extends Migration
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
            'phrase' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'url' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'comments' => [
                'type'    => 'TEXT',
                'default' => '',
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

        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('start_redirects');
    }

    public function down(): void
    {
        $this->forge->dropTable('start_redirects');
    }
}
