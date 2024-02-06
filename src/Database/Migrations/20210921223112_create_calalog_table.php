<?php namespace Sygecon\AdminBundle\Database\Migrations;

use CodeIgniter\Database\Migration;
use App\Config\Boot\NestedTree;
/// php spark migrate -all
class Migration_create_catalog_table extends Migration
{
	public function up()
    {
		/* * 
		 catalog 
		*/
		$this->forge->addField([
				'id'           	=> ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
				'tree'			=> ['type' => 'INT', 'constraint' => 11, 'unsigned' => true,'default' => 1],
				'layout_id'  	=> ['type' => 'INT', 'constraint' => 7, 'unsigned' => true,'default' => 1],
				'parent'  		=> ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'default' => 0],
				'lft'          	=> ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'default' => 0],
				'rgt'          	=> ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'default' => 0],
				'level'        	=> ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true,'default' => 0],
				'name'    		=> ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false, 'default' => 'index'],
				'link'    		=> ['type' => 'VARCHAR', 'constraint' => 1024, 'null' => false],
				'icon'    		=> ['type' => 'VARCHAR', 'constraint' => 1024, 'default' => ''],
				'active'  		=> ['type' => 'SMALLINT', 'constraint' => 1, 'unsigned' => true, 'default' => 0],
				'search_deny'  	=> ['type' => 'SMALLINT', 'constraint' => 1, 'unsigned' => true, 'default' => 0],
				'menu_deny'  	=> ['type' => 'SMALLINT', 'constraint' => 1, 'unsigned' => true, 'default' => 0],
				'robots_deny'  	=> ['type' => 'SMALLINT', 'constraint' => 1, 'unsigned' => true, 'default' => 0],
		]);
		
		$this->forge->addKey('id', true);
		$this->forge->addKey(['parent', 'layout_id', 'tree']);

		// $this->forge->addForeignKey('layout_id', NestedTree::TAB_LAYOUT, 'id', '', 'CASCADE');
		$this->forge->createTable(NestedTree::TAB_NESTED, true);

		$this->forge->addField([
				'id'           		=> ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
				'node_id'   		=> ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'default' => 1],
				'language_id'  		=> ['type' => 'SMALLINT', 'constraint' => 3, 'unsigned' => true, 'default' => 1],
				'title'       		=> ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
				'description'  		=> ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
				'meta_title'   		=> ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
				'meta_keywords'   	=> ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
				'meta_description' 	=> ['type' => 'VARCHAR', 'constraint' => 1024, 'default' => ''],
				'summary' 			=> ['type' => 'VARCHAR', 'constraint' => 17408, 'default' => ''],
				'created_at'   		=> ['type' => 'DATETIME', 'null' => true],
				'updated_at'   		=> ['type' => 'DATETIME', 'null' => true]
		]);
		$this->forge->addKey('id', true);
		$this->forge->addKey(['node_id', 'language_id']);
		$this->forge->addForeignKey('node_id', NestedTree::TAB_NESTED, 'id', 'CASCADE', 'CASCADE');
		$this->forge->addForeignKey('language_id', 'language', 'id', 'CASCADE', 'CASCADE');
		$this->forge->createTable(NestedTree::TAB_DATA, true);

		$this->forge->addField([
				'id'           	=> ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
				'tree'			=> ['type' => 'INT', 'constraint' => 11, 'unsigned' => true,'default' => 1],
				'layout_id'  	=> ['type' => 'INT', 'constraint' => 7, 'unsigned' => true,'default' => 1],
				'parent'  		=> ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'default' => 0],
				'link'    		=> ['type' => 'VARCHAR', 'constraint' => 1024, 'null' => false],
				'icon'    		=> ['type' => 'VARCHAR', 'constraint' => 1024, 'default' => ''],
				'search_deny'  	=> ['type' => 'SMALLINT', 'constraint' => 1, 'unsigned' => true, 'default' => 0],
				'menu_deny'  	=> ['type' => 'SMALLINT', 'constraint' => 1, 'unsigned' => true, 'default' => 0],
				'robots_deny'  	=> ['type' => 'SMALLINT', 'constraint' => 1, 'unsigned' => true, 'default' => 0],
				'created_at'   	=> ['type' => 'DATETIME', 'null' => true]
		]);
		$this->forge->addKey('id', true);
		$this->forge->createTable('deleted_nodes', true);

		$this->forge->addField([
				'id'           	=> ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
				'link_from'    	=> ['type' => 'VARCHAR', 'constraint' => 1024, 'null' => false],
				'link_to'    	=> ['type' => 'VARCHAR', 'constraint' => 1024, 'null' => false],
				'updated_at'   	=> ['type' => 'TIMESTAMP', 'null' => false]
		]);
		$this->forge->addKey('id', true);
		$this->forge->createTable('redirect_links', true);
	}

	public function down()
	{
		$this->db->disableForeignKeyChecks();
		
		if ($this->db->DBDriver != 'SQLite3')
		{
			//$this->forge->dropForeignKey(NestedTree::TAB_NESTED, NestedTree::TAB_NESTED . '_layout_id_foreign');
			$this->forge->dropForeignKey(NestedTree::TAB_DATA, NestedTree::TAB_DATA . '_node_id_foreign');
			$this->forge->dropForeignKey(NestedTree::TAB_DATA, NestedTree::TAB_DATA . '_language_id_foreign');
		}
		$this->forge->dropTable(NestedTree::TAB_NESTED, true);
		$this->forge->dropTable(NestedTree::TAB_DATA, true);
		$this->forge->dropTable('deleted_nodes', true);
		$this->forge->dropTable('redirect_links', true);

		$this->db->enableForeignKeyChecks();
	}
}