<?php namespace Sygecon\AdminBundle\Config;

use App\Config\Boot\NestedTree;
use Sygecon\AdminBundle\Libraries\Tree\NestedGet as TreeGet;

final class Catalog
{
	public const TREE_ID = 1;

	public const CURRENT_LINK = '/' . SLUG_ADMIN . '/catalog';

	//Tree Catalog TinyMCE
    public static function dataForHtmlEditor(string $lang = '', int $nodeId = 0, bool $onlyPath = false): array 
    {
        $data = [];
        if ($lang) {
            //helper('path');
            $parentId = null;
            $tree = new TreeGet(self::TREE_ID);
            $tree->onlyActive = true;
            $tree->setSelect = NestedTree::TAB_NESTED . '.id, ' . NestedTree::TAB_NESTED . '.name, ' . 
                NestedTree::TAB_NESTED . '.link as value, ' . NestedTree::TAB_NESTED . '.parent, ' . 
                NestedTree::TAB_DATA . '.title, ' .  NestedTree::TAB_DATA . '.description, ' .
                'ROUND ((' . NestedTree::TAB_NESTED . '.rgt - ' . NestedTree::TAB_NESTED . '.lft - 1) / 2) AS have_children, 1 as type';
            $data = $tree->getTreeNodes($nodeId, $lang);

            if (! $nodeId && ! $onlyPath) { return $data; }

            foreach($data as $i => &$row) {
                if ($parentId === null) {
                    $parentId = (int) $row['parent'];
                } else if ($parentId > (int) $row['parent']) {
                    $parentId = (int) $row['parent'];
                }
                if (! $row['parent']) { continue; }
                if ($onlyPath === true && (int) $row['have_children'] < 1) { 
                    array_splice($data[$i], 0);
                    unset($data[$i]);
                }
            }
            //baseWriteFile('melog-1.txt', jsonEncode($dataTree, true));
            if ($nodeId && $data && $parentId) {
                $data[] = ['id' => $parentId, 'parent' => (int) 0, 'value' => '/index', 'name' => 'home', 'title' => lang('Admin.goHome'), 'type' => 1];
            }
        }
        return $data;
    }
}