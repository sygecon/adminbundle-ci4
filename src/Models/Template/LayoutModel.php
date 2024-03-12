<?php 
namespace Sygecon\AdminBundle\Models\Template;

use App\Models\Boot\BaseModel as Model;
use Config\Services;
use Config\Boot\NestedTree;

class LayoutModel extends Model
{
    protected $table = NestedTree::TAB_LAYOUT;
    protected $columnsFindAll = 'id, name, title';
    protected $useTimestamps = false;

    public $JsonEncode = true;

    public function getBuilder(int $id = 0)
    {
        if ($id) {
            return $this->find((int) $id, $this->columnsFindAll);
        }

        $cacheName = 'Layout_All';
        if (! $this->JsonEncode) { $cacheName .= '_NoJson'; }
        $cache = Services::cache();
        if ($data = $cache->get($cacheName)) { return $data; }

        if ($this->JsonEncode) {
            $data = jsonEncode($this->builder()->select($this->columnsFindAll)->get()->getResult(), false);
        } else {
            $data = $this->builder()->select($this->columnsFindAll)->get()->getResult();
        }
        
        $cache->save($cacheName, $data, 40320);
        return $data;
    }

    public function setModel(int $id = 0, string $sheet = ''): bool
    {
        if (! $id) { return false; }
        $this->db->transStart();
        $result = $this->update((int) $id, ['sheet_name' => $sheet]);
        $this->db->transComplete();
        return (! $result ? false : true);
    }

    public function getSheet(int $layoutId = 0): array
    {
        $builder = $this->db->table(NestedTree::TAB_SHEET);
        $builder->select(NestedTree::TAB_SHEET . '.name as value, ' . NestedTree::TAB_SHEET . '.name as text, ' . NestedTree::TAB_SHEET . '.title');
        if ($layoutId) {
            $builder->join($this->table, $this->table . '.sheet_name = ' . NestedTree::TAB_SHEET . '.name', 'left')
                ->where($this->table . '.id', (int) $layoutId);
        } else {
            $builder->orderBy('text', 'ASC');
        }
        return $builder->get()->getResult('array');
    }

    public function clearCache(): void
    {
        $cache = Services::cache();
        $cache->deleteMatching('Layout_*');
        return;
    }
}

