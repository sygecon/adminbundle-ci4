<?php namespace Sygecon\AdminBundle\Models\Template;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use Config\Services;
use Config\Database;
use App\Config\Boot\NestedTree;

class LayoutSheetModel {

    private const TAB_SHEET_ID = 'sheet_name';

    protected $table = 'layout_sheets';
    
    protected $builder;

    protected $db;

    /**
	 * Constructor
	*/
	public function __construct(?BaseConnection &$db = null)
    {
        $db = $db ?? Database::connect(NestedTree::DB_GROUP);
        $this->db = &$db;
    }
    /**
	 * Destructor
	*/
    public function __destruct() 
    {
        if ($this->db instanceof BaseConnection) {
            $this->db->close();
        }
    }

    public function add(int $layoutId = 0, string $sheet = ''): int
    {
        $sheet = checkFileName($sheet);
        if ($layoutId && $sheet) {
            $data = ['layout_id' => (int) $layoutId, self::TAB_SHEET_ID => $sheet];
            $builder = $this->builder();
            $row = $builder->where($data)->get()->getRowArray();
            if (!isset($row) || ! $row) {
                $this->transBegin();
                $this->builder()->set($data)->insert();
                $this->transEnd();
            }
            return $layoutId;
        }
        return 0;
    }

    public function delete(int $layoutId = 0, string $sheet = '')
    {
        $sheet = checkFileName($sheet);
        if ($layoutId && $sheet) {
            $builder = $this->builder();
            $this->transBegin();
            $res = $builder->where(['layout_id' => (int) $layoutId, self::TAB_SHEET_ID => $sheet])->delete();
            $this->transEnd();
            return $res;
        }
        return false;
    }

    public function deleteSheet(string $sheet = '')
    {
        $sheet = checkFileName($sheet);
        if ($sheet) {
            $builder = $this->builder();
            $this->transBegin();
            $res = $builder->where(self::TAB_SHEET_ID, $sheet)->delete();
            $this->transEnd();
            return $res;
        }
        return false;
    }

    public function deleteLayout(int $layoutId = 0)
    {
        if ($layoutId) {
            $builder = $this->builder();
            $this->transBegin();
            $res = $builder->where('layout_id', (int) $layoutId)->delete();
            $this->transEnd();
            return $res;
        }
        return false;
    }

    public function getIdSheet(int $layoutId = 0)
    {
        $builder = $this->builder();
        $builder = $builder->select(self::TAB_SHEET_ID);
        if ($layoutId) { 
            $builder->where('layout_id', (int) $layoutId); 
        } else {
            $builder->orderBy('layout_id', 'ASC');
        }
        return $builder->get()->getResult($this->returnType);
    }

    public function getSheet(int $layoutId = 0)
    {
        $builder = $this->db->table(NestedTree::TAB_SHEET);
        $builder = $builder->select(NestedTree::TAB_SHEET . '.name as value, ' . NestedTree::TAB_SHEET . '.name as text, ' . NestedTree::TAB_SHEET . '.title');
        if ($layoutId) {
            $builder->join($this->table, $this->table . '.' . self::TAB_SHEET_ID . ' = ' . NestedTree::TAB_SHEET . '.name', 'left')
                ->where($this->table . '.layout_id', (int) $layoutId);
        } else {
            $builder->orderBy($this->table . '.layout_id', 'ASC');
        }
        return $builder->get()->getResult();
    }

    /**
     * Provides a shared instance of the Query Builder.
     * @throws ModelException
     * @return BaseBuilder
     */
    protected function builder(?string $table = null)
    {
        if ($this->builder instanceof BaseBuilder) {
            if ($table && $this->builder->getTable() !== $table) {
                return $this->db->table($table);
            }
            return $this->builder;
        }
        $table = empty($table) ? $this->table : $table;
        if (! $this->db instanceof BaseConnection) {
            $this->db = Database::connect(NestedTree::DB_GROUP);
        }
        $builder = $this->db->table($table);
        if ($table === $this->table) {
            $this->builder = $builder;
        }
        return $builder;
    }
    // Transactions
    protected function transBegin() {
        $cache = Services::cache();
        $cache->deleteMatching('Layout_Sheet_*');
        $this->db->transStart();
    }

    protected function transEnd() {
        $this->db->transComplete();
    }
}