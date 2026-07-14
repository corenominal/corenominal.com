<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRiddenKmToBikes extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('bikes', [
            'ridden_km' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'unsigned'   => true,
                'default'    => 0,
                'after'      => 'total_km',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('bikes', ['ridden_km']);
    }
}
