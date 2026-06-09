<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateMessagesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'default'    => '',
            ],
            'from' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'default'    => '',
            ],
            'to' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'default'    => '',
            ],
            'cc' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'default'    => '',
            ],
            'bcc' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'default'    => '',
            ],
            'subject' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'default'    => '',
            ],
            'body' => [
                'type' => 'TEXT',
            ],
            'mailtype' => [
                'type'       => 'VARCHAR',
                'constraint' => '4',
                'default'    => 'text',
            ],
            'domain' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'default'    => '',
            ],
            'sent_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('messages');
    }

    public function down()
    {
        $this->forge->dropTable('messages');
    }
}