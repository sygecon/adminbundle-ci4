<?php 
namespace Sygecon\AdminBundle\Models\Component;

use Config\Database;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use App\Config\Boot\NestedTree;
use Throwable;

final class RelationshipModel
{
    public const COL_LEFT  = NestedTree::COL_RELATION_LEFT;

    public const COL_RIGHT = NestedTree::COL_RELATION_RIGHT;

    protected $table       = 'relationship';
    protected $db;

    /** * Constructor */
	public function __construct(?BaseConnection &$db = null)	
    {
        try {
            $db = $db ?? Database::connect(NestedTree::DB_GROUP);
            $this->db = &$db;
        } catch (Throwable $th) {
            throw new DatabaseException($th->getMessage());
        }
	}

    /** * Destructor */
    public function __destruct() 
    {
        if ($this->db instanceof BaseConnection) { $this->db->close(); }
    }

    public function find(int $id = 0, string $select = '*', string $type = 'object')
    {
        $sql = 'SELECT ' . $select . ' FROM ' . $this->table;
        if ($id) { 
            return $this->db->query($sql . ' WHERE id = ' . (int) $id)->getFirstRow($type);
        }
        return $this->db->query($sql)->getResult($type);
    }

    public function update(int $id = 0, array $data = []): bool
    {
        if ($this->isExistDataFromTable($data) === true) { return false; }
        if (! $id) { return false; }
        // helper('path');
        // baseWriteFile('meLog-4.txt', jsonEncode($data, true));
        if ($this->db->table($this->table)->set($data)->where('id', (int) $id)->update()) {
            return true;
        }
        return false;
    }

    public function create(array $data = []): int 
    {
        $name = $data['name'];
        if (! $name || $this->isTable($name)) { return 0; }
        if ($this->isExistDataFromTable($data) === true) { return 0; }
        if (isset($data['id'])) { unset($data['id']); }
        
        $forge = Database::forge(NestedTree::DB_GROUP);
        try {
            $forge->addField([
                self::COL_LEFT       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'default' => 0],
				self::COL_RIGHT     => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'default' => 0]
            ]);
            $forge->addKey([self::COL_LEFT, self::COL_RIGHT]);
		    $forge->addForeignKey(self::COL_LEFT, NestedTree::TAB_NESTED, 'id', '', 'CASCADE');
		    $forge->addForeignKey(self::COL_RIGHT, NestedTree::TAB_NESTED, 'id', '', 'CASCADE');

            $forge->createTable(NestedTree::TAB_PREFIX_RELATION . $name, true);
        } catch (Throwable $th) {
            return 0;
        }
    
        if ($this->db->table($this->table)->set($data)->insert()) { 
            return (int) $this->db->insertID(); 
        }
        $this->deleteTable($name);
        return 0;
    }

    public function delete(int $id = 0): bool 
    {
        if ($id && $data = $this->db->query('SELECT name FROM ' . $this->table . ' WHERE id = ' . (int) $id)->getFirstRow()) {
            $this->deleteTable($data->name);
            if ($this->db->table($this->table)->where('id', (int) $id)->delete()) {
                return true;
            }
        }
        return false;
    }

    public function rename(string $name = '', string $newName = ''): bool {
        if ($name && $newName && $newName !== $name && $this->isTable($name) === true && $this->isTable($newName) === false) {
            $forge = Database::forge(NestedTree::DB_GROUP);
            $forge->renameTable(NestedTree::TAB_PREFIX_RELATION . $name, NestedTree::TAB_PREFIX_RELATION . $newName);
            return true;
        }
        return false;
    }

    protected function isTable(string $name = ''): bool
    {
        if ($name) { return $this->db->tableExists(NestedTree::TAB_PREFIX_RELATION . $name); }
        return false;
    }

    protected function isExistDataFromTable(array $data = []): bool 
    {
        if (! $data) { return false; }
        $sql = 'SELECT id FROM ' . $this->table . ' WHERE ';
        $where = '';
        if (isset($data['name'])) {
            $where = 'name = "' . $data['name'] . '"';
            $row = $this->db->query($sql . $where)->getRow();
            if (isset($row) && isset($row->id)) { return true; }
        }
        $where = '';
        if (isset($data[self::COL_LEFT]) && isset($data[self::COL_RIGHT])) {
            $where = self::COL_LEFT . ' = ' . (int) $data[self::COL_LEFT] . ' AND ' .
            self::COL_RIGHT . ' = ' . (int) $data[self::COL_RIGHT];
            $row = $this->db->query($sql . $where)->getRow();
            if (isset($row) && isset($row->id)) { return true; }
        }
        return false;
    }

    protected function deleteTable(string $name = ''): bool 
    {
        if ($name && $this->isTable($name)) {
            $forge = Database::forge(NestedTree::DB_GROUP);
            $forge->dropTable(NestedTree::TAB_PREFIX_RELATION . $name, true);
            return true;
        }
        return false;
    }
}