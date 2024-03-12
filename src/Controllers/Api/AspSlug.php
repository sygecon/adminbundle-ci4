<?php

namespace Sygecon\AdminBundle\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;
use Config\Boot\NestedTree;
use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Libraries\Tree\PageList;
use Sygecon\AdminBundle\Libraries\Tree\NestedGet;

final class AspSlug extends AdminController {
    
    use ResponseTrait;

    private int $nsTree = 1;

    public function me_pages(string $lang = APP_DEFAULT_LOCALE, string $nodesId = ''): ResponseInterface 
    {
        return $this->respond(
            jsonEncode(PageList::fromString($nodesId, $lang), false)
        , 200);
    }

    public function me_links(bool $onlyActive = null): ResponseInterface 
    {
        $onlyPath = false;
        if ($this->request->is('put')) {
            if ($getData = $this->request->getRawInput()) {
                $onlyPath = isset($getData['only-folder']);
            }
        }
        $tree = new NestedGet($this->nsTree);
        $tree->onlyActive = $onlyActive;
        $tree->setSelect = NestedTree::TAB_NESTED . '.id, ' . NestedTree::TAB_NESTED . '.name, ' . 
            NestedTree::TAB_NESTED . '.link as value, ' . NestedTree::TAB_NESTED . '.parent, ' . 
            NestedTree::TAB_DATA . '.node_id, ' . NestedTree::TAB_DATA . '.title, ' .  NestedTree::TAB_DATA . '.description';
        if ($onlyPath) {
            $tree->setSelect .= ', ROUND ((' . NestedTree::TAB_NESTED . '.rgt - ' . NestedTree::TAB_NESTED . '.lft - 1) / 2) AS have_children';
        }

        $data = $tree->getTreeNodes(0, $this->locale);

        if ($onlyPath && $data) {
            foreach($data as $i => &$row) {
                if (! $row['parent']) { continue; }
                if ($onlyPath === true && (int) $row['have_children'] < 1) { 
                    array_splice($data[$i], 0);
                    unset($data[$i]);
                }
            }
        }

        return $this->respond(jsonEncode($tree::buildTree($data), false), 200);
    }
}
