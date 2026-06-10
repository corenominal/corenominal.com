<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMetricsTable extends Migration
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
            'path' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'user_uuid' => [
                'type'       => 'CHAR',
                'constraint' => '36',
                'null'       => true,
            ],
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'is_admin' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'device_type' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
            ],
            'anonymized_ip' => [
                'type'       => 'VARCHAR',
                'constraint' => '45',
            ],
            'useragent' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'load_time_ms' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'window_width' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true,
            ],
            'window_height' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('created_at');             // Index for time-based queries
        $this->forge->addKey('path');                 // Index for path-only queries
        $this->forge->addKey('device_type');            // Index for device breakdown queries
        $this->forge->createTable('metrics');
    }

    public function down()
    {
        $this->forge->dropTable('metrics');
    }
}