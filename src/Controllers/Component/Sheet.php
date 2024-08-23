<?php
namespace Sygecon\AdminBundle\Controllers\Component;

use Config\Services;
use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Models\Component\SheetModel as BaseModel;
use Sygecon\AdminBundle\Libraries\Table\SheetForge;
use Throwable;

final class Sheet extends AdminController {

    protected const MODEL_SUFFIX = 'Model';

    protected $model;

    public function __construct() {
        $this->model = new BaseModel();
        helper('path');
    }

    public function index(int $id = 0): string 
    {
        if (strtolower($this->request->getMethod()) !== 'get') return $this->pageNotFound();
        
        if ($id) return $this->build('sheet_edit', $this->edit($id), 'Component');
        
        if ($this->request->isAJAX()) {
            if ($data = $this->model->findAll('updated_at', false)) {
                return $this->successfulResponse($data);
            }
            return $this->successfulResponse([]);
        }

        return $this->build('sheet', 
            ['head' => ['icon' => 'table', 'title' => lang('Admin.menu.sidebar.modelsDesc')]]
        , 'Component');
    }

    /**
     * Create a new resource object, from "posted" parameters.
     * @return string
     */
    public function create(): string 
    {
        $data = $this->postDataValid($this->request->getPost(), 32, 128);
        
        if (! $id = $this->model->create($data)) return $this->pageNotFound();
        return $this->successfulResponse($id);
    }

    /**
     * Add or update a model resource, from "posted" properties.
     * @param int $id
     * @return string
     */
    public function update(int $id = 0): string
    {
        if (! $id) return $this->pageNotFound();
        if (! $data = $this->postDataValid($this->request->getRawInput(), 32, 128)) return $this->pageNotFound();
        if (! $oldData = $this->model->find((int)$id, 'name')) return $this->pageNotFound();
        
        $sheetName = $oldData->name;
        unset($oldData->name, $oldData);

        if (array_key_exists('title', $data)) {
            if (array_key_exists('name', $data)) {
                $data['name'] = toSnake($data['name']);
                if (strlen($data['name']) < 2) return $this->pageNotFound();
                if ($data['name'] === $sheetName) { 
                    unset($data['name']); 
                    $sheetName = '';
                }
            }
            if (! $this->model->update($id, $data)) return $this->pageNotFound();
            
            // Переименовываем 
            if ($sheetName && isset($data['name'])) {   
                $this->model->renameSheetNameForLayout($sheetName, $data['name']);
            }
        } else
        //! Сохраниение структуры таблицы в файл    
        if (isset($data['data_table'])) {

            $SheetForge = new SheetForge($sheetName, $this->model::TAB_PREFIX);
            if (! $name = $SheetForge->update($data['data_table'])) {
                echo '<script type="text/javascript">window.location.reload(true);</script>';
                //return $this->fail(lang('Admin.IdNotFound'));
                // $config = config('App');
                // $url = rtrim($config->baseURL, '/') . '/direction/component/sheet';
                
                // echo '<script type="text/javascript">window.location.replace("' . $url . '");</script>';
            }

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
            
        } else
        //! Сохраниение модели в файл
        if (isset($data['data_app_model']) === true) { 
            if (! $this->modelFile($sheetName, 'app', $data['data_app_model'])) {
                return $this->pageNotFound();
            } 
        } else

        if (isset($data['data_direct_model']) === true) { 
            if (! $this->modelFile($sheetName, 'direct', $data['data_direct_model'])) {
                return $this->pageNotFound();
            }
        } else {
            return $this->pageNotFound();
        }
        
        return $this->successfulResponse($id);
    }

    /**
     * Delete the designated resource object from the model.
     * @param string
     */
    public function delete(int $id = 0): string
    {
        if ($id && $this->model->delete($id)) {
            return $this->successfulResponse($id);
        }
        return $this->pageNotFound();
    }
    
    /**
     * Редактор данных PHP 
     * @param int $id
     * @return array an array
     */
    protected function edit(int $id = 0): ?array 
    {
        
        if (! $row = $this->model->find((int) $id)) return null;
        $SheetForge = new SheetForge($row->name, $this->model::TAB_PREFIX);
        
        return [
            'id'            => (int) $id,
            'dataTable'     => $SheetForge->getFrame(),
            'isTable'       => $SheetForge->isTable(),
            'appModel'      => esc($this->modelFile($row->name, 'app')),
            'directModel'   => esc($this->modelFile($row->name, 'direct')),
            'head'          => [
                'h1'        => $this->genLinkHome((! $row->title ? $row->name : $row->title)),
                'icon'      => 'menu-up',
                'title'     => lang('Admin.editorTitle') . lang('HeadLines.catalog.frm.formPageModelShow'),
                'titleBase' => lang('Admin.editorTitle') . lang('HeadLines.catalog.frm.formPageModelAdmin'),
            ]
        ];
    }

    /**
     * Builds the file path from the class name.
     */
    private function getNSPath(string $class = '', string $mod = ''): string 
    {
        if (! $mod) return '';
        if (! $class) return '';
        $nsModels = [
            'app'    => ['App', 'Models'],
            'direct' => ['Admin', 'Models\Layout']
        ];
        $namespace  = trim(str_replace('/', '\\', $nsModels[$mod][0]), '\\/');
        $file       = Services::autoloader()->getNamespace($namespace);
        if (! $file = reset($file)) return '';
        //$file = realpath($file) ?: $file;
        $file .= DIRECTORY_SEPARATOR . trim($nsModels[$mod][1], '\\/');
        $file .= DIRECTORY_SEPARATOR . trim(str_replace($namespace . '\\', '', ucfirst($class) . self::MODEL_SUFFIX), '\\/') . '.php';
        return castingPath(str_replace(['\\', '//', '\\\\'], DIRECTORY_SEPARATOR, implode(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $file), 0, -1)) . DIRECTORY_SEPARATOR) . basename($file));
    }

    private function modelFile(string $name, string $mod, $data = null): string 
    {
        if (! $fileName = $this->getNSPath($name, $mod)) return '';
        if (! $data) return readingDataFromFile($fileName);
        return (writingDataToFile($fileName, $data) === true ? '1' : '');
    }

    // Изменение параметров Модели
    private function updateParam(string $param, string $newText, string &$data): void
    {
        if (! $pos   = mb_strpos($data, ' ' . $param)) return;
        if (! $start = mb_strpos($data, '=', $pos)) return;
        ++$start;
        if (! $end   = mb_strpos($data, ';', $start)) return;

        if ($end > $start)
            $data = rtrim(mb_substr($data, 0, $start)) . ' ' . $newText . ltrim(mb_substr($data, $end));
    }
}
