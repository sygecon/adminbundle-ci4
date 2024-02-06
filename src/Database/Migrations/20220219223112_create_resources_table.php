<?php

namespace Sygecon\AdminBundle\Database\Migrations;

use CodeIgniter\Database\Migration;
/// php spark migrate -all
class Migration_create_resources_table extends Migration {
	public function up() {
		/*
		* Ресурсы блоков страниц
		*/
		$this->forge->addField([
			'id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
			'type'  => ['type' => 'ENUM', 'constraint' => ['link', 'style', 'script', 'image', 'media'], 'null' => false, 'default' => 'link'],
			'hash'  => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
			'src'   => ['type' => 'VARCHAR', 'constraint' => 1024, 'null' => false, 'default' => ''],
			'class' => ['type' => 'VARCHAR', 'constraint' => 127],
			'alt'   => ['type' => 'VARCHAR', 'constraint' => 127]
		]);
		$this->forge->addKey('id', true);
		$this->forge->addUniqueKey('hash');
		$this->forge->addKey('type');
		$this->forge->createTable('resources', true);

		/* block_resources */
		$this->forge->addField([
			'block_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
			'resource_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0]
		]);
		$this->forge->addKey(['block_id', 'resource_id']);
		// $this->forge->addForeignKey('block_id', 'blocks', 'id', '', 'CASCADE');
		// $this->forge->addForeignKey('resource_id', 'resources', 'id', '', '');
		$this->forge->createTable('block_resources', true);

		/*
		* Ресурсы блоков страниц
		*/
		$this->forge->addField([
			'id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
			'type'  => ['type' => 'ENUM', 'constraint' => ['link', 'style', 'script', 'image', 'media'], 'null' => false, 'default' => 'link'],
			'hash'  => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
			'src'   => ['type' => 'VARCHAR', 'constraint' => 1024, 'null' => false, 'default' => ''],
			'class' => ['type' => 'VARCHAR', 'constraint' => 127],
			'alt'   => ['type' => 'VARCHAR', 'constraint' => 127]
		]);
		$this->forge->addKey('id', true);
		$this->forge->addUniqueKey('hash');
		$this->forge->addKey('type');
		$this->forge->createTable('control_resources', true);

		/* block_resources */
		$this->forge->addField([
			'block_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
			'resource_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0]
		]);
		$this->forge->addKey(['block_id', 'resource_id']);
		// $this->forge->addForeignKey('block_id', 'blocks', 'id', '', 'CASCADE');
		// $this->forge->addForeignKey('resource_id', 'control_resources', 'id', '', '');
		$this->forge->createTable('control_block_resources', true);
	}

	public function down() {
		$this->db->disableForeignKeyChecks();
		
		// if ($this->db->DBDriver != 'SQLite3')
		// {
		// 	$this->forge->dropForeignKey('block_resources', 'block_resources_block_id_foreign');
		//     $this->forge->dropForeignKey('block_resources', 'block_resources_resource_id_foreign');
		// 	$this->forge->dropForeignKey('control_block_resources', 'control_block_resources_block_id_foreign');
		//     $this->forge->dropForeignKey('control_block_resources', 'control_block_resources_resource_id_foreign');
		// }
		$this->forge->dropTable('resources', true);
		$this->forge->dropTable('block_resources', true);
		$this->forge->dropTable('control_resources', true);
		$this->forge->dropTable('control_block_resources', true);

		$this->db->enableForeignKeyChecks();
	}
}
