<?php
namespace Sygecon\AdminBundle\Controllers\Import;

use CodeIgniter\HTTP\ResponseInterface;
use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Models\ImportModel as BaseModel;
use Sygecon\AdminBundle\Libraries\Table\SheetForge;

class Resource extends AdminController 
{
    protected $config = [
        'type'  => 'resource',
        'page'  => 'resources',
        'icon'  => 'file-earmark-richtext',
        'title' => 'Admin.menu.sidebar.importResourcesDesc'
    ];

    protected $model;

    public function __construct() {
        helper('path');

        $this->config = (object) $this->config;
        $this->model = new BaseModel();
    }

     /**
     * @return ResponseInterface
     */
    public function index(int $id = 0): ResponseInterface 
    {
        if ($this->request->getMethod() !== 'get') {
            return $this->fail(lang('Admin.IdNotFound'));
        }
        if ($id) {
            return $this->respond($this->build('config_edit', $this->edit((int) $id), 'Import'), 200);
        }

        if ($this->request->isAJAX()) {
            if ($data = $this->model->findAllByType($this->config->type)) {
                return $this->respond(jsonEncode($data, false), 200);
            }
            return $this->respond('[]', 200);
        }

        return $this->respond($this->build($this->config->page, [
            'sheets' => $this->model->getSheets(),
            'head' => ['icon' => $this->config->icon, 'title' => lang($this->config->title)]
        ], 'Import'), 200);
    }
    
    /**
     * Create a new resource object, from "posted" parameters.
     * @return ResponseInterface
     */
    public function create(): ResponseInterface 
    {
        $data = $this->postDataValid($this->request->getPost());
        if (isset($data['name']) === false) { return $this->fail(lang('Admin.IdNotFound')); }
            
        $data['name'] = toSnake($data['name']);
        $data['title'] = mb_ucfirst($data['title']);
        $data['type'] = $this->config->type;
        if (! $id = $this->model->insert($data)) {
            return $this->fail(lang('Admin.IdNotFound'));
        }

        return $this->respondCreated($id, lang('Admin.navbar.msg.msg_insert')); //
    }

    /**
     * Add or update a model resource, from "posted" properties.
     * @param int $id
     * @return ResponseInterface
     */
    public function update(int $id = 0): ResponseInterface 
    {
        if (! $id) return $this->fail(lang('Admin.IdNotFound'));
        if (! $data = $this->request->getRawInput()) { return $this->fail(lang('Admin.IdNotFound')); }
        if (isset($data['json'])) { 
            if (! $row = $this->model->find((int) $id, 'name')) { 
                return $this->fail(lang('Admin.IdNotFound'));
            }
            $this->model->writeToFile($row->name, $data['json']);
            return $this->respondUpdated($id, lang('Admin.navbar.msg.msg_update'));
        }

        if (! isset($data['title'])) { return $this->fail(lang('Admin.IdNotFound')); }
        if (isset($data['name'])) { unset($data['name']); }
        if ($this->model->update((int) $id, $data) === false) { 
            return $this->fail(lang('Admin.IdNotFound'));
        }
        return $this->respondUpdated($id, lang('Admin.navbar.msg.msg_update'));
    }

    /**
     * Delete the designated resource object from the model.
     * @param int $id
     * @return ResponseInterface
     */
    public function delete(int $id = 0): ResponseInterface 
    {
        if (! $id) { return $this->fail(lang('Admin.IdNotFound')); }

        if ($this->model->remove((int) $id) === false) {
            return $this->failNotFound(lang('Admin.navbar.msg.msg_get_fail'));
        }
        return $this->respondDeleted($id, lang('Admin.navbar.msg.msg_delete'));
    }

    // Генерируем стартовый шаблон для импорта
    public function template(int $id = 0): ResponseInterface 
    {
        if (! $id) { return $this->fail(lang('Admin.IdNotFound')); }
        if (! $tmplName = $this->model->getTemplateName((int) $id)) { 
            return $this->fail(lang('Admin.IdNotFound'));
        }

        $result = [
            'locale'  => APP_DEFAULT_LOCALE,
            'localRootLink'  => '',
            'linkToSource' => '',
            'sitemapSourceURL' => '',
            'modelName' => $tmplName,
            'findChildsMode' => false,
            'fields' => []
        ];

        // Ресурсы
        if ($this->config->type === 'resource') { return $this->respond('[]', 200); }

        // Текстовые данные
        $sheetModel = new SheetForge($tmplName);
        $result['fields'] = jsonDecode($sheetModel->getFrame());
        if (! isset($result['fields']['fields'])) { return $this->respond('[]', 200); }

        $result['fields'] = $result['fields']['fields'];
        $fields = &$result['fields'];
        foreach ($fields as $key => &$value) {
            $dataType = 'text';
            foreach ($value as $name => &$val) {
                $str = trim($val, "{} ");
                if ($name === 'label' || $name === 'title') { 
                    $val = lang(substr($str, 5));
                    continue; 
                }

                $str = strtolower($str);
                if (strtolower($name) === 'formtype' && $str !== 'hidden' && $str !== 'ishidden') { 
                    if (strpos($str, 'tinymce')) {
                        $dataType = 'html'; 
                    } else 
                    if (substr($str, 0, 4) !== 'text') { 
                        $dataType = $val; 
                    } 
                }
                
                unset($value[$name]);
            }
            
            if (isset($value['label'])) {
                if (! isset($value['title']) || ! $value['title']) {
                    $value['title'] = $value['label'];
                }
                unset($value['label']);
            }
            $value['dataType']  = $dataType;
            $value['xPath']     = '';
            $value['callback']  = '';
            $value['params']    = '';
        }

        $fields['description'] = [
            'title'     => 'Page title', 
            'dataType'  => 'text',
            'callback'  => 'h1',
            'params'    => ''
        ];

        $fields['icon'] = [
            'title'     => 'Page icon', 
            'dataType'  => 'image',
            'callback'  => 'Attribute',
            'params'    => 'img'
        ];

        if (isset($fields['summary']) === false) {
            $fields['summary'] = [
                'title'     => lang('HeadLines.announce'), 
                'dataType'  => 'html', 
                'xPath'     => '',
                'callback'  => '',
                'params'    => ''
            ];
        }

        $fields['meta_title'] = [
            'title'     => 'Meta TITLE', 
            'dataType'  => 'text',
            'params'    => 'title',
            'callback'  => 'meta'
        ];

        $fields['meta_keywords'] = [
            'title'     => 'Meta KEYWORDS', 
            'dataType'  => 'text', 
            'params'    => 'keywords',
            'callback'  => 'meta'
        ];

        $fields['meta_description'] = [
            'title'     => 'Meta DESCRIPTIONS', 
            'dataType'  => 'text', 
            'params'    => 'description',
            'callback'  => 'meta'
        ];
        
        $fields['updated_at'] = [
            'title'     => 'Date update', 
            'dataType'  => 'date', 
            'callback'  => 'sitemap'
        ];

        return $this->respond(jsonEncode($result, false), 200);
    }

    /**
     * Редактор данных PHP 
     * Return the editable properties of a resource object.
     * @param int $id
     * @return array an array
     */
    private function edit(int $id): ?array 
    {
        if (! $row = $this->model->find($id, 'name, title')) { return null; }
        
        return [
            'id' => (int) $id,
            'data' => esc($this->model->readFromFile($row->name)),
            'head' => [
                'h1' => $this->genLinkHome((! $row->title ? $row->name : $row->title)),
                'icon' => $this->config->icon,
                'title' => lang('Admin.editorTitle') . ' - [ ' . $row->name . ' ]',
            ]
        ];
    }
}
