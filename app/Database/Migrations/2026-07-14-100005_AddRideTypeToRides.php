<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRideTypeToRides extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('rides', [
            'ride_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'leisure',
                'after'      => 'bike_id',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('rides', ['ride_type']);
    }
}
