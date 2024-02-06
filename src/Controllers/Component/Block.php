<?php

namespace Sygecon\AdminBundle\Controllers\Component;

use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Models\Component\BlockModel as BaseModel;
use Sygecon\AdminBundle\Models\Component\SheetModel;

final class Block extends AdminController {

    protected $model;

    public function __construct() {
        $this->model = new BaseModel();
    }

    public function index(int $id = 0) {
        if ($this->request->getMethod() !== 'get') {
            return $this->fail(lang('Admin.IdNotFound'));
        }
        if ($id) {
            return $this->respond($this->build('block_edit', $this->edit($id), 'Component'), 200);
        }

        if ($this->request->isAJAX()) {
            if ($data = $this->model->findAll('updated_at', false)) {
                return $this->respond(jsonEncode($data, false), 200);
            }
            return $this->respond('[]', 200);
        }
        
        return $this->respond($this->build('block', 
            ['head' => ['icon' => 'grid', 'title' => lang('Admin.menu.sidebar.blocksDesc')]]
        , 'Component'), 200);
    }
    /**
     * Create a new resource object, from "posted" parameters.
     * @return array an array
     */
    public function create() {
        $dataPost = $this->postDataValid($this->request->getPost());
        if (isset($dataPost['name'])) {
            if (!$id = $this->model->insert($dataPost)) {
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
    public function update(int $id = 0) {
        if (!$id) return $this->fail(lang('Admin.IdNotFound'), 400);
        $data = $this->request->getRawInput();
        if (!$data) return $this->fail(lang('Admin.IdNotFound'));
        if (isset($data['is_select'])) {
            unset($data['is_select']);
            if (isset($data['sheet_name']) || isset($data['type'])) {
                if (!$this->model->update($id, $data)) {
                    return $this->fail(lang('Admin.IdNotFound'));
                }
            }
        } else {
            $oldData = $this->model->find((int)$id, 'name');
            if (!isset($oldData) || !$oldData) {
                return $this->fail(lang('Admin.IdNotFound'));
            }
            if (isset($data['data_html_control'])) { //! Сохраниение формы редактирования данных страницы
                if (!$this->model->update($id, ['direct_tmpl' => htmlspecialchars(trim($data['data_html_control']))])) {
                    return $this->fail(lang('Admin.IdNotFound'));
                }
                unset($data['data_html_control']);
            } else if (isset($data['data_html'])) { //! Сохраниение формы редактирования данных страницы
                helper('path');
                if (writingDataToFile(templateFile(PATH_BLOCK . DIRECTORY_SEPARATOR . $oldData->name), $data['data_html']) === false) {
                    return $this->fail(lang('Admin.IdNotFound'));
                }
                unset($data['data_html']);
            } else {
                $data = $this->postDataValid($data);
                if (!isset($data['name'])) {
                    return $this->fail(lang('Admin.IdNotFound'));
                }
                if (!$this->model->update($id, $data)) {
                    return $this->fail(lang('Admin.IdNotFound'));
                }
                if ($oldData->name !== $data['name']) {
                    helper('files');
                    renameFile(APPPATH . toPath(PATH_TEMPLATE . DIRECTORY_SEPARATOR . PATH_BLOCK), $oldData->name . '.php', $data['name']);
                }
            }
        }
        return $this->respondUpdated($id, lang('Admin.navbar.msg.msg_update'));
    }
    /**
     * Delete the designated resource object from the model.
     * @param int $id
     */
    public function delete(int $id = 0) {
        if (!$id) return $this->fail(lang('Admin.IdNotFound'), 400);
        if (!$this->model->delete($id)) {
            return $this->failNotFound(lang('Admin.navbar.msg.msg_get_fail'));
        }
        return $this->respondDeleted($id, lang('Admin.navbar.msg.msg_delete'));
    }
    
    // Редактор данных PHP 
    /**
     * Return the editable properties of a resource object.
     * @param int $id
     * @return array an array
     */
    private function edit(int $id = 0): ?array {
        
        if (! $row = $this->model->find((int) $id)) { return null; }
        helper('path');
        $sheetList = new SheetModel();

        return [
            'id' => (int) $id,
            'sheet_id' => (int) $row->{'sheet_name'},
            'type' => (string) $row->type,
            'typeList' => [['name' => 'page', 'title' => lang('Admin.page')], ['name' => 'list', 'title' => lang('Admin.list')]],
            'theme' => getPathTheme('-'),
            'HTML' => readingDataFromFile(templateFile(PATH_BLOCK . DIRECTORY_SEPARATOR . $row->name)),
            'HTMLControl' => htmlspecialchars_decode($row->direct_tmpl),
            'sheetList' => $sheetList->findAll(),
            'head' => [
                'h1' => $this->genLinkHome((! $row->title ? $row->name : $row->title)),
                'icon' => 'menu-up',
                'iconHtml' => 'file-earmark-text',
                'title' => lang('Admin.editorTitle') . lang('HeadLines.catalog.frm.formPageBlockData'),
                'titleHtml' => lang('Admin.editorTitle') . lang('HeadLines.catalog.frm.formPageBlockTemplate')
            ]
        ];
    }
}
