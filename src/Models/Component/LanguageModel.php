<?php namespace Sygecon\AdminBundle\Models\Component;

use CodeIgniter\Config\Services;
use App\Models\Boot\BaseModel as Model;
use Sygecon\AdminBundle\Libraries\СonstantEditor;
use Sygecon\AdminBundle\Models\Catalog\PagesModel;
use Throwable;

final class LanguageModel extends Model
{
    protected const FILE_CONFIG = APPPATH . 'Config' . DIRECTORY_SEPARATOR . 'App.php';
    protected const MAX_COUNT_BATCH = 99;

    protected $table = 'language';
    protected $columnsFindAll = 'id, name, title, icon';

    protected bool $multilanuage = false;

    public function create(array $data = []): int
    {
        if (! $data) { return (int) 0; }
        if (! isset($data['name'])) { return (int) 0; }
        if (isset($data['id'])) { unset($data['id']); }

        $pos = (int) $this->countAll();
        $data['position'] = ++$pos;
        if (! $id = $this->insert($data)) { return (int) 0; }

        if ($pos === 1) {
            $this->setUpdate($data['name']);
            return $id;
        }

        if (! $defId = (int) $this->getDefaultId()) { return $id; }
        $this->setUpdate();
        if ($this->multilanuage === false) { return $id; }

        $pageModel = new PagesModel;
        if (! $data = $pageModel->builder()->where('language_id', $defId)->get()->getResult('array')) {
            return $id;
        }

        $box = [];
        $e = 0;
        foreach ($data as $i => &$row) {
            $row['language_id'] = (int) $id;
            unset($row['id'], $row['created_at'], $row['updated_at']);
            $box[] = $row;
            array_splice($row, 0);
            unset($data[$i]);
            ++$e;
            if ($e === self::MAX_COUNT_BATCH) {
                $e = 0;
                $pageModel->insertBatch($box);
                $this->clearArray($box);
            }
        }

        if ($box) {
            $pageModel->insertBatch($box);
            $this->clearArray($box);
        }
        unset($data, $box);

        return $id;
    }

    public function remove(int $id = 0): bool
    {
        if (! $id) { return false; }
        $len = (int) $this->countAll();
        if (is_numeric($len) === false || 2 > $len) return false;
        if (! $row = $this->find((int) $id, 'position')) { return false; }
        if (! $this->delete((int) $id)) { return false; }
        $this->db->query(
            'UPDATE ' . $this->table . ' SET position = position - 1 WHERE position > ' . (int) $row->position
        );

        $pageModel = new PagesModel();
        $pageModel->deleteByLanguage((int) $id);

        if ($row->position === 1) {
            $this->updateDefault();
            return true;
        }
        $this->setUpdate();
        return true;
    }

    // Перемещение на определенную позицию
    public function moveToPos(int $id = 0, int $newPosition = 0): bool
    {
        if (! $id) { return false; }
        if (! $row = $this->find((int) $id, 'name, position')) { return false; } 
        $oldPosition = (int) $row->position;
        ++$newPosition;
        if ($newPosition === $oldPosition) { return false; }
        $name = '';
        if ($newPosition === 1) { $name = $row->name; }
        unset($row->name, $row->position, $row);

        if ($newPosition > $oldPosition) { 
            $this->db->query(
                'UPDATE ' . $this->table . ' SET position = position - 1 WHERE position > ' . $oldPosition . ' AND position <= ' . $newPosition
            );
        } else {
            $this->db->query(
                'UPDATE ' . $this->table . ' SET position = position + 1 WHERE position < ' . $oldPosition . ' AND position >= ' . $newPosition
            );
        }
        $this->db->query(
            'UPDATE ' . $this->table . ' SET position = ' . $newPosition . ' WHERE id = ' . (int) $id
        );

        if ($oldPosition === 1) {
            $this->updateDefault();
            return true;
        }
        $this->setUpdate($name);
        return true;
    }

    public function getDefaultId(): int 
    {
        if (defined('APP_DEFAULT_LOCALE') && defined('SUPPORTED_LOCALES') && isset(SUPPORTED_LOCALES[APP_DEFAULT_LOCALE])) {
            return (int) SUPPORTED_LOCALES[APP_DEFAULT_LOCALE];
        }
        return $this->updateDefault();
    }

    public function getAll(): array
    {
        $cache = Services::cache();
        if ($data = $cache->get('Language_All')) { return $data; }

        if (! $data = $this->queryAllSelect()) { return []; }
        $cache->save('Language_All', $data, 40320);
        return $data;
    }
    
    public function getForSelect(): array
    {
        $cache = Services::cache();
        if ($data = $cache->get('Language_ForSelect')) { return $data; }

        $result = [];
        $data = $this->queryAllSelect();
        foreach($data as $i => &$val) {
            $result[$val->id] = [$val->name, $val->title, $val->icon, $val->default];
            unset($data[$i]);
        }
        unset($data);
        if ($result) { $cache->save('Language_ForSelect', $result, 40320); }
        return $result;
    }

    public function setUpdate(string $defaultName = ''): void 
    {
        $data   = [];
        $params = [];
        $value  = '';
        $json   = '';
        $n      = 0;
        $cache  = Services::cache();
        $cache->deleteMatching('Language_*');
        
        if ($items = $this->builder()->select('id, name, title, icon')->orderBy('position', 'ASC')->get()->getResult()) {
            foreach ($items as &$row) {
                $data[$row->name] = (int) $row->id;
                $value .= chr(39) . $row->name . chr(39) . ', ';
                $json  .= "\t" . '"' . $row->name . '": {"title": "' . $row->title . '", "icon": "' . $row->icon . '"},' . "\n"; // '"id": ' . $row->id . 
                ++$n;
            }
        }
        $this->multilanuage = (bool) ($n > 1); 

        if ($value) { 
            $value = substr($value, 0, -2); 
            $json = substr($json, 0, -2);
        }
        $params[] = ['supportedLocales' => '[' . $value . ']'];

        if (defined('LIST_LANGUAGES_JSON')) {
            file_put_contents(LIST_LANGUAGES_JSON, "{\n" . $json . "\n}", LOCK_EX);
        }
        unset($value, $json);

        $constEditor = new СonstantEditor();

        if ($defaultName) {
            if ($defaultName !== $constEditor->get('APP_DEFAULT_LOCALE')) { 
                $constEditor->set('APP_DEFAULT_LOCALE', $defaultName);
                $params[] = ['defaultLocale' => chr(39) . $defaultName . chr(39)];
            }
        }
        
        $constEditor->setArray('SUPPORTED_LOCALES', $data);
        $constEditor->setBoolean('MULTILINGUALITY', $this->multilanuage);
        $constEditor->save();

        $this->setConfig($params);
    }

    protected function queryAllSelect(): array
    {
        try {
            $query = $this->db->query(
                'SELECT ' . $this->columnsFindAll . ', 0 AS `default` FROM ' . $this->table . ' ORDER BY position'
            );
            if (! $data = $query->getResult($this->returnType)) { return []; }
            if (isset($data[0])) { $data[0]->default = 1; }
            return $data;
        } catch (Throwable $th) {
            return [];
        }
    }

    protected function updateDefault(): int 
    {
        if ($res = $this->builder()->select('id, name')->orderBy('position', 'ASC')->limit(1)->get()->getResult()) {
            $this->setUpdate($res[0]->name);
            return (int) $res[0]->id;
        }
        return (int) 0;
    }

    protected function setConfig(array &$data): void
    {
        if (! $data) { return; }
        if (is_file(self::FILE_CONFIG) === false) { return; }
        if (! $text = file_get_contents(self::FILE_CONFIG)) { return; }
        helper('match');
        $change = false;
        foreach($data as $key => &$value) {
            $param = key($value);
            if (! $param) { continue; }
            if (! isset($value[$param])) { continue; }
            if (writeParamToClass($text, $param, $value[$param]) === true) {
                $change = true;
            }
            unset($value[$param], $data[$key]);
        }
        if ($change === true) {
            file_put_contents(self::FILE_CONFIG, $text, LOCK_EX);
        }
    }

    protected function clearArray(array &$data): void
    {
        foreach($data as $key => &$value) {
            array_splice($value, 0);
            unset($data[$key]);
        }
    }
}