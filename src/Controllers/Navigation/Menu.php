<?php
namespace Sygecon\AdminBundle\Controllers\Navigation;

use CodeIgniter\HTTP\ResponseInterface;
use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Models\Navigation\MenuModel as BaseModel;

final class Menu extends AdminController 
{

    protected const CACHE_PREFIX = 'Admin_apiNavAdmin_';

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
            return $this->respond($this->build('menu_edit', $this->edit($id), 'Navigation'), 200);
        }
        if ($this->request->isAJAX()) {
            if ($data = $this->model->findAll('updated_at', false)) {
                return $this->respond(jsonEncode($data, false), 200);
            }
            return $this->respond('[]', 200);
        }
        // $root    = (new \CodeIgniter\HTTP\URI(site_url()))->getPath() ?: '/';
        // $this->detectCurrent(false)
        return $this->respond($this->build('menu', [
            'head' => [
                'icon' => 'menu-button-fill', 
                'title' => lang('Admin.menu.sidebar.navMenuDesc')
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
        if (isset($data['name']) && mb_strlen($data['name']) > 3) {
            // helper('path');
            // $data['name'] = toSnake($data['name']);
            if ($id = $this->model->create($data)) {
                return $this->respondCreated($id, lang('Admin.navbar.msg.msg_insert')); //
            }
        }
        return $this->fail(lang('Admin.IdNotFound'), 400);
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
        cache()->delete(self::CACHE_PREFIX . $oldData->name);
        helper('path');
        if (isset($data['data'])) {
            if (! writingDataToFile(templateFile(PATH_NAVIGATION . DIRECTORY_SEPARATOR . $oldData->name), $data['data'])) {
                return $this->fail(lang('Admin.IdNotFound'));
            }
        } else if (isset($data['name']) && mb_strlen($data['name']) > 3) {
            //$data['name'] = toSnake($data['name']);
            if (!$this->model->update($id, $data)) { return $this->fail(lang('Admin.IdNotFound')); }
            if ($oldData->name  !== $data['name']) {
                helper('files');
                renameFile(APPPATH . toPath(PATH_TEMPLATE . DIRECTORY_SEPARATOR . PATH_NAVIGATION), $oldData->name . '.php', $data['name']);
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
        $oldData = $this->model->find((int)$id, 'name');
        if (!isset($oldData) || !$oldData) { return $this->fail(lang('Admin.IdNotFound')); }
        cache()->delete(self::CACHE_PREFIX . $oldData->name);
        if (!$this->model->delete($id)) {
            return $this->failNotFound(lang('Admin.navbar.msg.msg_get_fail'));
        }
        $fileName = templateFile(PATH_NAVIGATION . DIRECTORY_SEPARATOR . $oldData->name);
        if (is_file($fileName)) { unlink($fileName); }
        return $this->respondDeleted($id, lang('Admin.navbar.msg.msg_delete'));
    }

    // Редактор данных меню JSON
    /**
     * Return the editable properties of a resource object.
     * @param int $id
     * @return array an array
     */
    private function edit(int $id = 0): ?array {
         
        if (! $row = $this->model->find((int) $id, 'name, title')) { return null; }

        helper('path');
        return [
            'dataMenu' => readingDataFromFile(templateFile(PATH_NAVIGATION . DIRECTORY_SEPARATOR . $row->name)),
            'head' => [
                'h1' => $this->genLinkHome(lang('Admin.editorTitle')),
                'icon' => 'menu-up', 
                'title' => (! $row->title ? $row->name : $row->title)
            ]
        ];
    }

    // protected function detectCurrent(bool $fullUrl = true): string
    // {
    //     $request = service('request', null, false);
    //     $root = ($fullUrl ? rtrim($request->config->baseURL, '/ ') . '/' : '/');
    //     $path = ltrim($request->detectPath($request->config->uriProtocol), '/');
    //     if ($fullUrl && $request->config->indexPage !== '') {
    //         $root .= $request->config->indexPage;
    //         if ($path !== '') { $root .= '/'; }
    //     }
    //     return (string) ($fullUrl ? new \CodeIgniter\HTTP\URI($root . $path) : $root . $path);
    // }
}
