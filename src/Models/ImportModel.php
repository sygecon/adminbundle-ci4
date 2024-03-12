<?php 
namespace Sygecon\AdminBundle\Models;

use App\Models\Boot\BaseModel as Model;
use Config\Boot\NestedTree;
use Sygecon\AdminBundle\Config\Paths;

final class ImportModel extends Model
{
    protected $table = 'import';
    protected $columnsFindAll = 'id, name, title';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function findAllByType(string $type = ''): mixed
    {
        $builder = $this->builder();
        $builder = $builder->select($this->columnsFindAll);
        $builder = $builder->where('type', $type);
        $builder = $builder->orderBy($this->updatedField, 'DESC');

        $data = $builder->get()->getResult($this->returnType);
        if (isset($data) && $data) {
            helper('path');
            foreach($data as &$value) {
                if ($this->returnType === 'object') {
                    $value->name = toCamelCase($value->name);
                } else {
                    $value['name'] = toCamelCase($value['name']);
                }
            }
        }
        return $data;
    }

    public function getSheets(): array
    {
        return $this->db->query(
            'SELECT id, sheet_name AS name, title FROM ' . NestedTree::TAB_LAYOUT . ' ORDER BY id DESC'
        )->getResult($this->returnType);
    }

    public function getTemplateName(int $id): string
    {
        $row = $this->db->query(
            'SELECT ' . NestedTree::TAB_LAYOUT . '.sheet_name AS name FROM ' . NestedTree::TAB_LAYOUT . 
            ' LEFT JOIN ' . $this->table . ' ON ' . NestedTree::TAB_SHEET . '.id = ' . $this->table . '.layout_id' .
            ' WHERE ' . $this->table . '.id = ' . (int) $id
        )->getRow();

        return (isset($row) ? $row->name : '');
    }

    public function remove(int $id): bool
    {
        if (! $row = $this->find((int) $id, 'name')) { return false; }
        
        helper(['files', 'path']);
        $file = $this->fileNameConfig($row->name);
        if (is_file($file) === true) { unlink($file); }
        deletePath(Paths::IMPORT . toCamelCase($row->name));
        $this->delete((int) $id);
        return true;
    }

    public function writeToFile(string $name, string $data): void
    {
        file_put_contents($this->fileNameConfig($name), $data . PHP_EOL, LOCK_EX);
    }

    public function readFromFile(string $name): string
    {
        helper('path');
        $file = $this->fileNameConfig($name);
        if (is_file($file) === false) { return ''; }
        if (! $result = file_get_contents($file)) { return ''; }
        return $result;
    }

    private function fileNameConfig(string $name): string
    {
        helper('path');
        return Paths::IMPORT . toSnake($name) . '.json';
    }
}