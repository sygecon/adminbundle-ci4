<?php
namespace Sygecon\AdminBundle\Controllers\Template;

use CodeIgniter\HTTP\ResponseInterface;
use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Config\Catalog as CatVar;
use Sygecon\AdminBundle\Models\Template\BlockModel as BaseModel;


final class Block extends AdminController 
{

    protected const TYPE_LIST = ['php', 'html'];

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
            $path = 'block_edit';
            $data = $this->edit($id);
        } else {
            if ($this->request->isAJAX()) {
                if ($data = $this->model->findAll('updated_at', false)) {
                    return $this->respond(jsonEncode($data, false), 200);
                }
                return $this->respond('[]', 200);
            }
            $path = 'block';
            $data = [
                'typeList' => self::TYPE_LIST,
                'head' => ['icon' => 'grid', 'title' => lang('Admin.menu.sidebar.blocksDesc')]
            ];
        }
        return $this->respond($this->build($path, $data, 'Template'), 200);
    }
    
    /**
     * Create a new resource object, from "posted" parameters.
     * @return array an array
     */
    public function create(): ResponseInterface 
    {
        $data = $this->postDataValid($this->request->getPost(), 64);
        if (isset($data['name'])) {
            helper('path');
            $data['name'] = toSnake($data['name']);
            if (! $data['title']) { $data['title'] = $data['name']; }
            $data['title'] = mb_ucfirst($data['title']);
            $data['type'] = strtolower(trim(strip_tags($data['type'])));
            if (! in_array($data['type'], self::TYPE_LIST)) {
                $data['type'] = strtolower(self::TYPE_LIST[0]);
            }
            if (!$id = $this->model->insert($data)) {
                return $this->fail($this->model->errors());
            }

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
        $data = $this->request->getRawInput();
        if (!$data) return $this->fail(lang('Admin.IdNotFound'));
        $oldData = $this->model->find((int)$id, 'name, type');
        if (!isset($oldData) || !$oldData) {
            return $this->fail(lang('Admin.IdNotFound'));
        }
        helper('path');
        if (isset($data['content'])) { //! Сохраниение формы редактирования данных страницы
            if (writingDataToFile(templateFile(PATH_BLOCK . DIRECTORY_SEPARATOR . $oldData->name), $data['content']) === false) {
                return $this->fail(lang('Admin.IdNotFound'));
            }
            unset($data['content']);
        } else if (isset($data['type'])) {
            $type = strtolower(trim($data['type']));
            if (! in_array($type, self::TYPE_LIST)) {
                return $this->fail(lang('Admin.IdNotFound'));
            }
            if ($oldData->type !== $type) {
                if (!$this->model->update($id, ['type' => $type])) {
                    return $this->fail(lang('Admin.IdNotFound'));
                }
            }
        } else {
            $data = $this->postDataValid($data, 64);
            if (!isset($data['name'])) {
                return $this->fail(lang('Admin.IdNotFound'));
            }
            helper('files');
            $data['name'] = toSnake($data['name']);
            $data['title'] = mb_ucfirst($data['title']);
            if (!$this->model->update($id, $data)) {
                return $this->fail(lang('Admin.IdNotFound'));
            }
            if ($oldData->name !== $data['name']) {
                renameFile(APPPATH . toPath(PATH_TEMPLATE . DIRECTORY_SEPARATOR . PATH_BLOCK), $oldData->name . '.php', $data['name']);
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
        if (!$id) return $this->fail(lang('Admin.IdNotFound'), 400);
        if (!$this->model->delete($id)) {
            return $this->failNotFound(lang('Admin.navbar.msg.msg_get_fail'));
        }
        return $this->respondDeleted($id, lang('Admin.navbar.msg.msg_delete'));
    }

    /**
     * Return the editable properties of a resource object.
     * @param int $id
     * @return array an array
     */
    private function edit(int $id = 0): ?array 
    {
        // Редактор данных PHP 
        if (! $row = $this->model->find((int) $id)) { return null; }
        helper('path');

        return [
            'id' => (int) $id,
            'type' => $row->type,
            'typeList' => self::TYPE_LIST,
            'data' => esc(readingDataFromFile(templateFile(PATH_BLOCK . DIRECTORY_SEPARATOR . $row->name))),
            'head' => [
                'h1' => $this->genLinkHome((! $row->title ? $row->name : $row->title)),
                'icon' => 'menu-up',
                'iconHtml' => 'file-earmark-text',
                'title' => lang('Admin.editorTitle') . ' - [ ' . $row->name . ' ]',
                'titleHtml' => lang('Admin.editorHTML') . ' - [ ' . $row->name . ' ]'
            ]
        ];
    }

    //Tree Catalog TinyMCE
    public function getCatalog(int $id = 0): ResponseInterface 
    {
        $data = [];
        if ($id && $this->request->isAJAX()) {
            $data = CatVar::dataForHtmlEditor($this->locale);
        }
        return $this->respond(jsonEncode($data, false), 200);
    }
}
