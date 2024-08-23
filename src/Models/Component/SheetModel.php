<?php 
namespace Sygecon\AdminBundle\Models\Component;

use App\Models\Boot\BaseModel as Model;
use Config\Boot\NestedTree;
use Sygecon\AdminBundle\Config\Paths;
// use Sygecon\AdminBundle\Models\Template\LayoutModel;
use Sygecon\AdminBundle\Libraries\Table\SheetForge;

final class SheetModel extends Model
{   
    public const TAB_PREFIX = 'model_';
    
    protected $table          = NestedTree::TAB_SHEET;
    protected $columnsFindAll = 'id, name, title';
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';

    public function create(array $data): int 
    {
        helper('path');
        if (! isset($data['name']) || ! $data['name'] || strlen($data['name']) < 3) return (int) 0;
        if (in_array($data['name'], Paths::FORBID_CLASS_NAMES) === true) return (int) 0;
        $data['name'] = toSnake($data['name']);

        $builder = $this->builder();
        $row = $builder->select('id')->where('name', $data['name'])->get()->getRowArray();
        if ($row && isset($row['id']) && $row['id']) return (int) 0;

        if (! isset($data['title']) || ! $data['title']) $data['title'] = $data['name'];
        $data['title']  = mb_ucfirst($data['title']);
        
        if (! $id = $this->insert($data)) return (int) 0;
        $data['sheet_id'] = (int) $id;

        $SheetForge = new SheetForge($data['name'], self::TAB_PREFIX);
        $SheetForge->create();
        
        // $model = new LayoutModel();
        // $model->insert($data);
        // $model->clearCache();

        return (int) $id;
    }

    public function getDataPage(int $idLayout = 0, int $idPage = 0): array 
    {
        if (! $idPage) return [];
        if (! $idLayout) return [];

        if (! $data = $this->db->query(
            'SELECT `name`, `title`, `sheet_name` FROM ' . NestedTree::TAB_LAYOUT . ' WHERE id = ' . (int) $idLayout
        )->getRowArray()) return [];

        helper('model');

        $class = getClassModelName($data['sheet_name']);
        if (enum_exists($class, false)) return [];
        if (! trim($data['sheet_name'])) $data['sheet_name'] = 'page';

        $model = new $class($this->db);
        $data['data'] = $model->getData((int) $idPage);
        return $data;
    }

    public function renameSheetNameForLayout(string $name = '', string $newName = ''): bool
    {
        if (! $name) return false;
        if (! $newName) return false;
        if ($newName === $name) return false;

        $this->transBegin();
        $result = $this->db->query(
            'UPDATE ' . NestedTree::TAB_LAYOUT . ' SET sheet_name = "' . $newName . '" WHERE sheet_name = "' . $name . '"'
        );
        $this->transEnd();

        return (! $result ? false : true);
    }
}