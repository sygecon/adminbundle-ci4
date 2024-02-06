<?php namespace Sygecon\AdminBundle\Database\Migrations;

use CodeIgniter\Database\Migration;
/// php spark migrate -all
class Migration_create_language_table extends Migration
{
	public function up()
    {
		/*
		* Users data
		*/
		$this->forge->addField([
			'id'    	=> ['type' => 'SMALLINT', 'constraint' => 3, 'unsigned' => true, 'auto_increment' => true],
			'position'  => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true],
			'name'  	=> ['type' => 'VARCHAR', 'constraint' => 8, 'null' => false],
            'title' 	=> ['type' => 'VARCHAR', 'constraint' => 64, 'default' => ''],
			'icon' 		=> ['type' => 'VARCHAR', 'constraint' => 512, 'default' => ''],
		]);
		$this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
		$this->forge->addKey('position');
		$this->forge->createTable('language', true);

		// add new identity info
		$fields = [
			'lang_id'  		=> ['type' => 'SMALLINT', 'constraint' => 3, 'default' => 1, 'unsigned' => true, 'after' => 'id'],
			'phone'	    	=> ['type' => 'VARCHAR', 'constraint' => 24, 'default' => '', 'after' => 'lang_id'],
			'firstname' 	=> ['type' => 'VARCHAR', 'constraint' => 96, 'default' => '', 'after' => 'username'],
			'lastname' 		=> ['type' => 'VARCHAR', 'constraint' => 96, 'default' => '', 'after' => 'firstname'],
			'patronymic' 	=> ['type' => 'VARCHAR', 'constraint' => 96, 'default' => '', 'after' => 'lastname'],
		];
		
		// $this->forge->addKey('phone');
		// $this->forge->addKey('lang_id');
		$this->forge->addColumn('users', $fields);
	}

	public function down()
	{
		$this->db->disableForeignKeyChecks();
		
		$this->forge->dropColumn('users', 'lang_id');
		$this->forge->dropColumn('users', 'phone');
		$this->forge->dropColumn('users', 'firstname');
		$this->forge->dropColumn('users', 'lastname');	
		$this->forge->dropColumn('users', 'patronymic');
		$this->forge->dropTable('language', true);

		$this->db->enableForeignKeyChecks();
	}
}