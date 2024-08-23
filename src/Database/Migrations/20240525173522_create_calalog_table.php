<?php namespace Sygecon\AdminBundle\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\Boot\NestedTree;
/// php spark migrate -all
class Migration_create_catalog_table extends Migration
{
	public function up()
    {
		/** 
		 **********************************************************************
		 * LANGUAGE
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

		/** 
		 **********************************************************************
		 * CATALOG
		 */

		/**
		 * Page Layouts
		 */
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

		/**
		 * Tree Nested
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
		$this->forge->addForeignKey('layout_id', NestedTree::TAB_LAYOUT, 'id', '', 'CASCADE');
		$this->forge->createTable(NestedTree::TAB_NESTED, true);

		/* 
		 * Catalog layout 
		 */
	    $this->forge->addField([
			'node_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'default' => 0],
			'layout_id' => ['type' => 'INT', 'constraint' => 7, 'unsigned' => true, 'default' => 0],
		]);
		$this->forge->addKey('node_id', true);
		$this->forge->addKey('layout_id');
		$this->forge->addForeignKey('node_id', NestedTree::TAB_NESTED, 'id', '', 'CASCADE');
		$this->forge->addForeignKey('layout_id', NestedTree::TAB_LAYOUT, 'id', '', 'CASCADE');
		$this->forge->createTable('catalog_layout', true);

		/**
		 * Pages
		 */
		$this->forge->addField([
				'id'           		=> ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
				'node_id'   		=> ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'default' => 1],
				'language_id'  		=> ['type' => 'SMALLINT', 'constraint' => 3, 'unsigned' => true, 'default' => 1],
				'title'       		=> ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
				'description'  		=> ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
				'meta_title'   		=> ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
				'meta_keywords'   	=> ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
				'meta_description' 	=> ['type' => 'VARCHAR', 'constraint' => 1024, 'default' => ''],
				'summary' 			=> ['type' => 'VARCHAR', 'constraint' => 14280, 'default' => ''],
				'created_at'   		=> ['type' => 'DATETIME', 'null' => true],
				'updated_at'   		=> ['type' => 'DATETIME', 'null' => true]
		]);
		$this->forge->addKey('id', true);
		$this->forge->addKey(['node_id', 'language_id']);
		$this->forge->addForeignKey('node_id', NestedTree::TAB_NESTED, 'id', 'CASCADE', 'CASCADE');
		$this->forge->addForeignKey('language_id', 'language', 'id', 'CASCADE', 'CASCADE');
		$this->forge->createTable(NestedTree::TAB_DATA, true);

		/**
		 * Deleted pages
		 */
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

		/**
		 * Redirecting links
		 */
		$this->forge->addField([
				'id'           	=> ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
				'link_from'    	=> ['type' => 'VARCHAR', 'constraint' => 1024, 'null' => false],
				'link_to'    	=> ['type' => 'VARCHAR', 'constraint' => 1024, 'null' => false],
				'updated_at'   	=> ['type' => 'TIMESTAMP', 'null' => false]
		]);
		$this->forge->addKey('id', true);
		$this->forge->createTable('redirect_links', true);

		/* 
		 **********************************************************************
	 	 * NAVIGATIONS 
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
		 * Administrative navigation panel
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
		 * The page navigation bar
		 */
		$fields = [
			'page_id' 	=> ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'default' => 0],
			'nav_id' 	=> ['type' => 'SMALLINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0],
		];
		$this->forge->addField($fields);
		$this->forge->addKey(['page_id', 'nav_id']);
		$this->forge->addForeignKey('page_id', NestedTree::TAB_DATA, 'id', '', 'CASCADE');
		$this->forge->addForeignKey('nav_id', 'navigation', 'id', '', 'CASCADE');
		$this->forge->createTable('page_navigation', true);

		/** 
		 **********************************************************************
		 * TEMPLATES 
		 */

		/* 
		 * Html blocks for pages 
		 */
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

		/* 
		 * Themes for the design of the site pages 
		 */
		$this->forge->addField([
			'id'    		=> ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
			'active' 		=> ['type' => 'SMALLINT', 'constraint' => 1, 'default' => 0, 'unsigned' => true],
			'name'  		=> ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
            'title' 		=> ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
			'resource' 		=> ['type' => 'VARCHAR', 'constraint' => 16064, 'default' => '{"scripts":[],"styles":[]}'],
			'created_at' 	=> ['type' => 'DATETIME', 'null' => true],
            'updated_at' 	=> ['type' => 'DATETIME', 'null' => true]
		]);
		$this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
		$this->forge->createTable('themes', true);

		/* 
		 * Tables / Models 
		 */
		$this->forge->addField([
			'id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
			'name'  => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
            'title' => ['type' => 'VARCHAR', 'constraint' => 128, 'default' => ''],
			'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true]
		]);
		$this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
		$this->forge->createTable(NestedTree::TAB_SHEET, true);

		/* 
		 * Фильтры / Связи между страницами каталога 
		 */
		$this->forge->addField([
			'id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
			NestedTree::COL_RELATION_LEFT => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'default' => 0],
			NestedTree::COL_RELATION_RIGHT => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'default' => 0],
			'name'  => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => false],
            'title' => ['type' => 'VARCHAR', 'constraint' => 128, 'default' => '']
		]);
		$this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
		// $this->forge->addKey(NestedTree::COL_RELATION_LEFT, NestedTree::COL_RELATION_RIGHT);
		$this->forge->addForeignKey(NestedTree::COL_RELATION_LEFT, NestedTree::TAB_NESTED, 'id', false, 'CASCADE');
		$this->forge->addForeignKey(NestedTree::COL_RELATION_RIGHT, NestedTree::TAB_NESTED, 'id', false, 'CASCADE');
		$this->forge->createTable('relationship', true);

		/** 
		 **********************************************************************
		 * RESOURCE
		 */
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

		/* 
		 * block_resources 
		 */
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

		/* 
		 * block_resources 
		 */
		$this->forge->addField([
			'block_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
			'resource_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0]
		]);
		$this->forge->addKey(['block_id', 'resource_id']);
		// $this->forge->addForeignKey('block_id', 'blocks', 'id', '', 'CASCADE');
		// $this->forge->addForeignKey('resource_id', 'control_resources', 'id', '', '');
		$this->forge->createTable('control_block_resources', true);
	}

	/* 
	 * FUNCTIONS 
	 */
	public function down()
	{
		$this->db->disableForeignKeyChecks();
		
		if ($this->db->DBDriver != 'SQLite3')
		{
			// $this->forge->dropForeignKey('block_resources', 'block_resources_block_id_foreign');
			// $this->forge->dropForeignKey('block_resources', 'block_resources_resource_id_foreign');
			// $this->forge->dropForeignKey('control_block_resources', 'control_block_resources_block_id_foreign');
			// $this->forge->dropForeignKey('control_block_resources', 'control_block_resources_resource_id_foreign');

			$this->forge->dropForeignKey('page_navigation', 'page_navigation_page_id_foreign');
			$this->forge->dropForeignKey('page_navigation', 'page_navigation_nav_id_foreign');

			$this->forge->dropForeignKey('layout_blocks', 'layout_blocks_layout_id_foreign');
			$this->forge->dropForeignKey('layout_blocks', 'layout_blocks_block_id_foreign');
			$this->forge->dropForeignKey('catalog_layout', 'catalog_layout_node_id_foreign');
			$this->forge->dropForeignKey('catalog_layout', 'catalog_layout_layout_id_foreign');
			$this->forge->dropForeignKey('relationship', 'relationship_' . NestedTree::COL_RELATION_LEFT . '_foreign');
			$this->forge->dropForeignKey('relationship', 'relationship_' . NestedTree::COL_RELATION_RIGHT . '_foreign');
			
			$this->forge->dropForeignKey(NestedTree::TAB_DATA, NestedTree::TAB_DATA . '_node_id_foreign');
			$this->forge->dropForeignKey(NestedTree::TAB_DATA, NestedTree::TAB_DATA . '_language_id_foreign');
		}

		$this->forge->dropTable('resources', true);
		$this->forge->dropTable('block_resources', true);
		$this->forge->dropTable('control_resources', true);
		$this->forge->dropTable('control_block_resources', true);

		$this->forge->dropTable('page_navigation', true);
        $this->forge->dropTable('navigation', true);
        $this->forge->dropTable('navigation_bars', true);

		$this->forge->dropTable('layout_blocks', true);
		$this->forge->dropTable('catalog_layout', true);
		$this->forge->dropTable(NestedTree::TAB_SHEET, true);
		$this->forge->dropTable(NestedTree::TAB_LAYOUT, true);
		$this->forge->dropTable('blocks', true);
		$this->forge->dropTable('themes', true);
		$this->forge->dropTable('relationship', true);
		$this->forge->dropTable('variables', true);

		$this->forge->dropTable('deleted_nodes', true);
		$this->forge->dropTable('redirect_links', true);
		$this->forge->dropTable(NestedTree::TAB_NESTED, true);
		$this->forge->dropTable(NestedTree::TAB_DATA, true);
		
		$this->forge->dropTable('language', true);
		
		$this->db->enableForeignKeyChecks();
	}
}