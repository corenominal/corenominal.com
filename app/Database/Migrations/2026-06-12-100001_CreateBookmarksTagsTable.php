<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookmarksTagsTable extends Migration
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
            'bookmark_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'tag' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => '',
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => '',
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
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('slug');
        $this->forge->addKey('bookmark_id');
        $this->forge->createTable('bookmarks_tags');
    }

    public function down(): void
    {
        $this->forge->dropTable('bookmarks_tags');
    }
}
