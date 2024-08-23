<?php
namespace Sygecon\AdminBundle\Controllers\Navigation;

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

    public function index(int $id = 0): string 
    {
        if (strtolower($this->request->getMethod()) !== 'get') return $this->pageNotFound();
        
        if ($this->request->isAJAX()) {
            if ($data = $this->model->findAll('name', false)) {
                return $this->successfulResponse($data);
            }
            return $this->successfulResponse([]);
        }

        if ($id) return $this->build('custom_panel_edit', $this->edit($id), 'Navigation');
        
        return $this->build('custom_panels', [
            'head' => [
                'icon' => 'menu-button-wide', 
                'title' => lang('Admin.menu.sidebar.navPanelsDesc')
            ]
        ], 'Navigation');
    }

    /**
     * Create a new object.
     * @return string
     */
    public function create(): string
    {
        $data = $this->postDataValid($this->request->getPost(), 32, 128);
        if (! isset($data['name'])) return $this->pageNotFound();

        helper('path');
        $data['name'] = toSnake($data['name']);
        if (strlen($data['name']) > 3) {
            if ($id = $this->model->insert($data)) {
                return $this->successfulResponse($id);
            }
        }
        return $this->pageNotFound();
    }

    /**
     * Update a object.
     * @param int $id
     * @return string
     */
    public function update(int $id = 0): string
    {
        $data = $this->postDataValid($this->request->getRawInput(), 32, 128);
        if (! $data) return $this->pageNotFound();
        $oldData = $this->model->find((int)$id, 'name');
        if (! isset($oldData) || !$oldData) return $this->pageNotFound();
        $path = WRITEPATH . 'base' . DIRECTORY_SEPARATOR . castingPath($this->path, true);
        cache()->delete(self::CACHE_PREFIX . $oldData->name);

        helper('path');
        if (isset($data['data'])) {
            if (! writingDataToFile($path . DIRECTORY_SEPARATOR . $oldData->name . '.json', $data['data'])) {
                return $this->pageNotFound();
            }
            return $this->successfulResponse($id);
        }
        if (! isset($data['name'])) return $this->pageNotFound();
        $data['name'] = toSnake($data['name']);
        if (strlen($data['name']) > 3) {
            if (! $this->model->update($id, $data)) return $this->pageNotFound();
            if ($oldData->name !== $data['name']) {
                helper('files');
                renameFile($path, $oldData->name . '.json', $data['name']);
            }
        }
        return $this->successfulResponse($id);
    }

    /**
     * Delete the designated resource object from the model.
     * @param int $id
     */
    public function delete(int $id = 0): string 
    {
        // if (! $this->model->delete($id)) {
            return $this->pageNotFound();
        // }
        // return $this->successfulResponse($id);
    }
    
    /**
     * Return the editable properties of a resource object.
     * @param int $id
     * @return array
     */
    private function edit(int $id = 0): ?array 
    {
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
