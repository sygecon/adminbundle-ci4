<?php
namespace Sygecon\AdminBundle\Controllers\Template;

use CodeIgniter\HTTP\ResponseInterface;
use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Models\Template\LayoutModel as BaseModel;

class Layout extends AdminController 
{
    protected $model;

    public function __construct() {
        $this->model = new BaseModel();
    }

    public function index(int $id = 0): ResponseInterface 
    {
        if ($this->request->getMethod() !== 'get') { return $this->fail(lang('Admin.IdNotFound')); }
        
        if ($id) {
            return $this->respond(
                $this->build('layout_edit', $this->edit((int) $id), 'Template')
            , 200);
        }

        return $this->respond($this->build('layout', ['head' => 
            ['icon' => 'layout-text-sidebar', 'title' => lang('Admin.menu.sidebar.layoutsDesc')]
        ], 'Template'), 200);
    }

    /**
     * Add or update a model resource, from "posted" properties.
     * @param int $id
     * @return array an array
     */
    public function update(int $id = 0): ResponseInterface 
    {
        if (! $id) { return $this->fail(lang('Admin.IdNotFound')); }
        if (! $data = $this->request->getRawInput()) { 
            return $this->fail(lang('Admin.IdNotFound')); 
        }
        $this->model->clearCache();
        
        if (array_key_exists('block_id', $data)) {
            if (isset($data['action']) && $data['action']) {
                if ($data['action'] === 'add') {
                    $this->model->setModel((int) $id, checkFileName($data['block_id']));
                } else {
                    $this->model->setModel((int) $id);
                }
            }
            return $this->respondUpdated($id, lang('Admin.navbar.msg.msg_update'));
        }

        $data = $this->postDataValid($data);
        if (! $oldData = $this->model->find((int)$id, 'name')) {
            return $this->fail(lang('Admin.IdNotFound'));
        }

        if (isset($data['data'])) {
            if ($this->fileLayout($oldData->name, $data['data']) === false) {
                return $this->fail(lang('Admin.IdNotFound'));
            }
            return $this->respondUpdated($id, lang('Admin.navbar.msg.msg_update'));
        } 

        if (! isset($data['title'])) { return $this->fail(lang('Admin.IdNotFound')); }
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

        if ($this->model->update($id, $data) === false) { 
            return $this->fail(lang('Admin.IdNotFound')); 
        }
        return $this->respondUpdated($id, lang('Admin.navbar.msg.msg_update'));
    }

    /**
     * Create a new resource object, from "posted" parameters.
     * @return array an array
     */
    public function create(): ResponseInterface 
    {
        $data = $this->postDataValid($this->request->getPost());
        if (isset($data['name']) && mb_strlen($data['name']) > 3) {
            if ($id = $this->model->insert($data)) {
                $this->model->clearCache();
                return $this->respondCreated($id, lang('Admin.navbar.msg.msg_insert')); //
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
        if (!$id) return $this->fail(lang('Admin.IdNotFound'));
        if (!$this->model->delete($id)) {
            return $this->failNotFound(lang('Admin.navbar.msg.msg_get_fail'));
        }
        $this->model->clearCache();
        return $this->respondDeleted($id, lang('Admin.navbar.msg.msg_delete'));
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
