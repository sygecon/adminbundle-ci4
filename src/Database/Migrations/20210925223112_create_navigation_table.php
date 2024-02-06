<?php namespace Sygecon\AdminBundle\Database\Migrations;

use CodeIgniter\Database\Migration;
/// php spark migrate -all
class Migration_create_navigation_table extends Migration
{
	public function up()
    {
		/*
		* МЕНЮ
		*/
		$this->forge->addField([
			'id'    	=> ['type' => 'SMALLINT', 'constraint' => 3, 'unsigned' => true, 'auto_increment' => true],
			'name'  	=> ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
            'title' 	=> ['type' => 'VARCHAR', 'constraint' => 128, 'default' => ''],
			'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true]
		]);
		$this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
		$this->forge->createTable('navigation', true);

        /*
		* Административные навигационные панели 
		*/
		$this->forge->addField([
			'id'    	=> ['type' => 'SMALLINT', 'constraint' => 3, 'unsigned' => true, 'auto_increment' => true],
			'name'  	=> ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
            'title' 	=> ['type' => 'VARCHAR', 'constraint' => 128, 'default' => ''],
			'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true]
		]);
		$this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
		$this->forge->createTable('navigation_bars', true);

		/*
		* Menu/Pages
		*/
		$fields = [
			'page_id' 	=> ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'default' => 0],
			'nav_id' 	=> ['type' => 'SMALLINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0],
		];
		$this->forge->addField($fields);
		$this->forge->addKey(['page_id', 'nav_id']);
		$this->forge->addForeignKey('page_id', 'pages', 'id', '', 'CASCADE');
		$this->forge->addForeignKey('nav_id', 'navigation', 'id', '', 'CASCADE');
		$this->forge->createTable('page_navigation', true);
	}

	public function down()
	{
		$this->db->disableForeignKeyChecks();

		if ($this->db->DBDriver != 'SQLite3')
		{
			$this->forge->dropForeignKey('page_navigation', 'page_navigation_page_id_foreign');
			$this->forge->dropForeignKey('page_navigation', 'page_navigation_nav_id_foreign');
		}
		$this->forge->dropTable('page_navigation', true);
        $this->forge->dropTable('navigation', true);
        $this->forge->dropTable('navigation_bars', true);

		$this->db->enableForeignKeyChecks();
	}
}