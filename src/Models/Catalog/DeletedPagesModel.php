<?php namespace Sygecon\AdminBundle\Models\Catalog;

use App\Models\Boot\BaseModel as Model;

class DeletedPagesModel extends Model
{
    protected $table          = 'deleted_nodes';
    protected $columnsFindAll = 'id,tree,layout_id,parent,link,search_deny,menu_deny,created_at';
    protected $useTimestamps  = true;
    protected $updatedField   = '';

    // Добавление
    public function create(array &$data = []): int
    {
        if (! isset($data['link']) || ! $data['link']) { return (int) 0; }
        if ($this->db->query('SELECT id FROM ' . $this->table . ' WHERE link = "' . $data['link'] . '"')->getRowArray())  { return (int) 0; }
        $fields = explode(',', $this->columnsFindAll);
        foreach($data as $col => $value) {
            if (! in_array($col, $fields)) { unset($data[$col]); }
        }
        return (int) $this->insert($data);
    }
}