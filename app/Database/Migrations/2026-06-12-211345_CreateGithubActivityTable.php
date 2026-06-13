<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGithubActivityTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'github_event_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'type' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'repo' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'icon' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'label' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'label_class' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
            ],
            'description' => [
                'type' => 'TEXT',
            ],
            'link' => [
                'type'       => 'VARCHAR',
                'constraint' => 512,
            ],
            'github_created_at' => [
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
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('github_event_id');
        $this->forge->createTable('github_activity');
    }

    public function down()
    {
        $this->forge->dropTable('github_activity');
    }
}
