<?php namespace Sygecon\AdminBundle\Libraries\Table;

use Sygecon\AdminBundle\Libraries\Table\Forge;
use Sygecon\AdminBundle\Config\Paths;
use Sygecon\AdminBundle\Config\FormDataTypes;

final class SheetForge 
{
    private $name       = '';
    private $prefix     = '';
    
    private array $forbidName   = ['block_layers', 'block_resources'];

    private array $pageField    = [
        'page_id' => [
            'type' => 'BIGINT', 
            'constraint' => 20, 
            'unsigned' => true
        ]
    ];

    public $relationsTab = '';

    public function __construct(string $name = '', string $pefix = '') {
        helper('path');

        $name = strtolower(checkFileName($name));
        if ($name && in_array($name, $this->forbidName) === false) { 
            $this->name = $name; 
            if ($pefix) { $this->prefix = $pefix; }
        }
    }
    
    public function create(): bool
    {
        if (! $fileName = $this->getFileName()) { return false; }
        return writingDataToFile($fileName, FormDataTypes::SCELETON);
    }

    public function rename(string $newName = ''): bool
    {
        if (! $this->name) { return false; }
        $oldFileName = $this->getFileName();
        if (is_file($oldFileName) === false) { return false; }
        if (! $newFileName = castingPath(strtolower(checkFileName($newName)))) { return false; }
        
        $newFileName = dirname($oldFileName) . DIRECTORY_SEPARATOR . $newFileName . '.json';
        if ($oldFileName === $newFileName) { return false; }
        return rename($oldFileName, $newFileName);
    }

    public function delete(): bool
    {
        if (! $fileName = $this->getFileName()) { return false; }
        if (is_file($fileName) === true) { @unlink($fileName); }
        $this->deleteTable();
        return true;
    }

    public function update(string $jsonData): string 
    {
        if (! $fileName = $this->getFileName()) { return ''; }
        helper('model');

        $data = (array) jsonDecode($jsonData);
        if ($data && isset($data['fields']) && is_array($data['fields'])) {
            if (! $data['fields']) {
                $sceleton = jsonDecode(FormDataTypes::SCELETON);
                $data['fields'] = $sceleton['fields'];
                $jsonData = jsonEncode($data, true);
            }
            if (writingDataToFile($fileName, $jsonData) === false) { return ''; }

            $data = $data['fields'];
            $reservedFields = Forge::reservedFields();
            if (isset($reservedFields['id'])) { unset($reservedFields['id']); }
            if (isset($reservedFields['node_id'])) { unset($reservedFields['node_id']); }
            if (isset($reservedFields['page_id'])) { unset($reservedFields['page_id']); }

            $reservedFields = dataKeyIntersect($data, $reservedFields);
            if ($data) { 
                $this->getRelationTabs($data); 
            } else {
                $this->deleteTable();
            }

            return $this->prefix . $this->name;
        }
        
        writingDataToFile($fileName, '{}');
        return '';
    }

    public function getFrame(): string 
    {
        if (! $fileName = $this->getFileName()) { return ''; }
        return readingDataFromFile($fileName);
    }

    public function isTable(): bool
    {
        return Forge::isTable($this->prefix . $this->name);
    }

    public function getFields(string $jsonData = ''): array
    {
        if (! $this->name) { return []; }
        $data = (array) jsonDecode($jsonData);
        if ($data && isset($data['fields']) && is_array($data['fields'])) { 
            $data = array_merge($this->pageField, $this->replaceFieldsName($data['fields']));
            $res = [];
            foreach($data as $field => $value) { $res[] = $field; }
            return $res;
        }
        return [];
    }

    // Private Functions

    private function deleteTable(): void 
    {
        $forge = new Forge($this->prefix . $this->name);
        $forge->delete();
    }

    private function getRelationTabs(array &$data): void
    {
        if (isset($data) && is_array($data)) {
            $this->relationsTab = '';
            foreach($data as $name => &$field) {
                if (isset($field['relation'])) {
                    if ($this->relationsTab) { $this->relationsTab .= ", \n\t\t"; }
                    $this->relationsTab .= "'" . $name . ($field['relation'] === 'left' ? "' => true" : "' => false");
                    unset($data[$name]);
                }
            }
            if ($this->relationsTab) { 
                $this->relationsTab = '[' . $this->relationsTab . ']'; 
                return;
            }
            $this->relationsTab = 'null';
        }
    }

    private function replaceFieldsName(array $data): array 
    {
        if (isset($data) && is_array($data)) {
            foreach($data as $name => $field) {
                if (isset($field['max_length'])) {
                    if (! isset($field['constraint'])) {
                        $field['constraint'] = $field['max_length'];
                    }
                }
                $data[$name] = array_intersect_key($field, ['name'=>1, 'constraint'=>1, 'default'=>1, 'type'=>1, 'null'=>1, 'unsigned'=>1, 'unique'=>1, 'auto_increment'=>1]);
                if (! $data[$name]) { unset($data[$name]); }
            }
            return $data;
        }
        return [];
    }

    private function getFileName(): string 
    {
        if (! $this->name) { return ''; }
        return WRITEPATH . 'base' . DIRECTORY_SEPARATOR . castingPath(Paths::MODEL . $this->name, true) . '.json';
    }
}