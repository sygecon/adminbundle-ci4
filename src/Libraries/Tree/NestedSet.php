<?php 
namespace Sygecon\AdminBundle\Libraries\Tree;

use Sygecon\AdminBundle\Libraries\Tree\NestedTab;
use App\Config\Boot\NestedTree;
use stdClass;

final class NestedSet extends NestedTab 
{
    private const DENY_CHANGING_KEYS = ['id', 'tree', 'parent', 'lft', 'rgt', 'level', 'link'];

    private $tableDeletedPage = 'deleted_nodes';

    /**
	 *  Вставляет новый узел в дерево каталога
	 *  @param array $newData An associative array of field names to values for \
	 *						  additional columns in tree table (eg CategoryName etc)
     *  @param bool $isFirst True/False узел вставить в начало блока / в конец
	 *  @return int  возвращает Id созданного узла или 0
	 *  @access public
	 */
	public function setNewNode(array &$newData = [], bool $isFirst = false): int 
    {
        $id = (int) 0;
        $isLastNode = false;
        $data = [
            'tree'  => (int) $this->tree,
            'parent'=> (int) 0,
            'lft'   => (int) 0,
            'rgt'   => (int) 0,
            'level' => (int) 0,
            'name'  => 'index'
        ];

        if (isset($newData['parent'])) {
            $data['parent'] = (int) $newData['parent'];
            unset($newData['parent']);
        }
        if (isset($newData['name'])) {
            $data['name'] = strtolower($this->setValue($newData['name']));
            if (! $data['name']) { $data['name'] = 'index'; }
            unset($newData['name']);
        }
        if (isset($newData['index'])) { 
            $data['lft'] = (int) $newData['index'];
            unset($newData['index']); 
        }
        if (isset($newData['tree'])) { 
            $data['tree'] = (int) $newData['tree'];
            unset($newData['tree']); 
        }
        if (isset($newData['id'])) { unset($newData['id']); }

        $data['link'] = ($data['name'] === 'index' ? '/' : '/' . $data['name']);

        foreach ($newData as $key => $value) {
            if (array_key_exists($key, NestedTree::PAGE_PROPERTY_COLUMNS)) {
                $data[$key] = $this->setValue($value);
                unset($newData[$key]);
            }
        }
        
        if ($data['parent']) {
            // Определяем ключи, если есть ID родительского узла
            if ($row = $this->getRowChilds((int) $data['parent'], 'tree, lft, rgt, link, level + 1 AS level')) { 
                $data['link']   = '/' . trim($row->link . $data['link'], '/');
                $data['tree']   = (int) $row->tree;
                $data['level']  = (int) $row->level;
                if ($isFirst === true) {
                    $data['lft'] = (int) ($row->lft + 1);
                } else {
                    $data['lft'] = (int) $row->rgt;
                }
                unset($row->level, $row->link, $row->tree, $row->rgt, $row->lft);
            } 
        } else if ($data['lft']) {
            // Определяем ключи если у нас задан левый ключ, но родительский узел не указан
            if ($row = $this->db->query(
                    'SELECT id, lft, level, parent, link FROM ' . NestedTree::TAB_NESTED . 
                    ' WHERE tree = ' . $data['tree'] . ' AND (lft = ' . $data['lft'] . ' OR rgt = ' . $data['lft'] . ')'
                )->getRow()) {
                $data['level'] = (int) $row->level;
                // Узел нашли по левому ключу, следовательно, новый узел у нас будет стоять перед найденным
                if ($row->lft == $data['lft']) {
                    $data['parent'] = (int) $row->parent;
                    $data['link'] = $this->setLink($row->link, $data['link']);
                // Узел нашли по правому ключу, следовательно, новый узел у нас будет стоять под найденным
                } else {
                    $data['parent'] = (int) $row->id;
                    $data['link'] = '/' . trim($row->link . $data['link'], '/');
                    ++$data['level'];
                } 
                unset($row->level, $row->link, $row->parent, $row->lft, $row->id);
            } else {
                $data['lft'] = (int) 0;
            }
        } 
        if (! $data['lft']) {
            $data['lft'] = 1;
            // Определяем максимальный правый ключ
            if ($row = $this->db->query(
                'SELECT MAX(rgt) + 1 AS max_index FROM ' . NestedTree::TAB_NESTED . ' WHERE tree = ' . $data['tree']
            )->getRow()) { 
                if ($isFirst === false) {
                    $data['lft'] = (int) $row->max_index;
                    $isLastNode = true;
                }
                unset($row->max_index);
            } else  {
                $isLastNode = true;
            }
        }

        if ($this->isSlugInLevel($data['parent'], $data['name'])) { return $id; }
        // Добавление отсутствкющих полей с данными по умолчанию
        foreach (NestedTree::PAGE_PROPERTY_COLUMNS as $i => &$value) {
            if (!array_key_exists($i, $data)) { $data[$i] = $this->setValue($value); }
        }
        $data['rgt'] = $data['lft'] + 1;
        if ($isLastNode === false) { 
            $this->db->transStart();
            $result = $this->db->query('UPDATE ' . NestedTree::TAB_NESTED .
                ' SET lft = CASE WHEN lft >= ' . $data['lft'] . 
                ' THEN lft + 2 ELSE lft END, rgt = rgt + 2' .  
                ' WHERE tree = ' . $data['tree'] . ' AND rgt >= ' . $data['lft']
            );
            $this->db->transComplete();
            if ($result === false) { return $id; }
        } 
        
        $this->db->transStart();
        if ($this->getBuilder()->insert($data) !== false) { 
            $id = (int) $this->db->insertID(); 

            $this->createLink($data['link'], (int) $id);
            $this->deleteFromDeletedPage($data['link']);
            if ($data['parent'] && isset($data['layout_id']) === true) {
                $this->setLayoutForCatalog((int) $data['parent'], (int) $data['layout_id']);
            }
        }
        $this->db->transComplete();
		return $id;
	}

    /**
	 * Активация / Деактивация узла
	 * @param int $id  Идентификатор узла
	 * @return bool в случае успеха TRUE, иначе FALSE
     * @access public
	 */
    public function setActive(int $id = 0): bool 
    {
        if (! $row = $this->getRowChilds($id, 'tree, lft, rgt, link, active')) { return false; }
        $value = (int) (! $row->active ? 1 : 0);
        if ($this->getBuilder()->set('active', $value)->where('id', (int) $id)->update() === false) { 
            return false; 
        }

        $this->visibilityChange($id);

        if ($value) {
            $this->createLink($row->link, (int) $id);
            return true;
        }

        $this->deleteLink($row->link, false);
        $this->childsSetParam('active', $row, $value);
        return true;
	}

    /**
	 * Присваиваем узлу ссылку на Изображение
	 * @param int $id  Идентификатор узла
     * @param string $iconSrc Ссылка на Изображение
	 * @return bool в случае успеха TRUE, иначе FALSE
     * @access public
	 */
    public function setIcon (int $id = 0, string $iconSrc = ''): bool 
    {
        if (! $id) { return false; }
        $res = false;
        $iconSrc = $this->setValue($iconSrc);
        $this->db->transStart();
        if ($this->getBuilder()->set('icon', $iconSrc)->where('id', $id)->update() !== false) {
            $res = true;
        }
        $this->db->transComplete();
        return $res;
	}

    public function find(int $id = 0, string $select = '*', string $type = 'array')
	{
        if ($id) {
            if ($data = $this->db->query(
                'SELECT ' . $select . ' FROM ' . NestedTree::TAB_NESTED . ' WHERE id = ' . (int) $id
            )->getResult($type)) {  return $data[0];  }
        }
        return ($type === 'array' ? [] : (object) []);
    }

    /**
	 * Удаление только одного узла дерева
	 * @param int $id  Идентификатор удаляемого узла
	 * @return bool в случае успеха TRUE, иначе FALSE
     * @access public
	 */
    public function deleteOne(int $id = 0): bool  
    {
        if (! $row = $this->getRowChilds($id, 'tree, parent, level, lft, rgt, link')) { return false; }
        
        $parent = (int) $row->parent;
        $this->deleteLayoutForCatalog($row);

        $this->db->transStart();
        $result = $this->db->query('DELETE FROM ' . NestedTree::TAB_NESTED . ' WHERE id = ' . $id);
        $this->db->transComplete();
        if (! $result) { return false; }

        $this->db->transStart();
        $this->db->query('UPDATE ' . NestedTree::TAB_NESTED .
            ' SET lft = CASE WHEN lft < ' . $row->lft .
            ' THEN lft ELSE CASE WHEN rgt < ' . $row->rgt .
            ' THEN lft - 1 ELSE lft - 2 END END,' .
            ' parent = CASE WHEN rgt < ' . $row->rgt . ' AND level = ' . ($row->level + 1) .
            ' THEN ' . $parent . ' ELSE parent END,' . 
            ' level = CASE WHEN rgt < ' . $row->rgt .
            ' THEN level - 1 ELSE level END, ' .
            ' rgt = CASE WHEN rgt < ' . $row->rgt . 
            ' THEN rgt - 1 ELSE rgt - 2 END' .
            ' WHERE tree = ' . $row->tree . ' AND ' .
            ' (rgt > ' . $row->rgt . ' OR (lft > ' . $row->lft . ' AND rgt < ' . $row->rgt . '))'
        );
        $this->db->transComplete();
        $this->deleteLink($row->link, false);
        return true;
    }   

    /**
	 * Удаляет узел (и все дочерние элементы) из древовидной таблицы
	 * @param int $id  Идентификатор удаляемого узла
	 * @return bool в случае успеха TRUE, иначе FALSE
     * @access public
	 */
    public function delete(int $id): bool 
    {
        if (! $row = $this->getRowChilds($id)) { return false; }

        $items = $this->getLinks($row);

        $skewTree = (int) ($row->rgt - $row->lft + 1);
        $this->deleteLayoutForCatalog($row);
        $this->db->transStart();
        $result = $this->db->query('DELETE FROM ' . NestedTree::TAB_NESTED .
            ' WHERE tree = ' . $row->tree . ' AND lft >= ' . $row->lft . ' AND rgt <= ' . $row->rgt
        );
        $this->db->transComplete();

        if (! $result) { return false; }

        $this->db->transStart();
        $this->db->query(
            'UPDATE ' . NestedTree::TAB_NESTED .
            ' SET lft = CASE WHEN lft > ' . $row->lft . 
            ' THEN lft - ' . $skewTree . ' ELSE lft END, rgt = rgt - ' . $skewTree . 
            ' WHERE tree = ' . $row->tree . ' AND rgt > ' . $row->rgt
        );
        $this->db->transComplete();

        $this->deleteLink($row->link);
        
        $this->removingLinksInSeo($items);
        return true;
	}

    /**
	 * Изменение данных узла
	 * @param int $id Идентификатор узла
	 * @param array $newData Новые данные
     * @param array $oldData Прежние данные
	 * @return void
     * @access public
	 */
    public function setDataСhange(int $id, array &$newData, array &$oldData): void
    {   
        if (! $id) { return; }
        if (! $newData) { return; }
        if (! $oldData) { return; }
        $data = '';
        $parentId = (int) $oldData['parent'];

        $objNode = new stdClass();
        $objNode->tree  = (int) $oldData['tree'];
        $objNode->lft   = (int) $oldData['lft'];
        $objNode->rgt   = (int) $oldData['rgt'];
        $objNode->link  = $oldData['link'];
        
        $newData = array_diff_key($newData, self::DENY_CHANGING_KEYS);
        $oldData = array_diff_key($oldData, self::DENY_CHANGING_KEYS);
        $robotsChange = false;
        $robotsValue = 0;
        $searchChange = false;
        $searchValue = 0;
        foreach ($newData as $n => $value) {
            if (! array_key_exists($n, $oldData)) { continue; }

            $key = $n;
            $var = $this->setValue($value);
            if ($key === 'name') { 
                if ($var === '' || $var === 'index.php') { $var = 'index'; }
            }
            if ($oldData[$n] == $var) { $key = ''; } // IS CHANGE VALUE
            unset($newData[$n], $oldData[$n]);
            if ($key === '') { continue; }

            if ($key === 'menu_deny') {             // NAVIGATION
                if ($var) { $this->childsSetParam('menu_deny', $objNode, 1); }
            } else if ($key === 'robots_deny') {    // INDEX SEARCH PAGE 
                $robotsChange   = true;
                $robotsValue    = $var;
            } else if ($key === 'search_deny') {    // SEARCH
                $searchChange   = true;
                $searchValue    = $var;
            } else if ($key === 'layout_id') {      // LAYOUT
                $this->setLayoutForCatalog($parentId, (int) $var);
            } else if ($key === 'name') {           // RENAME PAGE
                if ($this->isSlugInLevel($parentId, $var)) { continue; }
                $lenLinkOld = strlen($objNode->link);
                if ($parentId === 0) {
                    $slug = '/';  
                    $slug .= ($var === 'index' ? '' : $var);
                } else {
                    $slug = $this->setLink($objNode->link, $var);
                }
                if ($data !== '') { $data .= ', '; }
                $data .= 'link = "' . $slug . '"';
            }
            
            if ($data !== '') { $data .= ', '; }
            $data .= $key . ' = ';
            $data .= (is_numeric($var) ? $var : '"' . $var . '"');
        }

        foreach ($oldData as $key => &$value) { unset($oldData[$key]); }
        if (! $data) { return; }

        $this->db->transStart();
        $result = $this->db->query('UPDATE ' . NestedTree::TAB_NESTED . ' SET ' . $data . ' WHERE id = ' . $id);
        $this->db->transComplete();
        if(! $result) { return; }

        if ($robotsChange === true) { 
            $this->setRobotsDeny($id, $objNode, $robotsValue); 
        }
        if ($searchChange === true && isset($slug) === false) { 
            $this->setSearchDeny($objNode->link, $searchValue); 
        }

        if (isset($lenLinkOld) === false) { return; }

        $this->changeLinkInChilds((int) $id, $lenLinkOld);

        if (isset($slug) === true) {
            $this->renameLink($objNode->link, $slug);
            $this->deleteFromDeletedPage($slug);

            $change = false;
            $content = $this->readFileRoute();
            $change = $this->renameUrlToRoute($content, $objNode->link, $slug);
            $this->writeFileRoute($content, $change);
            unset($content);

            if ($searchChange === true) { $this->setSearchDeny($slug, $searchValue); }
            return;
        }
        
    }

    // private functions =========================================================

    private function childsSetParam(string $col, object &$row , int $value): void 
    {
        if (($row->rgt - $row->lft - 1) == 0) { return; }

        $this->db->transStart();
        $this->db->query(
            'UPDATE ' . NestedTree::TAB_NESTED . 
            ' SET ' . $col . ' = ' . $value . 
            ' WHERE tree = ' . $row->tree . ' AND lft > ' . $row->lft . ' AND rgt < ' . $row->rgt
        );
        $this->db->transComplete();

        if ($col === 'menu_deny') { return; }
        // Удаление ссылок в СЕО
        if (($col !== 'active' ? $value : ! $value)) {
            $this->removingLinksInSeo($this->getLinks($row));
        }
	}

    // Удаление записи из таблицы удаленных страниц
    private function deleteFromDeletedPage(string $link): void 
    {
        if (! $link || $link === '/index' || $link === '/index.php') { $link = '/'; }
        if (! isset($this->tableDeletedPage) || ! $this->tableDeletedPage) { return; }
        $this->db->query('DELETE FROM ' . $this->tableDeletedPage . ' WHERE link = "' . $link . '"');
    }
    
    // Макет каталога
    private function deleteLayoutForCatalog(object &$row): void
    {
        if (isset($row->tree) && isset($row->lft) && isset($row->rgt)) { 
            $this->db->transStart();
            $this->db->query('DELETE FROM ' . NestedTree::TAB_CATALOG_LAYOUT .
                ' WHERE node_id IN (SELECT id FROM ' . NestedTree::TAB_NESTED .
                ' WHERE tree = ' . $row->tree . ' AND lft >= ' . $row->lft . ' AND rgt <= ' . $row->rgt . ')'
            );
            $this->db->transComplete();
        }
    }

    private function setLayoutForCatalog(int $nodeId, int $layoutId): void
    {
        if (! $nodeId) { return; }
        $sql = 'INSERT INTO ' . NestedTree::TAB_CATALOG_LAYOUT . ' (node_id, layout_id) VALUES (' . (int) $nodeId . ', ' . (int) $layoutId .')';
        if ($row = $this->db->query(
                'SELECT layout_id FROM ' . NestedTree::TAB_CATALOG_LAYOUT . ' WHERE node_id = ' . (int) $nodeId
            )->getRowArray()) {
            if ((int) $row['layout_id'] === (int) $layoutId) { return; } 
            $sql = 'UPDATE ' . NestedTree::TAB_CATALOG_LAYOUT . ' SET layout_id = ' . (int) $layoutId . ' WHERE node_id = ' . (int) $nodeId; 
        }
        $this->db->transStart();
        $this->db->query($sql);
        $this->db->transComplete();
    }

    // Функция ремонта данных Nested таблицы
    public function setTabNesedRebuild(): void
    {
        $this->db->transStart();
        $this->db->query('SELECT rebuild_nested_set_tree()');
        $this->db->transComplete();
    }

    private function getLinks(object $row): array
    {
        return $this->db->query(
            'SELECT link FROM ' . NestedTree::TAB_NESTED . ' WHERE tree = ' . $row->tree . ' AND lft >= ' . $row->lft . ' AND rgt <= ' . $row->rgt
        )->getResult('array');
    }

    // Индексный файл по ссылке:  /catalog/ . $link . '/nid.php'
    // Создание 
    private function createLink(string $link = '', int $idNode = 0): void
    {
        if (! $idNode) { return; }
        $dir = NestedTree::PATH_INDEX . $this->setLinkAsPath($link);
        helper('files');
        if (createPath($dir) !== true) { return; }
        file_put_contents($dir . DIRECTORY_SEPARATOR . NestedTree::FILE_ID, 
            '<?php return ' . (int) $idNode . '; ?>'
        , LOCK_EX);
    }

    // Удаление
    private function deleteLink(string $link = '', bool $all = true): void
    {
        $dir = NestedTree::PATH_INDEX . $this->setLinkAsPath($link);
        $fileIndex = $dir . DIRECTORY_SEPARATOR . NestedTree::FILE_ID;
        if (file_exists($fileIndex) === true) { unlink($fileIndex); }
        helper('files');
        deletePath($dir, $all);
    }

    // Удаление индексных файлов для Поиска по файлам
    private function setSearchDeny(string $link, int $value): void
    {
        if (! $value) { 
            $this->updateSearch($link);
            return; 
        }

        $dir = NestedTree::PATH_INDEX . $this->setLinkAsPath($link);
        if (is_dir($dir) === false) { return; }
        $len = strlen(NestedTree::FILE_INDEX_WORD);
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if(is_file($dir . DIRECTORY_SEPARATOR . $file) && substr($file, 0, $len) === NestedTree::FILE_INDEX_WORD) {
                    //if (preg_match("~^a+\.php$~",$file))
                    unlink($dir . DIRECTORY_SEPARATOR . $file);
                }
            }
            closedir($dh);
        }
    }
    
    private function setRobotsDeny(int $id, object &$row, int $value): void
    {
        $this->visibilityChange($id);
        $this->childsSetParam('robots_deny', $row, $value);
    }
}