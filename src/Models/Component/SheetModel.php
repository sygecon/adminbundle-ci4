<?php 
namespace Sygecon\AdminBundle\Models\Component;

use App\Models\Boot\BaseModel as Model;
use Config\Boot\NestedTree;

final class SheetModel extends Model
{
    protected $table          = NestedTree::TAB_SHEET;
    protected $columnsFindAll = 'id, name, title';
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';

    public function getDataPage(int $idLayout = 0, int $idPage = 0): array 
    {
        if (! $idPage) { return []; }
        if (! $idLayout) { return []; }

        if (! $data = $this->db->query(
            'SELECT `name`, `title`, `sheet_name` FROM ' . NestedTree::TAB_LAYOUT . ' WHERE id = ' . (int) $idLayout
        )->getRowArray())
        { return []; }

        helper('model');

        $class = getClassModelName($data['sheet_name']);
        if (enum_exists($class, false)) { return []; }
        
        if (! trim($data['sheet_name'])) { $data['sheet_name'] = 'page'; }

        $model = new $class($this->db);
        $data['data'] = $model->getData((int) $idPage);
        return $data;
    }

    public function renameSheetNameForLayout(string $name = '', string $newName = ''): bool
    {
        if (! $name) { return false; }
        if (! $newName) { return false; }
        if ($newName === $name) { return false; }

        $this->transBegin();
        $result = $this->db->query(
            'UPDATE ' . NestedTree::TAB_LAYOUT . ' SET sheet_name = "' . $newName . '" WHERE sheet_name = "' . $name . '"'
        );
        $this->transEnd();

        return (! $result ? false : true);
    }
}