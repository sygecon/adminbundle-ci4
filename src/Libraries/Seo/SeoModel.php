<?php
/**
 * @author Panin Aleksei S <https://github.com/sygecon>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 */
namespace Sygecon\AdminBundle\Libraries\Seo;


use Config\App;
use Config\Database;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Boot\NestedTree;
use Sygecon\AdminBundle\Config\AccessControl;
use Sygecon\AdminBundle\Libraries\Parsers\SearchBuilder;
use Sygecon\AdminBundle\Libraries\Seo\Redirect;
use Sygecon\AdminBundle\Libraries\Seo\Robots;
use Sygecon\AdminBundle\Libraries\Seo\Sitemap;
use Throwable;

final class SeoModel 
{
    private const TABLE_DELETE_PAGE = 'deleted_nodes';
    private const TABLE_REDIRECT    = 'redirect_links';

    private const QUERY_WHERE       = ' WHERE ' . NestedTree::TAB_NESTED . '.tree = 1';
   
    private array $supportedLocales = [];
    private $isMultiLang;
    private $isDefLangEmpty;
    private $defaultLocale;

    private $db;
    private $closeDb;

    public function __construct(?BaseConnection &$db = null) 
    {
        $config = new App();
        try {
            $request = new CLIRequest($config);
        } catch (Throwable $th) {
            if (auth()->loggedIn() === false) {
                throw new \ErrorException('Error! ' . lang('Admin.error.notHavePermission'));
            }
        }

        if (! defined('APP_DEFAULT_LOCALE')) {
           require APPPATH . 'Config' . DIRECTORY_SEPARATOR . 'Boot' . DIRECTORY_SEPARATOR . 'options.php';
        }

        $this->defaultLocale = (int) 1;
        if (defined('SUPPORTED_LOCALES')) { 
            $this->supportedLocales = array_flip(SUPPORTED_LOCALES);
            $this->defaultLocale = (int) SUPPORTED_LOCALES[APP_DEFAULT_LOCALE];
        } else {
            $this->supportedLocales[$this->defaultLocale] = APP_DEFAULT_LOCALE;
        }
        $this->isMultiLang = (bool) count($this->supportedLocales) > 1;
        $this->isDefLangEmpty = (defined('DEFAULT_LANGUAGE_IS_EMPTY') && DEFAULT_LANGUAGE_IS_EMPTY === true ? true : false);
        
        helper('match');

        $this->closeDb = ($db instanceof BaseConnection ? false : true);
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
        if ($this->closeDb === true && $this->db instanceof BaseConnection) { 
            $this->db->close(); 
        }
    }
    
    // Обновление - пересборка
    public function build(): void
    {
        $data = $this->getPages();

        $sitemapModel = new Sitemap();
        $sitemapModel->createFile();

        $redirectModel = new Redirect($this->getRedirectLinks());

        $robotsModel = new Robots();
        foreach(AccessControl::BLOCK_LIST_URL as &$link) {
            $robotsModel->add($link);
        }
        foreach ($this->getDeletedPages() as $row) {
            if (isset($row['link']) === false) { continue; }
            $redirectModel->delete($row['link']);
            foreach($this->supportedLocales as $langId => $lang) {
                $robotsModel->add($this->normalizeLink($row['link'], (int) $langId));
            }
        }

        $red = [];
        foreach($data as &$row) {
            if (contains($row['link'], AccessControl::BLOCK_LIST_URL)) { continue; }
            
            $link = $this->normalizeLink($row['link'], (int) $row['language_id']);
            if ($row['active'] && ! $row['robots_deny']) { 
                $sitemapModel->add($link, $row['updated_at']);
            } else {
                $robotsModel->add($link);
                if (isset($red[$row['link']]) === false) {
                    $red[$row['link']] = 1;
                    $redirectModel->delete($row['link']);
                }
            }
        }

        $robotsModel->setSitemaps($sitemapModel->getFilenames());
        $robotsModel->build();

        $sitemapModel->build();

        $redirectModel->build();
        $this->deleteRedirectByTime($redirectModel->currentTime);
    }

    // Изменение
    public function update(string $where = ''): void
    {
        if (! $data = $this->getPages($where)) { return; }

        $searchModel = new SearchBuilder();

        $sitemapModel = new Sitemap();
        $sitemapModel->readAllFiles();
        
        foreach($data as &$row) {
            if (contains($row['link'], AccessControl::BLOCK_LIST_URL)) { continue; }
            if ($row['active']) {
                // Change file sitemap
                if (! $row['robots_deny']) { 
                    $sitemapModel->add(
                        $this->normalizeLink($row['link'], (int) $row['language_id']), 
                        $row['updated_at']
                    ); 
                }
                // Search index data
                if (! $row['search_deny']) { 
                    $searchModel->update($row['link'], $this->supportedLocales[$row['language_id']]); 
                }
            }
        }
        $sitemapModel->build();
    }

    public function visibilityChange(int $id = 0): void
    {
        if (! $data = $this->getPages(NestedTree::TAB_DATA . '.node_id = ' . (int) $id)) { return; }

        $sitemapModel = new Sitemap();
        $sitemapModel->readAllFiles();

        $robotsModel = new Robots();
        $robotsModel->readFile();

        foreach($data as &$row) {
            if (contains($row['link'], AccessControl::BLOCK_LIST_URL)) { continue; }

            $link = $this->normalizeLink($row['link'], (int) $row['language_id']);

            if ($row['active'] && ! $row['robots_deny']) {
                $sitemapModel->add($link, $row['updated_at']);
                $robotsModel->delete($link);
            } else {
                $sitemapModel->delete($link);
                $robotsModel->add($link);
            }
        }

        $robotsModel->build();
        $sitemapModel->build();
    }

    public function rename(array &$items = []): void
    {
        if (! $items) { return; }

        $sitemapModel = new Sitemap();
        $sitemapModel->readAllFiles();

        $robotsModel = new Robots();
        $robotsModel->readFile();

        $redirectModel = new Redirect();

        foreach($items as $i => &$row) {
            if (! $row || ! is_array($row)) { unset($items[$i]); continue; }
            
            $oldUrl = key($row);
            $newUrl = $row[$oldUrl];
            $this->addRedirectLink($oldUrl, $newUrl);
            foreach($this->supportedLocales as $langId => $lang) {
                $old = $this->normalizeLink($oldUrl, (int) $langId);
                $new = $this->normalizeLink($newUrl, (int) $langId);
                // Change file robots
                $robotsModel->rename($old, $new);
                // Change file sitemap
                $sitemapModel->rename($old, $new);
                // Change file redirect
                $redirectModel->add($old, $new);
            }
            unset($row[$oldUrl], $items[$i]);
        }
        
        $robotsModel->build();
        $sitemapModel->build();
        $redirectModel->build();
    }

    public function delete(string $url): void
    {
        $this->deleteRedirectLink($url);

        $sitemapModel = new Sitemap();
        $sitemapModel->readAllFiles();

        $robotsModel = new Robots();
        $robotsModel->readFile();

        $redirectModel = new Redirect();

        foreach($this->supportedLocales as $langId => $lang) {
            $link = $this->normalizeLink($url, (int) $langId);
            // Change file robots
            $robotsModel->add($link);
            // Change file sitemap
            $sitemapModel->delete($link);
            // Change file redirect
            $redirectModel->delete($link);
        }
        
        $robotsModel->build();
        $sitemapModel->build();
        $redirectModel->build();
    }

    public function updateSearch(string $url): void
    {
        $searchModel = new SearchBuilder();
        foreach($this->supportedLocales as $lang) {
            $searchModel->update($url, $lang);
        }
    }

    // PRIVATE FUNCTIONS ========================================================

    // Pages table
    private function getPages(string $where = ''): array 
    {
        if (! $this->db instanceof BaseConnection) { return []; }
        if ($where) { $where = ' AND ' . $where; }
        return $this->db->query(
            'SELECT ' . NestedTree::TAB_NESTED . '.name, ' . NestedTree::TAB_NESTED . '.active, ' . 
            NestedTree::TAB_NESTED . '.link, ' . NestedTree::TAB_NESTED . '.search_deny, ' . 
            NestedTree::TAB_NESTED . '.robots_deny, ' . NestedTree::TAB_DATA . '.id, ' . 
            NestedTree::TAB_DATA . '.language_id, ' . NestedTree::TAB_DATA . '.updated_at' . 
            ' FROM ' . NestedTree::TAB_NESTED . 
            ' LEFT JOIN ' . NestedTree::TAB_DATA . ' ON ' . NestedTree::TAB_DATA . '.node_id = ' . NestedTree::TAB_NESTED . '.id' . 
            self::QUERY_WHERE . $where . NestedTree::QUERY_ORDER
        )->getResultArray();
    }

    // Deleted pages table
    private function getDeletedPages(): array 
    {
        if (! $this->db instanceof BaseConnection) { return []; }
        $time = strtotime("-1 year", time());
        return $this->db->query('SELECT link FROM ' . self::TABLE_DELETE_PAGE . 
            ' WHERE DATE(created_at) > "' . date('Y-m-d H:i:s', $time) . '" ORDER BY link'
        )->getResultArray();
    }

    // Redirects table
    private function getRedirectLinks(): array
    {
        if (! $this->db instanceof BaseConnection) { return []; }
        return $this->db->query(
            'SELECT link_from, link_to, UNIX_TIMESTAMP(updated_at) as updated_at FROM ' . self::TABLE_REDIRECT
        )->getResultArray();
    }

    private function addRedirectLink(string $linkFrom = '', string $linkTo = ''): void
    {
        if (! $this->db instanceof BaseConnection) { return; }
        $this->db->transStart();
        $this->db->query('DELETE FROM ' . self::TABLE_REDIRECT . ' WHERE link_from = "' . $linkTo . '"');
        $this->db->query(
            'INSERT INTO ' . self::TABLE_REDIRECT . ' (link_from, link_to, updated_at)' .
            ' VALUES ("' . $linkFrom . '", "' . $linkTo . '", CURRENT_TIMESTAMP())'
        );
        $this->db->transComplete();
    }

    private function deleteRedirectByTime(?int $time): void
    {
        if (! is_numeric($time)) { return; }
        if (! $this->db instanceof BaseConnection) { return; }
        $this->db->transStart();
        $this->db->query('DELETE FROM ' . self::TABLE_REDIRECT . ' WHERE updated_at < UNIX_TIMESTAMP(' . $time . ')');
        $this->db->transComplete();
    }

    private function deleteRedirectLink(string $link): void
    {
        if (! $this->db instanceof BaseConnection) { return; }
        $this->db->transStart();
        $this->db->query('DELETE FROM ' . self::TABLE_REDIRECT . ' WHERE link_from = "' . $link . '" OR link_to = "' . $link . '"');
        $this->db->transComplete();
    }

    // =============================================================

    private function normalizeLink(string $link, int $langId): string
    {
        if ($link === '/' || $link === '/index' || $link === '/index.php') { $link = ''; }
        if ($this->isMultiLang === false) { return $link; }
        if ($langId === $this->defaultLocale && $this->isDefLangEmpty === true) { return $link; }
        if (! isset($this->supportedLocales[$langId])) { return $link; }
        return '/' . $this->supportedLocales[$langId] . $link;
    }
}