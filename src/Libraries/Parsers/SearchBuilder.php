<?php 
/**
 * @author Panin Aleksei S <https://github.com/sygecon>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 */
namespace Sygecon\AdminBundle\Libraries\Parsers;

use CodeIgniter\HTTP\CLIRequest;
use Config\App;
use Config\Database;
use Config\Boot\NestedTree;
use App\Libraries\Loader\WebDoc;
use Sygecon\AdminBundle\Config\AccessControl;
use Sygecon\AdminBundle\Libraries\HTML\DomDoc;
use Sygecon\AdminBundle\Libraries\Parsers\Morphyus;
use DateTime;
use Throwable;

final class SearchBuilder
{
    private const MIN_CONTENT_LENGTH = 150;

    private const LOGGER_FILE = WRITEPATH . 'base' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'search_builder.log';

    private array $logger = [];

    private $morphy;
    private $baseUrl;
    private $isDefLangEmpty;

    function __construct() {
        $config = new App();
        try {
            $request = new CLIRequest($config);
        } catch (Throwable $th) {
            if (auth()->loggedIn() === false) {
                throw new \ErrorException('Error! ' . lang('Admin.error.notHavePermission'));
            }
        }

        $this->baseUrl = rtrim($config->baseURL, '/ ');
        try {
            $this->morphy = new Morphyus();
            $this->isDefLangEmpty = (defined('DEFAULT_LANGUAGE_IS_EMPTY') && DEFAULT_LANGUAGE_IS_EMPTY === true ? true : false);
        } catch (Throwable $th) {
            throw $th->getMessage();
        }
    }

    public function builder(): void 
    {
        if (! $this->morphy) { return; }
        $this->getLogger();
        if (! $data = $this->getPages()) { return; }
        $supportedLocales = array_flip(SUPPORTED_LOCALES);
        helper('match');
        foreach($data as &$row) {
            if (contains($row['link'], AccessControl::BLOCK_LIST_URL)) { continue; }
            if (! $row['search_deny']) { 
                $this->update($row['link'], $supportedLocales[$row['language_id']]);
            } else {
                $this->delete($row['link'], $supportedLocales[$row['language_id']]);
            }
        }
        $this->setLogger();
    }

    public function update(string $link = '', string $lang = ''): void 
    {
        if (! $this->morphy) { return; }
        if (! $fileName = $this->getIndexFile($link, $lang)) { return; }

        if (! $url = filter_var(
            $this->siteUrl($this->normalizeLink($link, $lang))
        , FILTER_SANITIZE_URL)) { return; }

        $source = DomDoc::docAsArray(WebDoc::load($url), true);
        if (strlen($source['content']) < self::MIN_CONTENT_LENGTH) { 
            $this->deleteIndexFile($fileName);
            return; 
        }
        
        $this->saveToIndexFile($fileName, $lang,
            $source['title'] . ' ' . $source['description'] . ' ' . $source['content']
        );
    }

    public function delete(string $link = '', string $lang = ''): void 
    {
        $this->deleteIndexFile($this->getIndexFile($link, $lang));
    }

    // PRIVATE FUNCTIONS ========================================================

    private function getLogger(): void
    {
        $this->logger = [];
        if (is_file(self::LOGGER_FILE)) { 
            if ($text = file_get_contents(self::LOGGER_FILE)) { 
                $this->logger = explode("\n", $text);  
            }
        }
    }

    private function setLogger(): void
    {
        $date = new DateTime(); // For today/now, don't pass an arg.
        $date->modify('-1 day');
        $text = $date->format('Y-m-d H:i:s') . "\n" . 'Logger search builder' . PHP_EOL;
        file_put_contents(self::LOGGER_FILE, $text);
    }

    private function saveToIndexFile(string $fileName, string $lang, string $text): void
    {
        $this->morphy->setLanguage($lang);
        $this->morphy->setStoreInFile($fileName);
        $this->morphy->makeIndex($text);
    }

    private function getIndexFile(string $link = '', string $lang = APP_DEFAULT_LOCALE): string
    {
        if (! $lang) { return ''; }
        $path = toPath(trim($link, '/\\'));
        if ($path === '' || $path === 'index.php') { $path = 'index'; }
        return NestedTree::PATH_INDEX . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . NestedTree::FILE_INDEX_WORD . $lang . '.php';
    }

    private function deleteIndexFile(string $fileName): void
    {
        if (is_file($fileName)) { unlink($fileName); }
    }

    private function getPages(): array 
    {
        try {
            $db = Database::connect(NestedTree::DB_GROUP);
            $where = NestedTree::TAB_NESTED . '.tree = 1 AND ' . NestedTree::TAB_NESTED . '.active = 1';
            if ($this->logger && $this->isDate($this->logger[0])) {
                $where .= ' AND DATE(' . NestedTree::TAB_DATA . '.updated_at) > "' . $this->logger[0] . '"';
            }
            $result = $db->query(
                'SELECT ' . NestedTree::TAB_NESTED . '.link, ' . NestedTree::TAB_DATA . '.language_id, ' . 
                NestedTree::TAB_NESTED . '.search_deny, ' . NestedTree::TAB_DATA . '.updated_at' . 
                ' FROM ' . NestedTree::TAB_NESTED . 
                ' LEFT JOIN ' . NestedTree::TAB_DATA . ' ON ' . NestedTree::TAB_DATA . '.node_id = ' . NestedTree::TAB_NESTED . '.id' . 
                ' WHERE ' . $where . NestedTree::QUERY_ORDER
            )->getResultArray();
            $db->close();
            if (! $result) { return []; }
            return $result;
        } catch (Throwable $th) {
            return [];
        }
    }

    private function normalizeLink(string $link, string $lang): string
    {
        if ($link === '/' || $link === '/index' || $link === '/index.php') { $link = ''; }
        if (MULTILINGUALITY === false) { return $link; }
        if ($lang === APP_DEFAULT_LOCALE && $this->isDefLangEmpty === true) { return $link; }
        if (! isset(SUPPORTED_LOCALES[$lang])) { return $link; }
        return '/' . $lang . $link;
    }

    private function isDate(string $date): bool 
    {
        if (DateTime::createFromFormat('Y-m-d H:i:s', $date) !== false) { return true; }
        return false;
    }

    private function siteUrl(string $url): string 
    {
        return $this->baseUrl . '/' . trim($url, '/ ');
    }
}

