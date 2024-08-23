<?php namespace Sygecon\AdminBundle\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_create_import_table extends Migration
{
	public function up()
    {
		/**
		 **********************************************************************
		 * Import data
		 */
		$this->forge->addField([
			'id'    	=> ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
			'layout_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'type'  	=> ['type' => 'ENUM', 'constraint' => ['text', 'resource'], 'null' => false, 'default' => 'text'],
			'name'  	=> ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
            'title' 	=> ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
            'created_at'=> ['type' => 'DATETIME', 'null' => true],
            'updated_at'=> ['type' => 'DATETIME', 'null' => true]
        ]);
		$this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
		$this->forge->addKey('type');
		$this->forge->createTable('import', true);
	}

	public function down()
	{
		$this->db->disableForeignKeyChecks();
		$this->forge->dropTable('import', true);
		$this->db->enableForeignKeyChecks();
	}
}