<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBikesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'brand' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'default'    => '',
            ],
            'model' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'default'    => '',
            ],
            'year' => [
                'type'       => 'SMALLINT',
                'constraint' => 6,
                'null'       => true,
            ],
            'components' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'total_km' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'unsigned'   => true,
                'default'    => 0,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'active',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('status');
        $this->forge->createTable('bikes');
    }

    public function down(): void
    {
        $this->forge->dropTable('bikes');
    }
}
