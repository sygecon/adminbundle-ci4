<?php

namespace Sygecon\AdminBundle\Controllers\Component;

use CodeIgniter\HTTP\ResponseInterface;
use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Libraries\Parsers\ControlBuilder as BaseModel;

final class Control extends AdminController {

    private const APP_ROUTE = APPPATH . 'Config' . DIRECTORY_SEPARATOR . 'Boot' . DIRECTORY_SEPARATOR . 'routes.php';
    
    private $model;

    public function __construct() {
        $this->model = new BaseModel('');
    }

    public function index(string $name = ''): ResponseInterface 
    {
        if ($this->request->getMethod() !== 'get') {
            return $this->fail(lang('Admin.IdNotFound'));
        }
        if ($name) {
            return $this->respond($this->build('control_edit', $this->edit($name), 'Component'), 200);
        }

        if ($this->request->isAJAX()) {
            if ($data = $this->model->find()) {
                return $this->respond(jsonEncode($data, false), 200);
            }
            return $this->respond('[]', 200);
        }

        return $this->respond($this->build('control', [
            'dataRoute' => (is_file(self::APP_ROUTE) ? esc(file_get_contents(self::APP_ROUTE)) : ''),
            'head' => [
                'icon' => 'controller', 
                'title' => lang('Admin.menu.sidebar.controllerDesc'),
                'icon-route' => 'send-check',
                'title-route' => lang('Admin.menu.sidebar.routeName')
            ]
        ], 'Component'), 200);
    }

    /**
     * Create a new resource object, from "posted" parameters.
     * @return array an array
     */
    public function create(): ResponseInterface 
    {
        $data = $this->postDataValid($this->request->getPost(), 512, 128);
        if (isset($data['name']) && mb_strlen($data['name']) > 2) {
            if ($this->model->insert($data)) {
                return $this->respondCreated($data['name'], lang('Admin.navbar.msg.msg_insert')); //
            }
        }
        return $this->fail(lang('Admin.IdNotFound'));
    }
    
    /**
     * Add or update a model resource, from "posted" properties.
     * @param int $id
     * @return array an array
     */
    public function update(string $name = ''): ResponseInterface 
    {
        if (! $name) { return $this->fail(lang('Admin.IdNotFound')); }
        $data = $this->postDataValid($this->request->getRawInput(), 128);
        if (!isset($data)) { return $this->fail(lang('Admin.IdNotFound')); }
        $res = $this->model->update($name, $data);
        return $this->respondUpdated($res);
    }

    /**
     * Delete the designated resource object from the model.
     * @param int $id
     */
    public function delete(string $name = ''): ResponseInterface 
    {
        if (! $name) { return $this->fail(lang('Admin.IdNotFound')); }
        if (!$this->model->delete($name)) {
            return $this->failNotFound(lang('Admin.navbar.msg.msg_get_fail'));
        }
        return $this->respondDeleted(true, lang('Admin.navbar.msg.msg_delete'));
    }

    // Редактор данных PHP 
    /**
     * Return the editable properties of a resource object.
     * @param string $name
     * @return array an array
     */
    protected function edit(string $name = ''): array 
    {
        $fileName = $this->model->getFile($name);
        if (! is_file($fileName)) { return []; }
        $title = $this->model->titleClassName($name);

        return [
            'id' => $name,
            'dataControl' => esc(file_get_contents($fileName)),
            'head' => [
                'h1' => $this->genLinkHome(($title ? $title : $name)), 
                'icon' => 'controller',
                'title' => lang('Admin.editorTitle') . ' ' . lang('HeadLines.catalog.control'),
            ]
        ];
    }
}
