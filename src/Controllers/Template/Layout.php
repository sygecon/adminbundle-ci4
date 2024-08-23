<?php
namespace Sygecon\AdminBundle\Controllers\Template;

use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Models\Template\LayoutModel as BaseModel;

class Layout extends AdminController 
{
    protected $model;

    public function __construct() {
        $this->model = new BaseModel();
    }

    public function index(int $id = 0): string 
    {
        if (strtolower($this->request->getMethod()) !== 'get') return $this->pageNotFound(); 
        
        if ($id) return $this->build('layout_edit', $this->edit((int) $id), 'Template');
        
        return $this->build('layout', ['head' => 
            ['icon' => 'layout-text-sidebar', 'title' => lang('Admin.menu.sidebar.layoutsDesc')]
        ], 'Template');
    }

    /**
     * Update a object.
     * @param int $id
     * @return string
     */
    public function update(int $id = 0): string 
    {
        if (! $id) return $this->pageNotFound();
        if (! $data = $this->request->getRawInput()) return $this->pageNotFound();
        
        $this->model->clearCache();
        if (array_key_exists('block_id', $data)) {
            if (isset($data['action']) && $data['action']) {
                if ($data['action'] === 'add') {
                    $this->model->setModel((int) $id, checkFileName($data['block_id']));
                } else {
                    $this->model->setModel((int) $id);
                }
            }
            return $this->successfulResponse($id);
        }

        $data = $this->postDataValid($data);
        if (! $oldData = $this->model->find((int)$id, 'name')) {
            return $this->pageNotFound();
        }

        if (isset($data['data'])) {
            if ($this->fileLayout($oldData->name, $data['data']) === false) {
                return $this->pageNotFound();
            }
            return $this->successfulResponse($id);
        } 

        if (! isset($data['title'])) return $this->pageNotFound();
        if (array_key_exists('name', $data)) { 
            $data['name'] = mb_strtolower(trim($data['name']));
            if ($data['name']) {
                $oldName = $oldData->name;
                if ($oldName !== $data['name']) {
                    helper('files');
                    renameFile(APPPATH . toPath(PATH_TEMPLATE . DIRECTORY_SEPARATOR . PATH_LAYOUT), $oldName . '.php', $data['name']);
                }
            } else {
                unset($data['name']); 
            }
        }

        if ($this->model->update($id, $data) === false) return $this->pageNotFound(); 
        return $this->successfulResponse($id);
    }

    /**
     * Create a new object.
     * @return string
     */
    public function create(): string 
    {
        $data = $this->postDataValid($this->request->getPost());
        $id = $this->model->create($data);
        return ($id ? $this->successfulResponse($id) : $this->pageNotFound());
    }

    /**
     * Delete object from the model.
     * @param int $id
     * @return string
     */
    public function delete(int $id = 0): string 
    {
        if ($id && $this->model->delete($id)) {
            $this->model->clearCache();
            return $this->successfulResponse($id);
        }
        return $this->pageNotFound();
    }
    
    /**
     * Редактор данных PHP
     * Return the editable properties of a resource object.
     * @param int $id
     * @return array an array
     */
    private function edit(int $id = 0): ?array 
    { 
        if (! $row = $this->model->getBuilder($id)) { return null; }

        return [
            'id' => $id,
            'dataJson' => $this->fileLayout($row->name), 
            'head' => ['icon' => 'menu-up',
                'h1' => $this->genLinkHome(lang('Admin.editorTitle')),
                'title' => (! $row->title ? $row->name : $row->title . ' (' . $row->name . ')'),
                'icon_block' => 'table', 
                'title_block' => lang('HeadLines.catalog.sheetLayout')
            ]
        ];
    }

    /** Чтение и запись Макета в файл */
    private function fileLayout(string $name = '', ?string $data = null)
    {
        if (! $name) { return ''; }
        helper('path');
        $fileName = templateFile(PATH_LAYOUT . DIRECTORY_SEPARATOR . trim($name, ' /\\'));

        if ($data !== null && is_string($data)) { 
            return (writingDataToFile($fileName, $data) !== false ? true : false); 
        }
        return readingDataFromFile($fileName);
    }
}
