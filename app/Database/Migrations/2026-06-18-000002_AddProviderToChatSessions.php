<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProviderToChatSessions extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('chat_sessions', [
            'provider' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'ollama',
                'after'      => 'model',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('chat_sessions', 'provider');
    }
}
