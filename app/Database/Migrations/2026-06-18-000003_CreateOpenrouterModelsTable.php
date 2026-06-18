<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOpenrouterModelsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'model_id' => ['type' => 'VARCHAR', 'constraint' => 255],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('model_id');
        $this->forge->createTable('openrouter_models');
    }

    public function down(): void
    {
        $this->forge->dropTable('openrouter_models');
    }
}
