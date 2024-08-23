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

    public function index(int $id = 0): string 
    {
        if (strtolower($this->request->getMethod()) !== 'get') {
            return $this->pageNotFound();
        }
        if ($id) {
            return $this->build('block_edit', $this->edit($id), 'Component');
        }

        if ($this->request->isAJAX()) {
            if ($data = $this->model->findAll('updated_at', false)) {
                return $this->successfulResponse($data);
            }
            return $this->successfulResponse([]);
        }
        
        return $this->build('block', 
            ['head' => ['icon' => 'grid', 'title' => lang('Admin.menu.sidebar.blocksDesc')]]
        , 'Component');
    }
    /**
     * Create a new resource object, from "posted" parameters.
     * @return array an array
     */
    public function create(): string
    {
        $dataPost = $this->postDataValid($this->request->getPost());
        if (isset($dataPost['name']) && $dataPost['name']) {
            if ($id = $this->model->insert($dataPost)) return $this->successfulResponse($id);
        }
        return $this->pageNotFound();
    }

    /**
     * Add or update a model resource, from "posted" properties.
     * @param int $id
     * @return array an array
     */
    public function update(int $id = 0): string
    {
        if (! $id) return $this->pageNotFound();
        $data = $this->request->getRawInput();
        if (! $data) return $this->pageNotFound();
        if (isset($data['is_select'])) {
            unset($data['is_select']);
            if (isset($data['sheet_name']) || isset($data['type'])) {
                if (! $this->model->update($id, $data)) {
                    return $this->pageNotFound();
                }
            }
        } else {
            $oldData = $this->model->find((int)$id, 'name');
            if (!isset($oldData) || !$oldData) {
                return $this->pageNotFound();
            }
            if (isset($data['data_html_control'])) { //! Сохраниение формы редактирования данных страницы
                if (!$this->model->update($id, [
                    'direct_tmpl' => htmlspecialchars(trim($data['data_html_control']))
                ])) {
                    return $this->pageNotFound();
                }
                unset($data['data_html_control']);
            } else 
            if (isset($data['data_html'])) { //! Сохраниение формы редактирования данных страницы
                helper('path');
                if (writingDataToFile(
                    templateFile(PATH_BLOCK . DIRECTORY_SEPARATOR . $oldData->name), $data['data_html']
                ) === false) {
                    return $this->pageNotFound();
                }
                unset($data['data_html']);
            } else {
                $data = $this->postDataValid($data);
                if (! isset($data['name'])) return $this->pageNotFound();
                if (! $this->model->update($id, $data)) return $this->pageNotFound();
                if ($oldData->name !== $data['name']) {
                    helper('files');
                    renameFile(APPPATH . toPath(PATH_TEMPLATE . DIRECTORY_SEPARATOR . PATH_BLOCK), $oldData->name . '.php', $data['name']);
                }
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
        if ($id && $this->model->delete($id)) {
            return $this->successfulResponse($id);
        }
        return $this->pageNotFound();
    }
    
    // Редактор данных PHP 
    /**
     * Return the editable properties of a resource object.
     * @param int $id
     * @return array an array
     */
    private function edit(int $id = 0): ?array 
    {
        
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
