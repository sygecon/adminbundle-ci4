<?php 
namespace Sygecon\AdminBundle\Models\Template;

use App\Models\Boot\BaseModel as Model;
use Config\Services;
use Config\Boot\NestedTree;

class LayoutModel extends Model
{
    public const CACHE_TTL = 20160;

    protected $table = NestedTree::TAB_LAYOUT;
    protected $columnsFindAll = 'id, name, title';
    protected $useTimestamps = false;

    public $JsonEncode = true;

    public function create(array $data): int
    {
        if (! isset($data) || ! $data) return (int) 0;
        if (! isset($data['name']) || strlen($data['name']) < 3) return (int) 0;

        $builder = $this->builder();
        $row = $builder->select('id')->where('name', $data['name'])->get()->getRowArray();
        if ($row && isset($row['id']) && $row['id']) return (int) 0;

        if ($id = $this->insert($data)) {
            $this->clearCache();
            return (int) $id;
        }
        return (int) 0;
    }

    public function getBuilder(int $id = 0)
    {
        $cache = Services::cache();
        $cacheName = ($id ? 'Layout_Id' : 'Layout_All');
        if (! $this->JsonEncode) { $cacheName .= '_NoJson'; }

        if ($id) {
            $cacheName .= '_' . $id;
            if ($data = $cache->get($cacheName)) return $data; 

            if ($data = $this->find((int) $id, $this->columnsFindAll)) {
                $cache->save($cacheName, $data, self::CACHE_TTL);
            }
            return $data;
        }

        if ($data = $cache->get($cacheName)) return $data; 

        if ($this->JsonEncode) {
            $data = jsonEncode($this->builder()->select($this->columnsFindAll)->get()->getResult(), false);
        } else {
            $data = $this->builder()->select($this->columnsFindAll)->get()->getResult();
        }
        $cache->save($cacheName, $data, self::CACHE_TTL);
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

