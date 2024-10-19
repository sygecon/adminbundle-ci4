<?php 
namespace Sygecon\AdminBundle\Database\Migrations;

use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;
use Config\Auth;

class MigrationUsersData extends Migration
{
	private array $tables;

    private array $attributes;

    public function __construct(?Forge $forge = null)
    {
        /** @var Auth $authConfig */
        $authConfig = new Auth();
        if ($authConfig->DBGroup !== null) {
            $this->DBGroup = $authConfig->DBGroup;
        }

        parent::__construct($forge);

        $this->tables     = $authConfig->tables;
        $this->attributes = ($this->db->getPlatform() === 'MySQLi') ? ['ENGINE' => 'InnoDB'] : [];
    }

	public function up()
    {	
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
		$this->forge->addForeignKey('user_id', $this->tables['users'], 'id', 'CASCADE', 'CASCADE');

		$this->createTable('user_details');
		
		$fields = [
			'lang_id'  		=> ['type' => 'SMALLINT', 'constraint' => 3, 'default' => 1, 'unsigned' => true, 'after' => 'id'],
			'phone'	    	=> ['type' => 'VARCHAR', 'constraint' => 24, 'default' => '', 'after' => 'lang_id'],
			'firstname' 	=> ['type' => 'VARCHAR', 'constraint' => 96, 'default' => '', 'after' => 'username'],
			'lastname' 		=> ['type' => 'VARCHAR', 'constraint' => 96, 'default' => '', 'after' => 'firstname'],
			'patronymic' 	=> ['type' => 'VARCHAR', 'constraint' => 96, 'default' => '', 'after' => 'lastname'],
		];
        $this->forge->addColumn($this->tables['users'], $fields);
	}

	public function down()
	{
		$this->db->disableForeignKeyChecks();

		if ($this->db->DBDriver != 'SQLite3') {
			$this->forge->dropForeignKey('user_details', 'user_details' . '_' . $this->tables['users'] . '_id_foreign');
		}
		$this->forge->dropTable('user_details', true);

		$fields = ['lang_id', 'phone', 'firstname', 'lastname', 'patronymic'];
        $this->forge->dropColumn($this->tables['users'], $fields);
		
		$this->db->enableForeignKeyChecks();
	}

	private function createTable(string $tableName): void
    {
        $this->forge->createTable($tableName, false, $this->attributes);
    }

}