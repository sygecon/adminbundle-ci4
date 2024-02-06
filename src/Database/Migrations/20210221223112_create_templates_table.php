<?php namespace Sygecon\AdminBundle\Database\Migrations;

use CodeIgniter\Database\Migration;
use App\Config\Boot\NestedTree;
/// php spark migrate -all
class Migration_create_templates_table extends Migration
{
	public function up()
    {
		/* Макеты страниц */
		$this->forge->addField([
			'id'    		=> ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
			'name'  		=> ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
			'sheet_name' 	=> ['type' => 'VARCHAR', 'constraint' => 32, 'default' => ''],
			// 'type'  		=> ['type' => 'ENUM', 'constraint' => ['page', 'list', 'menu', 'none'], 'null' => false, 'default' => 'page'],
            'title' 		=> ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
		]);
		$this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
		$this->forge->createTable(NestedTree::TAB_LAYOUT, true);

		/* Блоки для страниц */
		$this->forge->addField([
			'id'    	=> ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
			'type'  	=> ['type' => 'ENUM', 'constraint' => ['html','json','php'], 'null' => false, 'default' => 'php'],
			'name'  	=> ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
            'title' 	=> ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
			'scripts'	=> ['type' => 'VARCHAR', 'constraint' => 5120, 'default' => ''],
			'styles' 	=> ['type' => 'VARCHAR', 'constraint' => 5120, 'default' => ''],
			'created_at'=> ['type' => 'DATETIME', 'null' => true],
            'updated_at'=> ['type' => 'DATETIME', 'null' => true]
		]);
		$this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
		$this->forge->createTable('blocks', true);

		/* Темы для оформления страниц сайта */
		$this->forge->addField([
			'id'    		=> ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
			'active' 		=> ['type' => 'SMALLINT', 'constraint' => 1, 'default' => 0, 'unsigned' => true],
			'name'  		=> ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
            'title' 		=> ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
			'resource' 		=> ['type' => 'VARCHAR', 'constraint' => 17024, 'default' => '{"scripts":[],"styles":[]}'],
			'created_at' 	=> ['type' => 'DATETIME', 'null' => true],
            'updated_at' 	=> ['type' => 'DATETIME', 'null' => true]
		]);
		$this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
		$this->forge->createTable('themes', true);

		/* Таблицы / Модели */
		$this->forge->addField([
			'id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
			'name'  => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
            'title' => ['type' => 'VARCHAR', 'constraint' => 128, 'default' => ''],
			'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true]
		]);
		$this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
		$this->forge->createTable('sheets', true);

		/* * Catalog layout */
	    $this->forge->addField([
			'node_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'default' => 0],
			'layout_id' => ['type' => 'INT', 'constraint' => 7, 'unsigned' => true, 'default' => 0],
		]);
		$this->forge->addKey('node_id', true);
		$this->forge->addKey('layout_id');
		$this->forge->addForeignKey('node_id', 'tree', 'id', '', 'CASCADE');
		$this->forge->addForeignKey('layout_id', NestedTree::TAB_LAYOUT, 'id', '', 'CASCADE');
		$this->forge->createTable('catalog_layout', true);

		/* Фильтры / Связи между страницами каталога */
		$this->forge->addField([
			'id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
			NestedTree::COL_RELATION_LEFT => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'default' => 0],
			NestedTree::COL_RELATION_RIGHT => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'default' => 0],
			'name'  => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
            'title' => ['type' => 'VARCHAR', 'constraint' => 128, 'default' => '']
		]);
		$this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
		$this->forge->addKey(NestedTree::COL_RELATION_LEFT, NestedTree::COL_RELATION_RIGHT);
		$this->forge->addForeignKey(NestedTree::COL_RELATION_LEFT, 'tree', 'id', false, 'CASCADE');
		$this->forge->addForeignKey(NestedTree::COL_RELATION_RIGHT, 'tree', 'id', false, 'CASCADE');
		$this->forge->createTable('relationship', true);

		/* * Block sheets */
		// $this->forge->addField([
		// 	'block_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
		// 	'sheet_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0]
		// ]);
		// $this->forge->addKey(['block_id', 'sheet_id']);
		// $this->forge->addForeignKey('block_id', 'blocks', 'id', false, 'CASCADE');
		// $this->forge->addForeignKey('sheet_id', 'sheets', 'id', false, 'CASCADE');
		// $this->forge->createTable('block_sheets', true);

		/* * layout Sheets */
		// $this->forge->addField([
		// 	'layout_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
		// 	'sheet_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0]
		// ]);
		// $this->forge->addKey(['layout_id', 'sheet_id']);
		// $this->forge->addForeignKey('layout_id', 'layouts', 'id', false, 'CASCADE');
		// $this->forge->addForeignKey('sheet_id', 'sheets', 'id', false, 'CASCADE');
		// $this->forge->createTable('layout_sheets', true);
	}

	public function down()
	{
		$this->db->disableForeignKeyChecks();
		
		if ($this->db->DBDriver != 'SQLite3')
		{
			//$this->forge->dropForeignKey('block_sheets', 'block_sheets_block_id_foreign');
			//$this->forge->dropForeignKey('block_sheets', 'block_sheets_sheet_id_foreign');
			$this->forge->dropForeignKey('layout_blocks', 'layout_blocks_layout_id_foreign');
			$this->forge->dropForeignKey('layout_blocks', 'layout_blocks_block_id_foreign');
			$this->forge->dropForeignKey('catalog_layout', 'catalog_layout_node_id_foreign');
			$this->forge->dropForeignKey('catalog_layout', 'catalog_layout_layout_id_foreign');
			$this->forge->dropForeignKey('relationship', 'relationship_' . NestedTree::COL_RELATION_LEFT . '_foreign');
			$this->forge->dropForeignKey('relationship', 'relationship_' . NestedTree::COL_RELATION_RIGHT . '_foreign');
		}
		//$this->forge->dropTable('block_sheets', true);
		$this->forge->dropTable('layout_blocks', true);
		$this->forge->dropTable('catalog_layout', true);
		$this->forge->dropTable('sheets', true);
		$this->forge->dropTable('layouts', true);
		$this->forge->dropTable('blocks', true);
		$this->forge->dropTable('themes', true);
		$this->forge->dropTable('relationship', true);
		$this->forge->dropTable('variables', true);

		$this->db->enableForeignKeyChecks();
	}
}