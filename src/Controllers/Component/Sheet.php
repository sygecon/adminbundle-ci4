<?php
namespace Sygecon\AdminBundle\Controllers\Component;

use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Models\Component\SheetModel as BaseModel;
use Sygecon\AdminBundle\Models\Template\LayoutModel;
use Sygecon\AdminBundle\Libraries\Table\SheetForge;
use Throwable;

final class Sheet extends AdminController {

    protected const TAB_PREFIX = 'model_';

    protected const MODEL_SUFFIX = 'Model';

    protected $model;

    public function __construct() {
        $this->model = new BaseModel();
    }

    public function index(int $id = 0): ResponseInterface 
    {
        if ($this->request->getMethod() !== 'get') {
            return $this->fail(lang('Admin.IdNotFound'));
        }
        if ($id) {
            return $this->respond($this->build('sheet_edit', $this->edit($id), 'Component'), 200);
        }
        if ($this->request->isAJAX()) {
            if ($data = $this->model->findAll('updated_at', false)) {
                return $this->respond(jsonEncode($data, false), 200);
            }
            return $this->respond('[]', 200);
        }
        return $this->respond($this->build('sheet', 
            ['head' => ['icon' => 'table', 'title' => lang('Admin.menu.sidebar.modelsDesc')]]
        , 'Component'), 200);
    }

    /**
     * Create a new resource object, from "posted" parameters.
     * @return array an array
     */
    public function create(): ResponseInterface 
    {
        $data = $this->postDataValid($this->request->getPost(), 32, 128);
        if (! isset($data['name'])) { return $this->fail(lang('Admin.IdNotFound')); }
        helper('path');
        $data['name'] = toSnake($data['name']);
        if (strlen($data['name']) < 2) { return $this->fail(lang('Admin.IdNotFound')); }
        
        if ($id = $this->model->insert($data)) {
            $data['sheet_id'] = (int) $id;

            $SheetForge = new SheetForge($data['name'], self::TAB_PREFIX);
            $SheetForge->create();
            
            $model = new LayoutModel();
            $model->insert($data);
            $model->clearCache();

            return $this->respondCreated($id, lang('Admin.navbar.msg.msg_insert')); //
        }
        return $this->fail(lang('Admin.IdNotFound'));
    }

    /**
     * Add or update a model resource, from "posted" properties.
     * @param int $id
     * @return array an array
     */
    public function update(int $id = 0): ResponseInterface 
    {
        if (!$id) return $this->fail(lang('Admin.IdNotFound'), 400);
        $data = $this->postDataValid($this->request->getRawInput(), 32, 128);
        if (!$data) { return $this->fail(lang('Admin.IdNotFound')); }
        if (! $oldData = $this->model->find((int)$id, 'name')) { 
            return $this->fail(lang('Admin.IdNotFound')); 
        }
        $sheetName = $oldData->name;
        unset($oldData->name, $oldData);

        if (array_key_exists('title', $data)) {
            if (array_key_exists('name', $data)) { 
                helper('path');
                $data['name'] = toSnake($data['name']);
                if (strlen($data['name']) < 2) { return $this->fail(lang('Admin.IdNotFound')); }
                if ($data['name'] === $sheetName) { 
                    unset($data['name']); 
                    $sheetName = '';
                }
            }

            if (! $this->model->update($id, $data)) {
                return $this->fail(lang('Admin.IdNotFound'));
            }

            if ($sheetName && isset($data['name'])) {
                // Переименовываем 
                $this->model->renameSheetNameForLayout($sheetName, $data['name']);
            }
            return $this->respondUpdated($id, lang('Admin.navbar.msg.msg_update'));
        }
        //! Сохраниение структуры таблицы в файл    
        if (isset($data['data_table'])) {

            $SheetForge = new SheetForge($sheetName, self::TAB_PREFIX);
            if (! $name = $SheetForge->update($data['data_table'])) {
                echo '<script type="text/javascript">window.location.reload(true);</script>';
                //return $this->fail(lang('Admin.IdNotFound'));
                // $config = config('App');
                // $url = rtrim($config->baseURL, '/') . '/direction/component/sheet';
                
                // echo '<script type="text/javascript">window.location.replace("' . $url . '");</script>';
            }

            helper('path');
            try {
                command('make:app-model-direct ' . toClassUrl($sheetName) . ' --table ' . $name . ' --namespace Sygecon\AdminBundle --suffix ' . self::MODEL_SUFFIX);
                command('make:app-model ' . toClassUrl($sheetName) . ' --table ' . $name . ' --suffix ' . self::MODEL_SUFFIX);
            } catch (Throwable $th) {
                baseWriteFile('logs/Errors/Model-Update-Command.log', date('H:i:s d.m.Y') . ' => ' . $th->getMessage());
            }
            // Обновление параметров модели [ protected $relations = ;]
            if ($SheetForge->relationsTab) {
                $dataAppFile = $this->modelFile($sheetName, 'direct');
                $this->updateParam('$relations', $SheetForge->relationsTab, $dataAppFile);
                $this->modelFile($sheetName, 'direct', $dataAppFile);
                // baseWriteFile('fields-file.txt', $dataAppFile);
                // baseWriteFile('fields.txt', jsonEncode($SheetForge->relationsTab, true));
            }
            return $this->respondUpdated($id, lang('Admin.navbar.msg.msg_update'));
        }
        //! Сохраниение модели в файл
        if (isset($data['data_app_model'])) { 

            if ($this->modelFile($sheetName, 'app', $data['data_app_model'])) {
                return $this->respondUpdated($id, lang('Admin.navbar.msg.msg_update'));
            }
            return $this->fail(lang('Admin.IdNotFound'));
        }
        //! Сохраниение модели в файл
        if (isset($data['data_direct_model'])) { 

            if ($this->modelFile($sheetName, 'direct', $data['data_direct_model'])) {
                return $this->respondUpdated($id, lang('Admin.navbar.msg.msg_update'));
            }
        }
        return $this->fail(lang('Admin.IdNotFound'));
    }

    /**
     * Delete the designated resource object from the model.
     * @param int $id
     */
    public function delete(int $id = 0): ResponseInterface 
    {
        if (!$id) return $this->fail(lang('Admin.IdNotFound'), 400);
        if (!$this->model->delete($id)) {
            return $this->failNotFound(lang('Admin.navbar.msg.msg_get_fail'));
        }
        return $this->respondDeleted($id, lang('Admin.navbar.msg.msg_delete'));
    }
    
    // Редактор данных PHP 
    /**
     * Return the editable properties of a resource object.
     * @param int $id
     * @return array an array
     */
    protected function edit(int $id = 0): ?array {
        
        if (! $row = $this->model->find((int) $id)) { return null; }
        $SheetForge = new SheetForge($row->name, self::TAB_PREFIX);
        
        return [
            'id' => (int) $id,
            'dataTable' => $SheetForge->getFrame(),
            'isTable' => $SheetForge->isTable(),
            'appModel' => esc($this->modelFile($row->name, 'app')),
            'directModel' => esc($this->modelFile($row->name, 'direct')),
            'head' => [
                'h1' => $this->genLinkHome((! $row->title ? $row->name : $row->title)),
                'icon' => 'menu-up',
                'title' => lang('Admin.editorTitle') . lang('HeadLines.catalog.frm.formPageModelShow'),
                'titleBase' => lang('Admin.editorTitle') . lang('HeadLines.catalog.frm.formPageModelAdmin'),
            ]
        ];
    }

    /**
     * Builds the file path from the class name.
     */
    private function getNSPath(string $class = '', string $mod = ''): string 
    {
        if (!$mod) { return ''; }
        if (!$class) { return ''; }
        $nsModels = [
            'app' => ['App', 'Models'],
            'direct' => ['Admin', 'Models\Layout']
        ];
        $namespace = trim(str_replace('/', '\\', $nsModels[$mod][0]), '\\/');
        $file = Services::autoloader()->getNamespace($namespace);
        if (!$file = reset($file)) { return ''; }
        //$file = realpath($file) ?: $file;
        $file .= DIRECTORY_SEPARATOR . trim($nsModels[$mod][1], '\\/');
        $file .= DIRECTORY_SEPARATOR . trim(str_replace($namespace . '\\', '', ucfirst($class) . self::MODEL_SUFFIX), '\\/') . '.php';
        return castingPath(str_replace(['\\', '//', '\\\\'], DIRECTORY_SEPARATOR, implode(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $file), 0, -1)) . DIRECTORY_SEPARATOR) . basename($file));
    }

    private function modelFile(string $name, string $mod, $data = null): string 
    {
        if ($fileName = $this->getNSPath($name, $mod)) {
            helper('path');
            if ($data) {
                return (writingDataToFile($fileName, $data) === true ? '1' : '');
            } else {
                return readingDataFromFile($fileName);
            }
        }
        return '';
    }

    // Изменение параметров Модели
    private function updateParam(string $param, string $newText, string &$data): void
    {
        if (! $pos = mb_strpos($data, ' ' . $param)) { return; }
        if (! $start = mb_strpos($data, '=', $pos)) { return; }
        ++$start;
        if (! $end = mb_strpos($data, ';', $start)) { return; }
        if ($end > $start) {
            $data = rtrim(mb_substr($data, 0, $start)) . ' ' . $newText . ltrim(mb_substr($data, $end));
        }
    }
}
