<?php 
namespace Sygecon\AdminBundle\Libraries\Tree;

use CodeIgniter\Database\BaseBuilder;
use Sygecon\AdminBundle\Libraries\Tree\NestedTab;
use App\Config\Boot\NestedTree;

final class NestedGet extends NestedTab 
{
    public const LANG_NONE = '-none-';

    public $onlyActive = null;
    public $setSelect = null;

    //Функция формирования дерева children
    public static function buildTree(array $elements = [], int $parentId = 0): array 
    {
        $branch = [];
        foreach ($elements as $element) {
            if ($element['parent'] == $parentId) {
                $children = self::buildTree($elements, $element['node_id']);
                if ($children) { 
                    $element[NestedTree::JSON_KEY_CHILDREN] = $children; 
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }
    
    /**
	 * Найдите путь к заданному узлу
	 * @return array or unordered list
	 */
	public function getLink(int $id = 0, string $lang = APP_DEFAULT_LOCALE, string $link = '', bool $includeSelf = true, bool $returnAsArray = false, bool $isActiveNoLink = true) 
    {
        $select = NestedTree::TAB_DATA . '.id, ' . 'parent.id AS node_id, ' . 
            NestedTree::TAB_DATA . '.title, ' . NestedTree::TAB_DATA . '.description';
        if (! $link) { $select .= ', parent.link'; }

        $builder = $this->getBuilder();
        $builder->select($select, false)
            ->from($this->table . ' AS node, ' . $this->table . ' AS parent', true)
            ->join(NestedTree::TAB_DATA, NestedTree::TAB_DATA . '.node_id = parent.id', 'left');
        $builder->where('node.tree =', 'parent.tree', false);
        if($includeSelf) {
            $builder->where('node.lft >=', 'parent.lft', false);
            $builder->where('node.lft <=', 'parent.rgt', false);
        } else {
            $builder->where('node.lft >', 'parent.lft', false);
            $builder->where('node.lft <', 'parent.rgt', false);
        }
        $builder->where('node.id', $id, false);
        $builder->where(NestedTree::TAB_DATA . '.language_id', (int) langIdFromName($lang), false);
        $this->whereActive($builder, 'parent', false);
        $rows = $builder->orderBy('parent.rgt - parent.lft', 'ASC', false)->get()->getResult('array');
        if($rows) {
            $rows = array_reverse($rows);
            if($returnAsArray) {
                return $rows;
            } else {
                return $this->buildCrumbs($rows, $link, $lang, $isActiveNoLink);
            }
        }
		return ($returnAsArray ? [] : '/');
	}

    public function getLinkNode(int $id = 0) : string
    {
        if ($node = $this->getBuilder()->select('link')->where('id', (int) $id)->get()->getRow()) { 
            return $node->link; 
        }
        return '/';
    }

    /**
	 * Получает массив узлов
	 *
	 * @param mixed $whereArg String or array of where arguments
     * @param integer $langId Number Filter pages by language
     * @param bool $recursive Boolean Recursive page search
	 * @param bool $orderASC Orderby Page sorting option
	 * @param integer $limit Number of rows to retrieve
	 * @param integer $offset Row to start retrieving from
	 * @return array Returns array of nodes found
	 */
    public function getTreeNodes(int $parentId = 0, string $lang = APP_DEFAULT_LOCALE, bool $recursive = true, bool $orderASC = true, bool $orderByDate = false,int $limit = 0, int $offset = 0): array
	{
        $order = (! $orderASC ? ' DESC' : ' ASC');
        $builder = $this->getBuilder();
        if($recursive && $parentId) {
            if (! $node = $builder->select('lft, rgt')->where('id', (int) $parentId)->get()->getRowArray()) {
                return [];
            }
        }
        $builder->select($this->select());
        $builder->join(NestedTree::TAB_DATA, NestedTree::TAB_DATA . '.node_id = ' . NestedTree::TAB_NESTED . '.id', 'left');
        $builder->where(NestedTree::TAB_NESTED . '.tree', $this->tree);
        if($recursive) {    
            if (isset($node) && $node) {
                $builder->where(NestedTree::TAB_NESTED . '.lft >=', (int) $node['lft']);
                $builder->where(NestedTree::TAB_NESTED . '.rgt <=', (int) $node['rgt']);
                unset($node['lft'], $node['rgt'], $node);
            }
            if ($orderByDate) {
                $order = NestedTree::TAB_DATA . '.updated_at' . $order . ', ' . NestedTree::TAB_NESTED . '.parent' . $order;
            } else {
                $order = NestedTree::TAB_NESTED . '.lft' . $order . ', ' . NestedTree::TAB_NESTED . '.parent' . $order;
            }
		} else {
			$builder->where(NestedTree::TAB_NESTED . '.parent', (int) $parentId);
            if ($orderByDate) {
                $order = NestedTree::TAB_DATA . '.updated_at' . $order;
            } else {
                $order = NestedTree::TAB_NESTED . '.lft' . $order;
            }
		}
        $builder->where(NestedTree::TAB_DATA . '.language_id', (int) langIdFromName($lang));
        $this->whereActive($builder, NestedTree::TAB_NESTED, null);
        $builder->orderBy($order);
        if ($limit) {
            if ($offset) {
                $builder->limit((int) $limit, (int) $offset);
            } else {
                $builder->limit((int) $limit);
            }
        }
		if ($catBuf = $builder->get()->getResult('array')) { return $catBuf; }
		return [];
	}

    public function getNode(int $id = 0, string $lang = APP_DEFAULT_LOCALE, string $select = '', string $result = 'array')
	{
        if ($id) {
            if (! $select) { $select = $this->select(); }
            $builder = $this->getBuilder()->select($select)
                ->join(NestedTree::TAB_DATA, NestedTree::TAB_DATA . '.node_id = ' . NestedTree::TAB_NESTED . '.id', 'left');
            if ($lang === self::LANG_NONE) {
                $builder = $builder->where(NestedTree::TAB_DATA . '.id', (int) $id);
            } else {
                $builder = $builder->where(NestedTree::TAB_NESTED . '.id', (int) $id)
                    ->where(NestedTree::TAB_DATA . '.language_id', (int) langIdFromName($lang));
            }
            $this->whereActive($builder, NestedTree::TAB_NESTED, null);
            return $builder->get()->getResult($result)[0];
        }
        return ($result === 'array' ? [] : null);
    }

    // private functions =========================================================

    private function select(): string 
    {
        if (is_string($this->setSelect) && $this->setSelect) { return $this->setSelect; }
        $select = NestedTree::TAB_DATA . '.*, ' .  $this->table . '.name, ' . $this->table . '.link, ' . 
            $this->table . '.parent, ' . $this->table . '.lft as index, ' .
            //$this->table . '.level' . ', ' . $this->table . '.rgt, ' . 
            'ROUND ((' . $this->table . '.rgt - ' . $this->table . '.lft - 1) / 2) AS have_children';
        foreach (NestedTree::PAGE_PROPERTY_COLUMNS as $colName => $i) {
            $select .= ', ' . $this->table . '.' . $colName;
        }
        return $select;
    }

    // Построение меню Хлебные крошки
    private function buildCrumbs(array &$crumbData = [], string $link = '', string $langName = '', bool $isActiveNoLink = true): string
	{
		$retVal = '<ol class="breadcrumb">';
        if ($link) {
            $retVal .= '<li class="breadcrumb-item icon"><a href="' . $link . '" title="' . 
                lang('Admin.menu.sidebar.catalogName') . '" asp-lazy="home"></a></li>';
        }
        $count = count($crumbData);
        --$count;
		foreach ($crumbData as $i => $item) {
            if ($isActiveNoLink && $count === $i) {
                $retVal .= '<li class="breadcrumb-item page active"><a href="javascript:void(0)" title="' . $item['description'] . '">' . $item['title'] . '</a></li>';
            } else {
                if ($link) {
                    if ($langName) {
                        $src = rtrim($link, '/') . '/' . $langName . '/' . $item['node_id'];
                    } else {
                        $src = rtrim($link, '/') . '/' . $item['node_id'];
                    }
                } else {
                    $src = $item['link'];
                }
                $retVal .= '<li class="breadcrumb-item page">' . 
                '<a href="' . $src . '" title="' . $item['description'] . '">' . $item['title'] . '</a>' . '</li>';
            }
            unset($crumbData[$i]);
		}
        unset($crumbData);
		$retVal .= '</ol>';
		return $retVal;
	}

    private function whereActive(BaseBuilder &$builder, string $table, ?bool $escape): void
    {
        if ($this->onlyActive !== null) {
            if ($this->onlyActive) {
                $builder = $builder->where($table . '.active', (int) 1, $escape);
            } else {
                $builder = $builder->where($table . '.active', (int) 0, $escape);
            }
        }
    }
    //Функция сравнения для сортировки
    // private function sort_catalog ($a, $b)
    // {
    //     $sort = 0;
    //     if ( isset($a['parent']) ) { $sort = ( $a['parent'] <=> $b['parent'] ); }
    //     if ( $sort === 0 && isset($a['lft']) ) { $sort = ( $a['lft'] <=> $b['lft'] ); }
    //     return $sort;
    // }
}