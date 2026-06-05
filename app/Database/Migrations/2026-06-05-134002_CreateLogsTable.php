<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateLogsTable extends Migration
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
            'message' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'level' => [
                'type'       => 'TINYINT',
                'constraint' => 4,
                'comment'    => '0=info,1=warning,2=error,3=critical,4=debug',
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('logs');
    }

    public function down()
    {
        $this->forge->dropTable('logs');
    }
}
