<?php
namespace Sygecon\AdminBundle\Libraries\Catalog;

use App\Config\Boot\NestedTree;

class ListHTML
{
    private const FORMAT_ROOT = '<nav>%s</nav>';
    private const LIST_TAG = 'UL';
    private const LIST_ATTR = '';
    private const ITEM_ATTR = '';

    private $tree;
    
    public function __construct(array &$data = [])
    {
        $this->tree = $this->buildTree($data);
    }

    public function render(): string
    {
        if (! $this->tree) { return ''; }

        $result = '';
        $this->internalRender($this->tree, $result);
        return sprintf(self::FORMAT_ROOT, $result);
    }
    
    private function buildTree(array &$data): array
    {
        $grouped = [];
        $lessParent = null; 
        foreach ($data as $i => &$row){
            $parent = (int) $row['parent'];
            $grouped[$parent][] = $row;
            if ($lessParent === null) { $lessParent = $parent; }
            unset($data[$i]);
        }

        $fnBuilder = function(array $siblings) use (&$fnBuilder, $grouped) {
            foreach ($siblings as $k => $sibling) {
                $id = (int) $sibling['id'];
                if(isset($grouped[$id])) {
                    $sibling[NestedTree::JSON_KEY_CHILDREN] = $fnBuilder($grouped[$id]);
                }
                $siblings[$k] = $sibling;
            }
            return $siblings;
        };

        if ($lessParent === null) { return []; }
        return $fnBuilder($grouped[$lessParent]);
    }

    private function internalRender(array $nodes, string &$result): void
    {
        $result .= '<' . self::LIST_TAG . self::LIST_ATTR . '>';
        foreach($nodes as &$node) {
            $result .= '<li' . self::ITEM_ATTR . '> ' . $node['name'];
            if (isset($node[NestedTree::JSON_KEY_CHILDREN])) {
                $this->internalRender($node[NestedTree::JSON_KEY_CHILDREN], $result);
            }
            $result .= "</li>";
        }
       $result .= '</' . self::LIST_TAG . '>';
    }
}
