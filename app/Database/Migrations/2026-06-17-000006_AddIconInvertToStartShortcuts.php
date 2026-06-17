<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIconInvertToStartShortcuts extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('start_shortcuts', [
            'icon_invert' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 0,
                'after'      => 'icon_filename',
            ],
            'icon_invert_light' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 0,
                'after'      => 'icon_invert',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('start_shortcuts', ['icon_invert', 'icon_invert_light']);
    }
}
