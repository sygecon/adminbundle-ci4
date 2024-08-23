<?php
namespace Sygecon\AdminBundle\Controllers\Template;

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

    public function index(int $id = 0): string 
    {
        if (strtolower($this->request->getMethod()) !== 'get') return $this->pageNotFound();
        
        if ($id) {
            if (! $row = $this->model->find((int) $id, 'active, name, title')) {
                return $this->pageNotFound();
            }
            //$path = ($row->active ? ACTIVE_THEME : $row->name);
            return $this->build('theme_edit', [
                'id'    => (int) $id,
                'theme' => $row->name,
                'head'  => [
                    'h1' => $this->genLinkHome((! $row->title ? $row->name : $row->title)),
                    'icon' => 'menu-up', 
                    'title' => lang('Admin.editTitle')
                ]
            ], 'Template');
        }

        if ($this->request->isAJAX()) {
            if ($data = $this->model->findAll('active, updated_at', false)) {
                foreach ($data as &$value) {
                    $value->{'path'} = $value->name; //($value->active ? ACTIVE_THEME : $value->name);
                }
                return $this->successfulResponse($data);
            }
            return $this->successfulResponse([]);
        }

        return $this->build('theme', ['head' => [
            'icon' => 'nut', 
            'title' => lang('Admin.menu.sidebar.themesDesc')
        ]], 'Template');
    }

    /**
     * Create a new object.
     * @return string
     */
    public function create(int $id = 0): string 
    {
        $data = $this->postDataValid($this->request->getPost());
        if (! isset($data['name'])) return $this->pageNotFound();

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
                return $this->successfulResponse($id);
            }
        }
        return $this->pageNotFound();
    }

    /* Open file */
    public function open(int $id = 0): string 
    {
        if ($id) {
            if (! $data = $this->request->getRawInput()) return $this->pageNotFound();
            if (isset($data['fname']) === true && $data['fname']) {
                helper('path');
                return $this->successfulResponse(
                    readingDataFromFile(FCPATH . trim(PATH_THEME, '\\/') . $data['fname'])
                );
            }
        }
        return $this->pageNotFound();
    }

    /**
     * Updating object data.
     * @param int $id
     * @return string
     */
    public function update(int $id = 0): string 
    {
        if (!$id) return $this->pageNotFound();
        $data = $this->postDataValid($this->request->getRawInput());
        if (!$data) return $this->pageNotFound();
        
        if (isset($data['title'])) { // Изменение описания темы
            $oldData = $this->model->find((int) $id, 'name, title');
            if (!isset($oldData) || !$oldData) return $this->pageNotFound();
            if ($data['title'] && $data['title'] !== $oldData->title) {
                if ($this->model->update($id, ['title' => $data['title']]) === false) {
                    return $this->pageNotFound();
                }
            }
        } else 
        if (isset($data['active'])) { // Тема По умолчанию
            $row = $this->model->getActive();
            if (! $this->model->setActive((int) $id)) {
                return $this->pageNotFound();
            }   
            $this->minify((int) $id);
        } else 
        if (isset($data['data']) && isset($data['fname'])) {
            helper('path');
            $fileName = castingPath(PATH_THEME, true) . DIRECTORY_SEPARATOR . trim($data['fname'], '\\/');
            $fname = FCPATH . $fileName;
            unset($data['fname']);
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (isset($data['data'][$ext])) {
                if (! writingDataToFile($fname, $data['data'][$ext])) {
                    return $this->pageNotFound();  
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
            $this->pageNotFound();
        }
        return $this->successfulResponse($id);
    }

    /**
     * Delete a object from the model.
     */
    public function delete(int $id = 0, string $name = ''): string 
    {
        if (! $id) return $this->pageNotFound();
        if (! $name) return $this->pageNotFound();
        if (! $row = $this->model->find((int) $id, 'active')) return $this->pageNotFound(); 
        if ($row->active) return $this->pageNotFound();

        if (! $this->model->delete((int) $id)) return $this->pageNotFound();
        return $this->successfulResponse($id);
    }

    /***/
    public function topics(): string 
    {
        $themes = [];
        $isNames = array_column(
            $this->model->builder()->select('name')->orderBy('updated_at', 'DESC')->get()->getResultArray()
        , 'name');

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
        return $this->successfulResponse($themes, true);
    }

    /*
    * @return bool|string
     */
    public function resource(int $id = 0, string $type = '') 
    {
        if ($id && $type) { 
            if (strtolower($this->request->getMethod()) === 'get') {
                return $this->successfulResponse(
                    $this->model->getResource((int) $id, $type)
                , true);
            }

            $data = $this->request->getRawInput();
            if ($data && is_array($data)) {
                return $this->successfulResponse(
                    $this->model->setResource((int) $id, $data)
                );
                // {"src":"\/themes\/_current\/js\/bootstrap.min.js","alt":"bootstrap.min.js","action":"insert"}
            }
        }
        return $this->successfulResponse(false);
    }

    /** Combining scripts and style files into minifiles */
    public function minify(int $id = 0, string $type = ''): int 
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
        return $this->successfulResponse($id);
    }
}
