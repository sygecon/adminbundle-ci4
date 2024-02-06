<?php
namespace Sygecon\AdminBundle\Controllers\Navigation;

use CodeIgniter\HTTP\ResponseInterface;
use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Models\Navigation\CustomPanelModel as BaseModel;

final class CustomPanel extends AdminController 
{

    protected const CACHE_PREFIX = 'Admin_apiNavAdmin_';

    protected $model;
    protected $path = 'control' . DIRECTORY_SEPARATOR . 'nav';

    public function __construct() {
        $this->model = new BaseModel();
    }

    public function index(int $id = 0): ResponseInterface 
    {
        if ($this->request->getMethod() !== 'get') {
            return $this->fail(lang('Admin.IdNotFound'));
        }
        if ($this->request->isAJAX()) {
            if ($data = $this->model->findAll('name', false)) {
                return $this->respond(jsonEncode($data, false), 200);
            }
            return $this->respond('[]', 200);
        }

        if ($id) {
            return $this->respond(
                $this->build('custom_panel_edit', $this->edit($id), 'Navigation')
            , 200);
        }

        return $this->respond($this->build('custom_panels', [
            'head' => [
                'icon' => 'menu-button-wide', 
                'title' => lang('Admin.menu.sidebar.navPanelsDesc')
            ]
        ], 'Navigation'), 200);
    }

    /**
     * Create a new resource object, from "posted" parameters.
     * @return array an array
     */
    public function create(): ResponseInterface 
    {
        $data = $this->postDataValid($this->request->getPost(), 32, 128);
        //return jsonEncode($dataPost, false);
        if (! isset($data['name'])) {
            return $this->fail(lang('Admin.IdNotFound'));
        }
        helper('path');
        $data['name'] = toSnake($data['name']);
        if (strlen($data['name']) > 3) {
            if ($id = $this->model->insert($data)) {
                return $this->respondCreated($id, lang('Admin.navbar.msg.msg_insert'));
            }
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
        $data = $this->postDataValid($this->request->getRawInput(), 32, 128);
        if (!$data) { return $this->fail(lang('Admin.IdNotFound')); }
        $oldData = $this->model->find((int)$id, 'name');
        if (!isset($oldData) || !$oldData) { return $this->fail(lang('Admin.IdNotFound')); }
        $path = WRITEPATH . 'base' . DIRECTORY_SEPARATOR . castingPath($this->path, true);
        cache()->delete(self::CACHE_PREFIX . $oldData->name);

        helper('path');
        if (isset($data['data'])) {
            if (! writingDataToFile($path . DIRECTORY_SEPARATOR . $oldData->name . '.json', $data['data'])) {
                return $this->fail(lang('Admin.IdNotFound'));
            }
            return $this->respondUpdated($id, lang('Admin.navbar.msg.msg_update'));
        }
        if (! isset($data['name'])) {
            return $this->fail(lang('Admin.IdNotFound'));
        }
        $data['name'] = toSnake($data['name']);
        if (strlen($data['name']) > 3) {
            if (!$this->model->update($id, $data)) { return $this->fail(lang('Admin.IdNotFound')); }
            if ($oldData->name  !== $data['name']) {
                helper('files');
                renameFile($path, $oldData->name . '.json', $data['name']);
            }
        }
        return $this->respondUpdated($id, lang('Admin.navbar.msg.msg_update'));
    }

    /**
     * Delete the designated resource object from the model.
     * @param int $id
     */
    public function delete(int $id = 0): ResponseInterface 
    {
        //if (!$this->model->delete($id)) {
            return $this->failNotFound(lang('Admin.navbar.msg.msg_get_fail'));
        // }
        // return $this->respondDeleted($id, lang('Admin.navbar.msg.msg_delete'));
    }
    
    /**
     * Return the editable properties of a resource object.
     * @param int $id
     * @return array an array
     */
    private function edit(int $id = 0): ?array {
        // Редактор данных меню JSON 
        if (! $row = $this->model->find((int) $id, 'name, title')) { return null; }

        $title = $row->name;
        if ($title) { $title = $row->title . ' (' . $title . ')'; }
        helper('path');

        return [
            'dataJson' => baseReadFile($this->path . DIRECTORY_SEPARATOR . $row->name . '.json'),
            'head' => [
                'h1' => $this->genLinkHome(lang('Admin.editorTitle')),
                'icon' => 'menu-up', 'title' => $title
            ]
        ];
    }
}
