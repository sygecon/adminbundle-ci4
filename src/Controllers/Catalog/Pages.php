<?php
namespace Sygecon\AdminBundle\Controllers\Catalog;

use CodeIgniter\HTTP\ResponseInterface;
use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Models\Template\LayoutModel;
use Sygecon\AdminBundle\Models\Catalog\PagesModel as BaseModel;

final class Pages extends AdminController 
{
    protected $model;
    protected $lang = APP_DEFAULT_LOCALE;

    public function __construct() {
        // go on even if user "stops" the script by closing the browser, closing the terminal etc.
        ignore_user_abort(true);
        set_time_limit(0);
        $this->model = new BaseModel();
        $this->lang = $this->locale;
    }

    public function index($lang = APP_DEFAULT_LOCALE, $parentId = 0, $parent = 0): ResponseInterface
    {
        if ($this->request->getMethod() !== 'get') {
            return $this->fail(lang('Admin.IdNotFound'));
        }

        if (isset($lang)) {
            if (! is_null($lang) && is_numeric($lang)) {
                $parentId = (int) $lang;
            } else {
                $this->setLanguage($lang);
            }
        }
        $parent = (isset($parent) ? (int) $parent : (int) 0);
        if (! $parent && isset($parentId) && $parentId) { $parent = (int) $parentId; }
        
        if ($this->request->isAJAX()) {
            return $this->respond(
                jsonEncode($this->model->getPages((int) $parent, $this->lang), false)
            , 200);
        }

        $layoutModel = new LayoutModel();
        $layoutModel->JsonEncode = false;
        $data = [
            'head' => [
                'icon'  => 'wallet', 'title' => lang('Admin.menu.sidebar.catalogName'),
                'h1'    => lang('Admin.menu.sidebar.catalogName')
            ],
            'lang_name' => $this->lang,
            'link_page' => $this->model::LINK_PAGE . '/page/' . $this->lang . '/',
            'layouts'   => $layoutModel->getBuilder()
        ];

        if ($parent != 0) {
            $data['head']['breadcrumb'] = $this->model->getBreadCrumb((int) $parent, $this->lang);
        }
        return $this->respond($this->build('pages', $data, 'Catalog'), 200);
    }

    /**
     * Create a new resource object, from "posted" parameters.
     * @return array an array
     */
    public function create(string $lang = APP_DEFAULT_LOCALE, int $parentId = 0): ResponseInterface 
    {
        // helper('path');
        // baseWriteFile('log.txt', $parentId . ' = ' . $lang);

        if (! $data = $this->postDataValid($this->request->getPost(), 128)) { return $this->fail(lang('HeadLines.catalog.msgErrorCreate')); }
        $this->clearing($data);
        $data['name'] = $this->checkName($data['name']);
        if (! isset($data['name']) || $data['name'] === '') { $data['name'] = 'index'; }
        if (strlen($data['name']) < 3) { return $this->fail(lang('HeadLines.catalog.msgErrorCreate')); }
        $this->setLanguage($lang);
        $this->normalize($data);
        $first = (isset($data['is_first']) ? true : false);
        if (isset($data['parent_id'])) {
            $data['parent'] = (int) $data['parent_id'];
            unset($data['parent_id']);
        }
        if (! isset($data['parent'])) { $data['parent'] = (int) $parentId; }
        
        if ($id = $this->model->create($data, $this->lang, $first)) {
            return $this->respondCreated($id, lang('HeadLines.catalog.msgCreate'));
        }
        return $this->fail(lang('HeadLines.catalog.msgErrorCreate'));
    }

    public function parent(int $id = 0): ResponseInterface 
    {
        if (! $id) { return $this->failNotFound(lang('Admin.navbar.msg.msg_get_fail')); }
        return $this->respond((int) $this->model->getParentNode($id), 200);
    }

    /**
     * Return the editable properties of a resource object.
     * @param int $id
     * @return array an array
     */
    public function edit(string $lang = APP_DEFAULT_LOCALE, int $id = 0): ResponseInterface 
    {
        if (! $id) { return $this->fail(lang('HeadLines.catalog.msgErrorCreate')); }
        if (! $data = $this->model->getNode((int) $id)) { return $this->fail(lang('Admin.IdNotFound')); }
        $this->setLanguage($lang);
        $this->model->setLinkPages($data, $this->lang);
        return $this->respond(
            jsonEncode(['data' => $data], false)
        , 200);
    }

    /**
     * Add or update a model resource, from "posted" properties.
     * @param int $id
     * @return array an array
     */
    public function update(string $lang = APP_DEFAULT_LOCALE, int $id = 0, int $parentId = 0): ResponseInterface 
    {
        if (! $id) { return $this->fail(lang('Admin.IdNotFound')); }
        if (! $this->request->isAJAX()) { return $this->fail(lang('Admin.IdNotFound')); }
        if (! $data = $this->postDataValid($this->request->getRawInput(), 128)) {
            return $this->fail(lang('Admin.IdNotFound'));
        }
        $this->clearing($data);
        $this->setLanguage($lang);

        if (isset($data['pos']) && isset($data['action']) && $data['action'] === 'sortable') {
            if ($this->model->moveToPos((int) $id, (int) $data['pos'])) {
                return $this->respond($id, 200, lang('HeadLines.catalog.msgUpdate'));
            }
        } else
        if (isset($data['title'])) {
            $this->normalize($data);
            if (isset($data['name'])) {
                $data['name'] = $this->checkName($data['name']);
            }
            if ($this->model->dataСhange((int) $id, $data)) {
                return $this->respond($id, 200, lang('HeadLines.catalog.msgUpdate'));
            }
        } else 
        if (isset($data['active'])) {
            if ($this->model->active((int) $id)) {
                return $this->respond($id, 200, lang('HeadLines.catalog.msgUpdate'));
            }
        } else 
        if (isset($data['icon'])) {
            if ($this->model->setIcon((int) $id, checkFileName($data['icon']))) {
                return $this->respond($id, 200, lang('HeadLines.catalog.msgUpdate'));
            }
        }

        return $this->fail(lang('Admin.IdNotFound'));
    }

    /**
     * Delete the designated resource object from the model.
     * @param int $id
     */
    public function delete(int $id = 0) {
        if (! $this->model->deleteNode((int) $id)) {
            return $this->failNotFound(lang('Admin.navbar.msg.msg_get_fail'));
        }
		return $this->respondDeleted((int) $id);
    }

    private function setLanguage(string $lang): void
    {
        $lang = strtolower($lang);
        if ($lang === $this->lang) { return; }
        if (isset(SUPPORTED_LOCALES[$lang]) === false) { 
            $this->lang = APP_DEFAULT_LOCALE;
            return; 
        }
        $this->lang = $lang;
    }

    /**
     * Get resource data.
     */
    private function checkName(string $name): string
    {
        return strtolower(str_replace(['\\', '/', ' '], '', (checkFileName($name))));
    }

    private function clearing(array &$data): void 
    {
        if (! $data) { return; }
        $noexcept = array('<?', '?>', '/>', '<', '>', '{', '}');
        foreach ($data as $i => &$value) {
            if (!is_numeric($value) && !is_bool($value) && !is_null($value)) {
                $data[$i] = str_replace($noexcept, '', trim($value));
            }
        }
    }

    private function normalize(array &$data): void 
    {
        if (isset($data['href'])) { unset($data['href']); }
        if (isset($data['type'])) { unset($data['type']); }
        if (isset($data['link'])) { unset($data['link']); }
        if (isset($data['active'])) { unset($data['active']); }
        if (! isset($data['menu_deny'])) { $data['menu_deny'] = (int) 0; }
        if (! isset($data['search_deny'])) { $data['search_deny'] = (int) 0; }
        if (! isset($data['robots_deny'])) { $data['robots_deny'] = (int) 0; }
    }
}
