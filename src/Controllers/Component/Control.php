<?php

namespace Sygecon\AdminBundle\Controllers\Component;

use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Libraries\Parsers\ControlBuilder as BaseModel;
use Sygecon\AdminBundle\Config\Paths;

final class Control extends AdminController 
{
    private const APP_ROUTE = APPPATH . 'Config' . DIRECTORY_SEPARATOR . 'Boot' . DIRECTORY_SEPARATOR . 'routes.php';
    
    private $model;

    public function __construct() {
        $this->model = new BaseModel('');
    }

    /**
     * @param string $name
     * @return string 
     */
    public function index(string $name = ''): string
    {
        if (strtolower($this->request->getMethod()) !== 'get') return $this->pageNotFound();
        
        if ($name) return $this->build('control_edit', $this->edit($name), 'Component');
        
        if ($this->request->isAJAX()) {
            if ($data = $this->model->find()) {
                return $this->successfulResponse($data);
            }
            return $this->successfulResponse([]);
        }

        return $this->build('control', [
            'dataRoute' => (is_file(self::APP_ROUTE) ? esc(file_get_contents(self::APP_ROUTE)) : ''),
            'head' => [
                'icon' => 'controller', 
                'title' => lang('Admin.menu.sidebar.controllerDesc'),
                'icon-route' => 'send-check',
                'title-route' => lang('Admin.menu.sidebar.routeName')
            ]
        ], 'Component');
    }

    /**
     * Create a new object.
     * @return string
     */
    public function create(): string 
    {
        $data = $this->postDataValid($this->request->getPost(), 512, 128);

        if (isset($data['name']) === false || strlen($data['name']) < 3) return $this->pageNotFound();
        if (in_array(strtolower($data['name']), Paths::FORBID_CLASS_NAMES) === true) return $this->pageNotFound();
        if (! $this->model->insert($data)) return $this->pageNotFound();
        return $this->successfulResponse($data['name']);
    }
    
    /**
     * Update a object.
     * @param string $name
     * @return string
     */
    public function update(string $name = ''): string 
    {
        if (! $name) return $this->pageNotFound();
        $data = $this->postDataValid($this->request->getRawInput(), 128);
        if (false === isset($data)) return $this->pageNotFound();
        if (! $this->model->update($name, $data)) return $this->pageNotFound();
        return $this->successfulResponse($name);
    }

    /**
     * Delete object from the model.
     * @param string $name
     * @return string
     */
    public function delete(string $name = ''): string 
    {
        if ($name && $this->model->delete($name)) {
            return $this->successfulResponse('1');
        }
        return $this->pageNotFound();
    }

    /**
     * Редактор данных PHP
     * @param string $name
     * @return array
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
