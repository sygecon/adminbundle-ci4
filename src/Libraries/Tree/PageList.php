<?php namespace Sygecon\AdminBundle\Libraries\Tree;

use CodeIgniter\Database\BaseConnection;
use Config\Database;
use App\Config\Boot\NestedTree;

final class PageList 
{
    protected const QUERY = 
        'SELECT tree.id, tree.name, tree.link%s, pages.title, pages.description FROM tree LEFT JOIN pages ON pages.node_id = tree.id WHERE pages.language_id = %u AND tree.active = 1 AND tree.id IN (%s) order by tree.lft';
    
    protected const IS_SUMMARY = ', pages.summary';

    protected static string $asLink = '';

    public static function fromArray(array $nodes = [], string $lang = APP_DEFAULT_LOCALE, ?BaseConnection $db = null): array 
    {
        if (is_array($nodes) && $nodes) { return self::fromString(implode(', ', $nodes), $lang, $db); }
        return [];
    }

    public static function fromString(string $nodes = '', string $lang = APP_DEFAULT_LOCALE, ?BaseConnection $db = null): array
    {
        if (! $nodes) { return []; }
        self::$asLink = ' AS slug';
        return self::builder($nodes, $lang, $db);
    }

    public static function relationship(string $filterName = '', bool $isLeft = true, int $nodeId = 0, string $lang = APP_DEFAULT_LOCALE, ?BaseConnection $db = null): array 
    {
        if (! $filterName) { return []; }
        if (! $nodeId) { return []; }
        self::$asLink = '';
    
        return self::builder((! $isLeft
            ? 'SELECT ' . NestedTree::COL_RELATION_LEFT . ' FROM ' . NestedTree::TAB_PREFIX_RELATION . $filterName . ' WHERE ' .  NestedTree::COL_RELATION_RIGHT . ' = ' . (int) $nodeId
            : 'SELECT ' . NestedTree::COL_RELATION_RIGHT . ' FROM ' . NestedTree::TAB_PREFIX_RELATION . $filterName . ' WHERE ' .  NestedTree::COL_RELATION_LEFT . ' = ' . (int) $nodeId
        ), $lang, $db);
    }

    public static function relationNode(string $filterName = '', bool $isLeft = true, ?BaseConnection $db = null): int 
    {
        if (! $filterName) { return (int) 0; }
        $dbClose = true;
        if ($db instanceof BaseConnection) {
            $dbClose = false;
        } else {
            $db = Database::connect(NestedTree::DB_GROUP);
        }
        $col = ($isLeft ? NestedTree::COL_RELATION_RIGHT : NestedTree::COL_RELATION_LEFT);
        $row = $db->query('SELECT ' . $col . ' FROM relationship WHERE name = "' . $filterName . '"')->getRow();
        if ($dbClose === true) { $db->close(); }
        if (isset($row) && isset($row->{$col})) {
            return (int) $row->{$col};
        }
        return (int) 0;
    }

    protected static function builder(string $nodes = '', string $lang = APP_DEFAULT_LOCALE, ?BaseConnection $db = null): array 
    {
        $dbClose = true;
        if ($db instanceof BaseConnection) {
            $dbClose = false;
        } else {
            $db = Database::connect(NestedTree::DB_GROUP);
        }

        $result = $db->query(
            sprintf(self::QUERY, self::$asLink, (int) langIdFromName($lang), $nodes)
        )->getResultArray();
        
        if ($dbClose === true) { $db->close(); }
        return ($result ? $result : []);
    }
}
