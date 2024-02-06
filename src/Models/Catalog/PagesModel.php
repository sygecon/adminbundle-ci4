<?php namespace Sygecon\AdminBundle\Models\Catalog;

//use CodeIgniter\I18n\Time;
use App\Models\Boot\BaseModel as Model;
use App\Config\Boot\NestedTree;
use Sygecon\AdminBundle\Libraries\Tree\NestedGet as TreeGet;
use Sygecon\AdminBundle\Libraries\Tree\NestedSet as TreeSet;
use Sygecon\AdminBundle\Libraries\Tree\NestedMoving as TreeMoving;
use Sygecon\AdminBundle\Libraries\Parsers\ControlBuilder;
use Sygecon\AdminBundle\Models\Catalog\DeletedPagesModel;

final class PagesModel extends Model
{
    const LINK_PAGE = '/' . SLUG_ADMIN . '/catalog';

    const CLASS_SUFFIX = '';

    const ALLOWED_FIELDS = [
        'language_id', 'title', 'description', 'meta_title', 'meta_keywords', 
        'meta_description', 'summary', 'updated_at'
    ];

    const TREE_ID = 1;

    protected $table = NestedTree::TAB_DATA;
    
    protected $useTimestamps = true;

    protected $langId;
    
    // Создание
    public function create(array $data = [], string $langName = APP_DEFAULT_LOCALE, bool $isFirst = false): int
    {
        if (! $data) { return (int) 0; }
        $id         = (int) 0;
        $parentId   = (int) 0;
        $langId     = (int) langIdFromName($langName);
        if (isset($data['parent'])) { $parentId = (int) $data['parent']; }
        if ($parentId === 0) { $data['menu_deny'] = (int) 0; } 

        // Попытка создания Ноды страницы
        $tree = new TreeSet(self::TREE_ID, $this->db);
        if (! $nodeId = $tree->setNewNode($data, $isFirst)) { return (int) 0; }

        $data['node_id'] = (int) $nodeId;
        $data['language_id'] = $langId;
        if (isset($data['title']) && $data['title']) { 
            $data['title'] = ucfirst($data['title']); 
            if (isset($data['description']) && !$data['description']) {
                $data['description'] = $data['title'];
            }
        }
        if (isset($data['description']) && $data['description']) { 
            $data['description'] = ucfirst($data['description']); 
        }

        if (array_key_exists('updated_at', $data)) {
            $data['updated_at'] = meDate();
        }
        
        if (! $id = $this->insert($data)) {
            $tree->delete((int) $nodeId);
            return (int) 0;
        }
        
        if ($langId && defined('SUPPORTED_LOCALES') && count(SUPPORTED_LOCALES) > 1) {
            // Создание страниц для всех языков
            foreach(SUPPORTED_LOCALES as &$val) {
                $i = (int) $val;
                if ($i !== $langId) {
                    $data['language_id'] = $i;
                    $this->insert($data);
                }
            }
        }

        if ($parentId !== 0) { return $id; }
        // Попытка Создания Колнтроллера (для самого нижнего уровня)
        if ($row = $tree->find((int) $nodeId, 'name, link')) {
            //$name = ($row['name'] === 'index' ? 'home' : $row['name']);
            $control = new ControlBuilder();
            if ($control->insert(['name' => $row['name']]) === true) {
                $control->addUrlToRoute($row['link'], $row['name'], self::CLASS_SUFFIX);
            }
        }
        
        return $id;
    }

    // Удаление
    public function deleteNode(int $nodeId = 0): bool
    {
        if (! $nodeId) { return false; }
        $tree = new TreeSet(self::TREE_ID, $this->db);
        if (! $row = $tree->find((int) $nodeId)) { return false; }
        if ($result = $tree->delete((int) $nodeId)) {
            $control = new ControlBuilder();
            $control->removeUrlToRoute(toUrl($row['link']));
            $deletedPages = new DeletedPagesModel();
            $deletedPages->create($row);
            return $result;
        }
        return false;
    }

    public function deleteByLanguage(int $langId = 0): bool
    {
        if (! $langId) { return false; }
        $this->where('language_id', (int) $langId)->delete();
        return true;
    }

    // Активация / деактивация
    public function active(int $id = 0): bool
    {
        if ($nodeId = $this->getNodeId((int) $id)) {
            $tree = new TreeSet(self::TREE_ID, $this->db);
            if($tree->setActive((int) $nodeId)) { return true; }
        }
        return false;
    }

    // Иконка для страницы
    public function setIcon(int $id = 0, string $iconSrc = ''): bool
    {
        if ($nodeId = $this->getNodeId((int) $id)) { 
            $tree = new TreeSet(self::TREE_ID, $this->db);
            if ($tree->setIcon((int) $nodeId, $iconSrc)) {
                $this->update((int) $id, ['updated_at' => meDate()]);
                return true;
            }
        }
        return false;
    }

    // Изменение
    public function dataСhange(int $id = 0, array $data = []): bool
    {
        if (! $data) { return false; }
        if (! $nodeId = $this->getNodeId((int) $id)) { return false; }
        if (isset($data['language_id'])) { unset($data['language_id']); }
        if (isset($data['icon'])) { unset($data['icon']); }
        $tree = new TreeSet(self::TREE_ID, $this->db);
        if (! $oldData = $tree->find($nodeId)) { return false; }
        $tree->setDataСhange($nodeId, $data, $oldData);
        array_splice($oldData, 0);
        unset($oldData);
        if ($data = array_intersect_key($data, array_flip(self::ALLOWED_FIELDS))) {
            $data['updated_at'] = meDate();
            return $this->saveData($id, $data);
        }
        return false;
    }

    public function saveData(int $id = 0, array $data = []): bool
    {
        if (! $data) { return false; }
        if ($this->update((int) $id, $data)) { return true; }
        return false;
    }

    // Перемещение относительно другой страницы
    public function move(int $id = 0, int $targetId = 0): bool
    {
        if ($id && $targetId && $targetId !== $id) { 
            if (! $nodeId = $this->getNodeId((int) $id)) { return false; }
            if (! $target = $this->getNodeId((int) $targetId)) { return false; }
            $tree = new TreeMoving(self::TREE_ID, $this->db);
            return $tree->moveNode((int) $nodeId, (int) $target);
        }
        return false;
    }

    // Перемещение на определенную позицию в списке
    public function moveToPos(int $id = 0, ?int $position = null): bool
    {
        if ($id && $position !== null) { 
            if ($nodeId = $this->getNodeId((int) $id)) {
                $tree = new TreeMoving(self::TREE_ID, $this->db);
                return $tree->moveNodeToPos((int) $nodeId, (int) $position);
            }
        }
        return false;
    }

    // Вывод Хлебных крошек
    public function getBreadCrumb(int $id = 0, string $lang = APP_DEFAULT_LOCALE): string
    {
        if ($id) {
            $tree = new TreeGet(self::TREE_ID, $this->db);
            $breadcrumb = $tree->getLink((int) $id, $lang, self::LINK_PAGE);
            if ($breadcrumb && $breadcrumb !== '/') { 
                return $breadcrumb; 
            }	
        }

        return '<span class="h6" style="vertical-align:middle"><ol class="breadcrumb">' .
            '<li class="breadcrumb-item icon"><a href="' . self::LINK_PAGE . 
            '" asp-lazy="home" class="toolbtn"></a></li></ol></span>';
    }

    // Вывод данных страницы по идентификатору страницы
    public function getNode(int $id = 0): array
    {
        if (! $id) { return []; }
        $tree = new TreeGet(self::TREE_ID, $this->db);
        return $tree->getNode($id, TreeGet::LANG_NONE);
    }

    public function getParentNode(int $id = 0): int
    {
        if (! $id) { return (int) 0; }
        if ($row = $this->db->query('SELECT parent FROM ' . NestedTree::TAB_NESTED . ' WHERE id = ' . (int) $id)->getRow()) {
            return (int) $row->parent;
        }
        return (int) 0;
    }

    // Вывод страниц каталога 
    public function getPages(int $parentId = 0, string $langName = APP_DEFAULT_LOCALE) : array
    {
        $data = $this->getTree((int) $parentId, $langName, false);
        foreach ($data as &$value) { $this->setLinkPages($value, $langName); }
        return $data;
    }

    public function getPageId(int $nodeId = 0, string $langName = APP_DEFAULT_LOCALE): int
    {
        if (! $row = $this->db->query(
                'SELECT id FROM ' . $this->table . ' WHERE node_id = ' . (int) $nodeId . ' AND language_id = ' . (int) langIdFromName($langName)
            )->getRow())  { 
            return (int) 0;
        }
        return (int) $row->id;
    }

    public function setLinkPages(array &$node, string $langName = APP_DEFAULT_LOCALE): void
    {
        if (isset($node['have_children']) && (int) $node['have_children'] > (int) 0) {
            $node['href'] = self::LINK_PAGE . '/' . $langName . '/' . $node['node_id']; //'javascript:goPath(' . $node['id'] . ')';
            $node['type'] = 'folder';
        } else {
            $node['href'] = 'javascript:void(0)';
            $node['type'] = 'file';
        }
    }

    // Вывод Дерева 
    protected function getTree(int $parentId = 0, string $langName = APP_DEFAULT_LOCALE, bool $recursive = true, bool $orderASC = true, bool $orderByDate = false, int $limit = 0, int $offset = 0): array
    {
        $tree = new TreeGet(self::TREE_ID, $this->db);
        $data = $tree->getTreeNodes((int) $parentId, $langName, $recursive, $orderASC, $orderByDate, (int) $limit, (int) $offset);
        if ($recursive !== true) { return $data; }
        return $tree::buildTree($data);
    }

    protected function getNodeId(int $id): int
    {
        if (! $id) { return (int) 0; }
        if ($row = $this->find((int) $id, 'node_id, language_id')) { 
            $this->langId = (int) $row->language_id;
            return (int) $row->node_id; 
        }
        return (int) 0;
    }

    //! Get Id node from Id Page
    // private function getIdNodes(array $nodes): array
    // {
    //     if ($row = $this->builder()->select('node_id')
    //         ->whereIn($this->table . '.' . $this->primaryKey, $nodes)->get()->getResult('array')) 
    //     { 
    //         return array_column($row, 'node_id'); 
    //     }
    //     return [];
    // }
}