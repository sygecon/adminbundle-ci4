<?php
namespace Sygecon\AdminBundle\Controllers\Navigation;

use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Models\Navigation\MenuModel as BaseModel;

final class Menu extends AdminController 
{

    protected const CACHE_PREFIX = 'Admin_apiNavAdmin_';

    protected $model;

    public function __construct() {
        $this->model = new BaseModel();
    }

    public function index(int $id = 0): string 
    {
        if (strtolower($this->request->getMethod()) !== 'get') return $this->pageNotFound();
        
        if ($id) return $this->build('menu_edit', $this->edit($id), 'Navigation');
        
        if ($this->request->isAJAX()) {
            if ($data = $this->model->findAll('updated_at', false)) {
                return $this->successfulResponse($data);
            }
            return $this->successfulResponse([]);
        }
        // $root    = (new \CodeIgniter\HTTP\URI(site_url()))->getPath() ?: '/';
        // $this->detectCurrent(false)
        return $this->build('menu', ['head' => [
            'icon' => 'menu-button-fill', 
            'title' => lang('Admin.menu.sidebar.navMenuDesc')
        ]], 'Navigation');
    }

    /**
     * Create a new object.
     * @return string
     */
    public function create(): string 
    {
        $data = $this->postDataValid($this->request->getPost(), 32, 128);
        if (isset($data['name']) && mb_strlen($data['name']) > 3) {
            // helper('path');
            // $data['name'] = toSnake($data['name']);
            if ($id = $this->model->create($data)) {
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
        if (!isset($oldData) || !$oldData) return $this->pageNotFound();

        cache()->delete(self::CACHE_PREFIX . $oldData->name);
        helper('path');
        if (isset($data['data'])) {
            if (! writingDataToFile(templateFile(PATH_NAVIGATION . DIRECTORY_SEPARATOR . $oldData->name), $data['data'])) {
                return $this->pageNotFound();
            }
        } else 
        if (isset($data['name']) && mb_strlen($data['name']) > 3) {
            //$data['name'] = toSnake($data['name']);
            if (! $this->model->update($id, $data)) return $this->pageNotFound();
            if ($oldData->name  !== $data['name']) {
                helper('files');
                renameFile(APPPATH . toPath(PATH_TEMPLATE . DIRECTORY_SEPARATOR . PATH_NAVIGATION), $oldData->name . '.php', $data['name']);
            }
        }
        return $this->successfulResponse($id);
    }

    /**
     * Delete the designated resource object from the model.
     * @param int $id
     * @return string
     */
    public function delete(int $id = 0): string 
    {
        $oldData = $this->model->find((int)$id, 'name');
        if (!isset($oldData) || !$oldData) return $this->pageNotFound();
        cache()->delete(self::CACHE_PREFIX . $oldData->name);
        if (! $this->model->delete($id)) return $this->pageNotFound();
        
        $fileName = templateFile(PATH_NAVIGATION . DIRECTORY_SEPARATOR . $oldData->name);
        if (is_file($fileName)) { unlink($fileName); }
        return $this->successfulResponse($id);
    }

    // Редактор данных меню JSON
    /**
     * Return the editable properties of a resource object.
     * @param int $id
     * @return array an array
     */
    private function edit(int $id = 0): ?array 
    {
        if (! $row = $this->model->find((int) $id, 'name, title')) return null;
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
