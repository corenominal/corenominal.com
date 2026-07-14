<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRidesTable extends Migration
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
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'bike_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'distance_km' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'unsigned'   => true,
                'default'    => 0,
            ],
            'moving_time_seconds' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'elapsed_time_seconds' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'elevation_gain_m' => [
                'type'       => 'DECIMAL',
                'constraint' => '8,2',
                'unsigned'   => true,
                'null'       => true,
            ],
            'avg_speed_kmh' => [
                'type'       => 'DECIMAL',
                'constraint' => '6,2',
                'unsigned'   => true,
                'null'       => true,
            ],
            'max_speed_kmh' => [
                'type'       => 'DECIMAL',
                'constraint' => '6,2',
                'unsigned'   => true,
                'null'       => true,
            ],
            'avg_heart_rate' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true,
            ],
            'max_heart_rate' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true,
            ],
            'avg_cadence' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true,
            ],
            'avg_power' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true,
            ],
            'started_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'trackpoints_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'fit_file_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
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
        $this->forge->addKey('bike_id');
        $this->forge->addKey('started_at');
        $this->forge->createTable('rides');
    }

    public function down(): void
    {
        $this->forge->dropTable('rides');
    }
}
