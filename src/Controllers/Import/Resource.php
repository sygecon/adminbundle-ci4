<?php
namespace Sygecon\AdminBundle\Controllers\Import;

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
     * @return string
     */
    public function index(): string 
    {
        if (strtolower($this->request->getMethod()) !== 'get') return $this->pageNotFound();

        if ($this->request->isAJAX()) {
            if ($data = $this->model->findAllByType($this->config->type)) {
                return $this->successfulResponse($data);
            }
            return $this->successfulResponse([]);
        }

        return $this->build($this->config->page, [
            'sheets' => $this->model->getSheets(),
            'head' => ['icon' => $this->config->icon, 'title' => lang($this->config->title)]
        ], 'Import');
    }

    public function view(int $id = 0): string 
    {
        if (strtolower($this->request->getMethod()) !== 'get') return $this->pageNotFound();
        if (! $data = $this->edit((int) $id)) return $this->pageNotFound();
        
        return $this->build('config_edit', $data, 'Import');
    }
    
    /**
     * Create a new object.
     * @return string
     */
    public function create(): string
    {
        $data = $this->postDataValid($this->request->getPost());
        if (isset($data['name']) === false) return $this->pageNotFound();
            
        $data['name'] = toSnake($data['name']);
        $data['title'] = mb_ucfirst($data['title']);
        $data['type'] = $this->config->type;

        if (! $id = $this->model->insert($data)) return $this->pageNotFound();
        return $this->successfulResponse($id);
    }

    /**
     * Update a model resource.
     * @param int $id
     * @return string
     */
    public function update(int $id = 0): string
    {
        if (! $id) return $this->pageNotFound();
        if (! $data = $this->request->getRawInput()) return $this->pageNotFound();

        if (isset($data['json'])) { 
            if (! $row = $this->model->find((int) $id, 'name')) { 
                return $this->pageNotFound();
            }
            $this->model->writeToFile($row->name, $data['json']);
            return $this->successfulResponse($id);
        }
        if (! isset($data['title'])) return $this->pageNotFound();
        
        if (isset($data['name'])) { unset($data['name']); }
        if ($this->model->update((int) $id, $data) === false) return $this->pageNotFound();
        return $this->successfulResponse($id);
    }

    /**
     * Delete the object from the model.
     * @param int $id
     * @return string
     */
    public function delete(int $id = 0): string 
    {
        if ($id && $this->model->remove((int) $id) === true) {
            return $this->successfulResponse($id);
        }
        return $this->pageNotFound();
    }

    // Генерируем стартовый шаблон для импорта
    public function template(int $id = 0): string
    {
        if (! $id) return $this->pageNotFound();
        if (! $tmplName = $this->model->getTemplateName((int) $id)) { 
            return $this->pageNotFound();
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
        if ($this->config->type === 'resource') { return '[]'; }

        // Текстовые данные
        $sheetModel = new SheetForge($tmplName);
        $result['fields'] = jsonDecode($sheetModel->getFrame());
        if (! isset($result['fields']['fields'])) { return '[]'; }

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
        return $this->successfulResponse($result, true);
    }

    /**
     * Редактор данных PHP 
     * Return the editable properties of a resource object.
     * @param int $id
     * @return array an array
     */
    private function edit(int $id): ?array 
    {
        if (! $id) return null;
        if (! $row = $this->model->find($id, 'name, title')) return null;
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
