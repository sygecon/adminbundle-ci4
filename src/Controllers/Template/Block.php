<?php
namespace Sygecon\AdminBundle\Controllers\Template;

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

    /**
     * @return string
     */
    public function index(int $id = 0): string 
    {
        if (strtolower($this->request->getMethod()) !== 'get') return $this->pageNotFound();

        if ($id) return $this->build('block_edit', $this->edit($id), 'Template');
        
        if ($this->request->isAJAX()) {
            if ($data = $this->model->findAll('updated_at', false)) {
                return $this->successfulResponse($data);
            }
            return $this->successfulResponse([]);
        }

        return $this->build('block', [
                'typeList' => self::TYPE_LIST,
                'head' => ['icon' => 'grid', 'title' => lang('Admin.menu.sidebar.blocksDesc')]
            ]
        , 'Template');
    }
    
    /**
     * Create a new object.
     * @return string
     */
    public function create(): string
    {
        $data = $this->postDataValid($this->request->getPost(), 64);
        if (! isset($data['name'])) return $this->pageNotFound();
        helper('path');

        $data['name'] = toSnake($data['name']);
        if (! $data['title']) { $data['title'] = $data['name']; }
        $data['title'] = mb_ucfirst($data['title']);
        $data['type'] = strtolower(trim(strip_tags($data['type'])));

        if (! in_array($data['type'], self::TYPE_LIST)) {
            $data['type'] = strtolower(self::TYPE_LIST[0]);
        }
        if (! $id = $this->model->insert($data)) return $this->pageNotFound();

        return $this->successfulResponse($id);
    }

    /**
     * Update a object.
     * @param int $id
     * @return string
     */
    public function update(int $id = 0): string 
    {
        if (! $id) return $this->pageNotFound();
        $data = $this->request->getRawInput();
        if (! $data) return $this->pageNotFound();
        $oldData = $this->model->find((int)$id, 'name, type');
        if (! isset($oldData) || !$oldData) return $this->pageNotFound();

        helper('path');
        if (isset($data['content'])) { //! Сохраниение формы редактирования данных страницы
            if (writingDataToFile(templateFile(PATH_BLOCK . DIRECTORY_SEPARATOR . $oldData->name), $data['content']) === false) {
                return $this->pageNotFound();
            }
            unset($data['content']);
        } else 
        if (isset($data['type'])) {
            $type = strtolower(trim($data['type']));
            if (! in_array($type, self::TYPE_LIST)) {
                return $this->pageNotFound();
            }
            if ($oldData->type !== $type) {
                if (!$this->model->update($id, ['type' => $type])) {
                    return $this->pageNotFound();
                }
            }
        } else {
            $data = $this->postDataValid($data, 64);
            if (!isset($data['name'])) {
                return $this->pageNotFound();
            }
            helper('files');
            $data['name'] = toSnake($data['name']);
            $data['title'] = mb_ucfirst($data['title']);
            if (! $this->model->update($id, $data)) return $this->pageNotFound();
            
            if ($oldData->name !== $data['name']) {
                renameFile(APPPATH . toPath(PATH_TEMPLATE . DIRECTORY_SEPARATOR . PATH_BLOCK), $oldData->name . '.php', $data['name']);
            }
        }
        return $this->successfulResponse($id);
    }

    /**
     * Delete object from the model.
     * @param int $id
     * @return string
     */
    public function delete(int $id = 0): string 
    {
        if ($id && $this->model->delete($id)) {
            return $this->successfulResponse($id);
        }
        return $this->pageNotFound();
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
    public function getCatalog(int $id = 0): string 
    {
        $data = [];
        if ($this->request->isAJAX()) {
            $data = CatVar::dataForHtmlEditor($this->locale);
        }
        return $this->successfulResponse($data);
    }
}
