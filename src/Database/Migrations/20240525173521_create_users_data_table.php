<?php namespace Sygecon\AdminBundle\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Auth;

class Migration_create_users_data_table extends Migration
{
	protected $DBGroup = null;

	public function up()
    {	
		$this->setDBGroup();

		/**
		 **********************************************************************
		 * User details
		 */
		$this->forge->addField([
			'user_id' 	=> ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
			'relatеd'  	=> ['type' => 'ENUM', 'constraint' => ['client', 'staff'], 'null' => false, 'default' => 'client'],
			'data' 		=> ['type' => 'LONGTEXT', 'null' => true],
		]);
		$this->forge->addKey('user_id', true);
		$this->forge->addKey('relatеd');
		$this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
		$this->forge->createTable('user_details', true);
	}

	public function down()
	{
		$this->setDBGroup();
		
		$this->db->disableForeignKeyChecks();

		if ($this->db->DBDriver != 'SQLite3') {
			$this->forge->dropForeignKey('user_details', 'user_details' . '_user_id_foreign');
		}
		$this->forge->dropTable('user_details', true);
		
		$this->db->enableForeignKeyChecks();
	}

	protected function setDBGroup(): void
	{
		$config = new Auth();
		$this->DBGroup = $config->DBGroup;
	}
}