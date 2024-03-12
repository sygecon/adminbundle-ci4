<?php namespace Sygecon\AdminBundle\Libraries\Tree;

use Config\Database;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Boot\NestedTree;
use Sygecon\AdminBundle\Libraries\Seo\SeoModel;
use Throwable;

class NestedTab 
{
    protected const APP_ROUTE = APPPATH . 'Config' . DIRECTORY_SEPARATOR . 'Boot' . DIRECTORY_SEPARATOR . 'routes.php';

    protected $table = NestedTree::TAB_NESTED;
    protected $builder;
    protected $db;
    protected $closeDb;
    protected int $tree = 1;

    /** * Constructor */
	public function __construct(int $tree = 0, ?BaseConnection &$db = null)	
    {
        if ($tree) { $this->tree = $tree; }
        $this->closeDb = is_null($db);
        try {
            $db = $db ?? Database::connect(NestedTree::DB_GROUP);
            $this->db = &$db;
        } catch (Throwable $th) {
            throw new DatabaseException($th->getMessage());
        }
	}

    /** * Destructor */
    public function __destruct() 
    {
        if ($this->closeDb && $this->db instanceof BaseConnection) { $this->db->close(); }
    }

    public function setValue($value)
    {
        if (is_bool($value)) { return ($value ? 1 : (int) 0); }
        if (is_numeric($value)) { return (int) $value; }
        return checkFileName($value);
    }

    //==================================
    public function getParentNode(int $id = 0): array
    {
        if ($id) {
            if ($data = $this->db->query(
                'SELECT parent.*' .
                ' FROM NestedTree::TAB_NESTED AS node, NestedTree::TAB_NESTED AS parent' .
                ' WHERE (node.lft >= parent.lft AND node.lft <= parent.rgt) AND parent.tree = ' . $this->tree . ' AND node.id = ' . $id .
                ' ORDER BY parent.parent LIMIT 1'
            )->getResult('array')) { if (isset($data[0])) return $data[0]; }
        }
        return [];
    }
    
    /** * Get table builder */
    protected function getBuilder(): BaseBuilder
    {  
        if ($this->builder instanceof BaseBuilder) {
            return $this->builder;
        }
        if (! $this->db instanceof BaseConnection) {
            $this->db = Database::connect(NestedTree::DB_GROUP);
        }

        $this->builder = $this->db->table($this->table);
        return $this->builder;
    }

    protected function setLink(string &$oldLink, string &$newName): string
    {
        $buf = explode('/', trim(str_replace('//', '/', str_replace('\\', '/', $oldLink)), '/ '));
        array_pop($buf);
        $name = ($newName === 'index' ? '' : trim($newName, '\\/'));
        return '/' . trim(implode('/', $buf) . '/' . $name, '/');
    }

    // public function getFullLink (int $id = 0): string
    // {
    //     $link = $this->getQuerySlug($id);
    //     if (isset($link) && $link) {
    //         return implode('/', $link);
    //     }
    //     return '';
    // } 

    // protected function getQuerySlug(int $id): array
    // {
    //     if (isset($id) && $id) { 
            // return array_reverse(array_column($this->getBuilder()->select('parent.name')
            //     ->from(NestedTree::TAB_NESTED . ' AS node, ' . NestedTree::TAB_NESTED . ' AS parent', true)
            //     ->where('node.lft >=', 'parent.lft', false)
            //     ->where('node.lft <=', 'parent.rgt', false)
            //     ->where('node.id', $id, false)
            //     ->orderBy('parent.rgt - parent.lft', 'ASC', false)
            //     ->get()->getResultArray()
            // , 'name')); 
            // parent.parent > 0 AND 
            // case when email is NULL or ltrim(email) = '' then email2 else email end

    //         return array_reverse(array_column( 
    //             $this->db->query("
    //                 SELECT parent.name
    //                 FROM NestedTree::TAB_NESTED AS node,
    //                     NestedTree::TAB_NESTED AS parent
    //                 WHERE 
    //                     node.id = $id AND 
    //                     CASE WHEN node.parent > 0 
    //                     THEN
    //                         node.lft >= parent.lft AND 
    //                         node.lft <= parent.rgt
    //                     ELSE
    //                         parent.id = $id
    //                     END
    //                 ORDER BY parent.rgt - parent.lft ASC
    //             ")->getResultArray()
    //         , 'name'));
    //     }
    //     return [];
    // }

    /**
	 * _modifyNode
	 * Добавляет $ changeVal ко всем левым и правым значениям, которые больше или равны $node_int
	 * @param integer $node_int The value to start the shift from
	 * @param integer $changeVal unsigned integer value for change
	 * @access private
	 */
	// protected function modifyNode(int $node_int = 1, int $changeVal = 2, int $tree = 0) {
    //     if (! $tree) { $tree = $this->tree; }
    //     $this->db->transStart();
    //     $this->db->query(
    //         'UPDATE ' . NestedTree::TAB_NESTED .
    //         ' SET lft = CASE WHEN lft >= ' . $node_int . 
    //         ' THEN lft +' . $changeVal . ' ELSE lft END, rgt = rgt +' . $changeVal . 
    //         ' WHERE tree = ' . $tree . ' AND rgt >= ' . $node_int
    //     );
        // $builder = $this->getBuilder();
        // $builder->set('lft', 'lft+' . $changeVal, FALSE)->where('lft >=', $node_int)->update();
		// $builder->set('rgt', 'rgt+' . $changeVal, FALSE)->where('rgt >=', $node_int)->update();
    //     $this->db->transComplete();
    // }

    protected function isSlugInLevel(int $parentId = 0, string $slug = '', int $tree = 0): int 
    {
        if (! $tree) { $tree = $this->tree; }
        if ($row = $this->db->query('SELECT id FROM ' . NestedTree::TAB_NESTED . 
            ' WHERE tree = ' . $tree . ' AND parent = ' . $parentId . ' AND name = "' . $slug . '"'
        )->getRowArray()) { return (int) $row['id']; }
        return (int) 0;
    }

    protected function getRowChilds(int $id = 0, string $select = 'tree, lft, rgt, link'): ?object
    {
        if (! $id) { return null; }
        if ($row = $this->db->query(
            'SELECT ' . $select . ' FROM ' . NestedTree::TAB_NESTED . ' WHERE id = ' . $id
        )->getRow()) { return $row; }
        return null;
    }

    protected function changeLinkInChilds(int $id = 0, int $lenLinkOld = 0): bool
    {
        if (! $row = $this->getRowChilds($id)) { return false; }
        if (($row->rgt - $row->lft - 1) == 0) { return false; }

        if (! $items = $this->db->query(
            'SELECT id, link FROM ' . NestedTree::TAB_NESTED . ' WHERE tree = ' . $row->tree . ' AND lft > ' . $row->lft . ' AND rgt < ' . $row->rgt
        )->getResult('array')) { return false; }
        $link = $row->link;
        unset($row->link, $row->tree, $row->lft, $row->rgt, $row);  

        if (! $lenLinkOld && $link) { $link .= '/'; }
        $change = false;
        $content = $this->readFileRoute();
        $smItems = [];   
        $seoModel = new SeoModel($this->db); 
        foreach($items as $key => &$value) {
            $oldLink = $value['link'];
            $newLink = '/' . trim($link . '/' . ltrim(substr($oldLink, $lenLinkOld), '/'), '/');
            if ($this->renameUrlToRoute($content, $oldLink, $newLink)) { $change = true; }
            $smItems[] = [$oldLink => $newLink];  
            $items[$key]['link'] = $newLink;
        }
        $this->writeFileRoute($content, $change);
        unset($content);
        if ($this->getBuilder()->updateBatch($items, 'id') !== false) { 
            $seoModel->rename($smItems);
            return true; 
        }
        return false;
    }

    // Переименование Url в файле Роутера
    protected function renameUrlToRoute(string &$dataRoute = '', string &$oldUrl = '', string &$newUrl = ''): bool 
    {
        $oUrl = chr(39) . trim($oldUrl, '/');
        $nUrl = chr(39) . trim($newUrl, '/');
        if ($oUrl == $nUrl) { return false; }
        $change = false;
        if (mb_strpos($dataRoute, $oUrl . chr(39)) !== false) {
            $change = true;
            $dataRoute = str_replace($oUrl . chr(39), $nUrl . chr(39), $dataRoute);
        }
        if ($oUrl === '/') { $oUrl = ''; }
        if ($nUrl === '/') { $nUrl = ''; }
        $oUrl .= '/';
        if (mb_strpos($dataRoute, $oUrl) !== false) {
            $change = true;
            $dataRoute = str_replace($oUrl, $nUrl . '/', $dataRoute);
        }
        return $change;
    }

    protected function readFileRoute(): string 
    {
        helper('path');
        return readingDataFromFile(self::APP_ROUTE);
    }

    protected function writeFileRoute(string &$dataRoute, bool $change): void 
    {
        helper('path');
        if ($change === true) { writingDataToFile(self::APP_ROUTE, $dataRoute, false); }
    }

    protected function setLinkAsPath(string $link): string
    {
        if ($link === '' || $link === '/' || $link === '/index.php') { return DIRECTORY_SEPARATOR . 'index'; }
        return toPath(rtrim($link, '/\\'));
    }

    // Переименование Индексного файла по ссылке
    protected function renameLink(string $oldLink = '', string $newLink = ''): void
    {
        $oldUrl = NestedTree::PATH_INDEX . $this->setLinkAsPath($oldLink);
        $newUrl = NestedTree::PATH_INDEX . $this->setLinkAsPath($newLink);
        if ($newUrl === $oldUrl) { return; }
        if (is_dir($oldUrl) === false) { return; }
        if (is_dir($newUrl) === false) { 
            rename($oldUrl, $newUrl); 
            $items = [];
            $items[] = [$oldLink => $newLink];
            $seoModel = new SeoModel($this->db);
            $seoModel->rename($items);
            unset($items, $seoModel);
        }
    }

    protected function removingLinksInSeo(array $items): void
    { 
        if (! $items) { return; }
        $seoModel = new SeoModel($this->db);
        foreach($items as &$row) {
            $seoModel->delete($row['link']);
        }
    }

    protected function visibilityChange(int $id): void
    { 
        if (! $id) { return; }
        $seoModel = new SeoModel($this->db);
        $seoModel->visibilityChange($id);
    }

    protected function updateSearch(string $url = ''): void
    { 
        $seoModel = new SeoModel($this->db);
        $seoModel->updateSearch($url);
    }
}