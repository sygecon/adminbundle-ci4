<?php
namespace Sygecon\AdminBundle\Controllers\Template;

use CodeIgniter\HTTP\ResponseInterface;
use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Models\Template\ThemeModel as BaseModel;

final class Theme extends AdminController 
{

    protected $model;

    public function __construct() {
        // go on even if user "stops" the script by closing the browser, closing the terminal etc.
        ignore_user_abort(true);
        set_time_limit(0);
        $this->model = new BaseModel();
    }

    public function index(int $id = 0): ResponseInterface 
    {
        if ($this->request->getMethod() !== 'get') {
            return $this->fail(lang('Admin.IdNotFound'));
        }

        if ($id) {
            if (! $row = $this->model->find((int) $id, 'active, name, title')) {
                return $this->fail(lang('Admin.IdNotFound'));
            }
            //$path = ($row->active ? ACTIVE_THEME : $row->name);
            return $this->respond($this->build('theme_edit', [
                'id'    => (int) $id,
                'theme' => $row->name,
                'head' => [
                    'h1' => $this->genLinkHome((! $row->title ? $row->name : $row->title)),
                    'icon' => 'menu-up', 
                    'title' => lang('Admin.editTitle')
                ]
            ], 'Template'), 200);
        }

        if ($this->request->isAJAX()) {
            if ($data = $this->model->findAll('active, updated_at', false)) {
                foreach ($data as &$value) {
                    $value->{'path'} = $value->name; //($value->active ? ACTIVE_THEME : $value->name);
                }
                return $this->respond(jsonEncode($data, false), 200);
            }
            return $this->respond('[]', 200);
        }
        return $this->respond($this->build('theme',
            ['head' => ['icon' => 'nut', 'title' => lang('Admin.menu.sidebar.themesDesc')]] 
        , 'Template'), 200);
    }

    /**
     * Create a new resource object, from "posted" parameters.
     * @return array an array
     */
    public function create(int $id = 0): ResponseInterface 
    {
        $data = $this->postDataValid($this->request->getPost());
        if (isset($data['name'])) {
            //helper('path'); toCamelCase = as Name, toSnake = as Folder Name
            $name = checkFileName($data['name']);
            if (! $id && mb_strlen($name) > 2 && $name !== ACTIVE_THEME) {
                $active = false;
                if (!$data['title']) $data['title'] = $data['name'];
                $data['name'] = $name;
                if (! $this->model->getCount()) { 
                    $data['active'] = (int) 1; 
                    $active = true;
                }
                if ($id = $this->model->insert($data)) {
                    if ($active) { $this->model->setActiveConstant($name); }
                    helper('files');
                    createPath(FCPATH . castingPath(PATH_THEME, true) . DIRECTORY_SEPARATOR . $name);
                    return $this->respondCreated($id, lang('Admin.navbar.msg.msg_insert')); //
                }
            }
        }
        return $this->fail(lang('Admin.IdNotFound'));
    }

    /* Open file */
    public function open(int $id = 0): ResponseInterface 
    {
        if ($id) {
            if (! $data = $this->request->getRawInput()) { return $this->fail(lang('Admin.IdNotFound')); }
            if (isset($data['fname'])) {
                helper('path');
                return $this->respond(readingDataFromFile(FCPATH . trim(PATH_THEME, '\\/') . $data['fname']), 200);
            }
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
        $data = $this->postDataValid($this->request->getRawInput());
        if (!$data) return $this->fail(lang('Admin.IdNotFound'));
        
        if (isset($data['title'])) { // Изменение описания темы
            $oldData = $this->model->find((int) $id, 'name, title');
            if (!isset($oldData) || !$oldData) return $this->fail(lang('Admin.IdNotFound'));
            if ($data['title'] && $data['title'] !== $oldData->title) {
                if ($this->model->update($id, ['title' => $data['title']]) === false) {
                    return $this->fail(lang('Admin.IdNotFound'));
                }
            }
        } else if (isset($data['active'])) { // Тема По умолчанию
            $row = $this->model->getActive();
            if (! $this->model->setActive((int) $id)) {
                return $this->fail(lang('Admin.IdNotFound'));
            }   
            $this->minify((int) $id);
        } else if (isset($data['data']) && isset($data['fname'])) {
            helper('path');
            $fileName = castingPath(PATH_THEME, true) . DIRECTORY_SEPARATOR . trim($data['fname'], '\\/');
            $fname = FCPATH . $fileName;
            unset($data['fname']);
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (isset($data['data'][$ext])) {
                if (! writingDataToFile($fname, $data['data'][$ext])) {
                    return $this->fail(lang('Admin.IdNotFound'));   
                }
                unset($data['data'][$ext], $data['data']);
                // Автогенерация после изменений в файле, одного общего файла мини .css .js
                if ($row = $this->model->find((int) $id, 'name, active, resource')) {
                    if ($row->active) {
                        if (isset($this->model::HTML_ELEMENT[$ext])) {
                            $val = $this->model::HTML_ELEMENT[$ext]['type'];
                            $resource = jsonDecode($row->resource);
                            if (isset($resource[$val])) {
                                $resource = $resource[$val];
                                // Проверка, есть ли имя файла в списке $resource, тогда - запустить перезапись общего файла по типу
                                $fileName = strtolower(str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $this->model->cutFileName($fileName, $ext, $row->name)));
                                $go = false;  
                                foreach ($resource as $name) {
                                    if (strtolower(str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $name)) === $fileName) {
                                        $go = true;
                                        break;
                                    }
                                }
                                if ($go === true) { 
                                    $this->model->minifyFile($row->name, $ext, $resource); 
                                }
                            }
                        }
                    }
                }
            }
        } else {
            return $this->fail(lang('Admin.IdNotFound'));
        }
        return $this->respondUpdated($id, lang('Admin.navbar.msg.msg_update'));
    }

    /**
     * Delete the designated resource object from the model.
     * @param int $id
     */
    public function delete(int $id = 0, string $name = ''): ResponseInterface 
    {
        if (! $row = $this->model->find((int) $id, 'active')) { return $this->fail(lang('Admin.IdNotFound')); }
        if ($row->active) { return $this->fail(lang('Admin.IdNotFound')); }
        if (! $name) {
            if (!$this->model->delete((int) $id)) {
                return $this->failNotFound(lang('Admin.navbar.msg.msg_get_fail'));
            }
        } 
        return $this->respondDeleted($id, lang('Admin.navbar.msg.msg_delete'));
    }

    /***/
    public function topics(): ResponseInterface 
    {
        $themes = [];
        $isNames = array_column($this->model->builder()->select('name')->orderBy('updated_at', 'DESC')->get()->getResultArray(), 'name');
        foreach ($isNames as $i => $value) {
            $isNames[$i] = strtolower($value);   
        }
        $path = FCPATH . castingPath(PATH_THEME, true);
        if ($handle = opendir($path)) {
            $path .= DIRECTORY_SEPARATOR;
            while (($file = readdir($handle)) !== false) {
                if ($file !== '.' && $file !== '..' && is_dir($path . $file)) {
                    $file = strtolower($file);
                    if ($file !== ACTIVE_THEME && !in_array($file, $isNames)) {
                        $themes[] = $file;
                    }
                } 
            }
            closedir($handle);
        }
        if (! $themes) { $themes[] = ''; }
        return $this->respond(jsonEncode($themes, false), 200);
    }

    public function resource(int $id = 0, string $type = ''): ResponseInterface 
    {
        if ($id && $type) { 
            if ($this->request->getMethod() === 'get') {
                return $this->respond(
                    jsonEncode($this->model->getResource((int) $id, $type), false)
                , 200);
            }
            $data = $this->request->getRawInput();
            if ($data && is_array($data)) {
                return $this->respond($this->model->setResource((int) $id, $data), 200);
                // {"src":"\/themes\/_current\/js\/bootstrap.min.js","alt":"bootstrap.min.js","action":"insert"}
            }
        }
        return $this->respond(false, 200);
    }

    /** Combining scripts and style files into minifiles */
    public function minify(int $id = 0, string $type = ''): ResponseInterface 
    {
        if ($id) {
            if ($row = $this->model->find((int) $id, 'active, name, resource')) {
                $active = ($row->active ? true : false);
                $data = jsonDecode($row->resource);
                if ($data) {
                    if ($type) {
                        if (isset($this->model::HTML_ELEMENT[$type])) {
                            $val = $this->model::HTML_ELEMENT[$type]['type'];
                            if (array_key_exists($val, $data)) {
                                $this->model->minifyFile($row->name, $type, $data[$val], $active);
                            }
                        }
                    } else {
                        foreach ($this->model::HTML_ELEMENT as $key => &$val) {
                            if (array_key_exists($val['type'], $data)) {
                                $this->model->minifyFile($row->name, $key, $data[$val['type']], $active);
                            }
                        }
                    }
                }
            }
        }
        return $this->respondUpdated($id, lang('Admin.navbar.msg.msg_update'));
    }
}
