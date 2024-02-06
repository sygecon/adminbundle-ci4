<?php

declare(strict_types=1);
/**
 * @author  Aspada.ru
 * @license http://www.opensource.org/licenses/bsd-license.php BSD 3-Clause License
 */
namespace Sygecon\AdminBundle\Libraries\HTML\Dom;

use CodeIgniter\HTTP\CLIRequest;
use Config\App;
use Sygecon\AdminBundle\Config\Paths;
use Sygecon\AdminBundle\Libraries\HTML\Dom\Document;
use Sygecon\AdminBundle\Libraries\HTML\WebDoc;
use Throwable;
use ErrorException;

/**
 * This is the default parser for the selector.
 */
final class Parser
{
    private const SITEMAP_FILENAME = 'sitemap.json';

    private array $options = [];
    private array $log = [];
    private $projectFolder = '';

    /**
     * Constructor
     */
    public function __construct(string $name = '')
    {
        ignore_user_abort(true);
        set_time_limit(0);

        $config = new App();
        try {
            $request = new CLIRequest($config);
        } catch (Throwable $th) {
            if (auth()->loggedIn() === false) {
                throw new ErrorException('Error! ' . lang('Admin.error.notHavePermission'));
            }
        }
        helper(['path', 'files']);
        $this->open($name);
    }

    /** * Destructor */
    public function __destruct() 
    {
        $this->completeProject(false);
    }

    /* * * @param string $name */
    public function build(): bool
    {
        if (isset($this->options['fields']) === false) { return false; }
        $fields = &$this->options['fields']; 
        if (! $fields) { return false; }
        if (! $sitemap = $this->getLinksFromSitemap()) { return false; }
        $lenRootSource = (int) mb_strlen(toUrl(parse_url($this->options['linkToSource'], PHP_URL_PATH)));
        $localRoot = $this->options['localRootLink'];

        $this->completePath();
        $pathBody = 'body' . DIRECTORY_SEPARATOR;
        $doc = new Document();

        foreach($sitemap as $link => $date) {
            $localURL = $localRoot . mb_substr(toUrl(parse_url($link, PHP_URL_PATH)), $lenRootSource);
            $result = [];
            $doc->open($link);
            
            foreach($fields as $key => $params) {
                if (isset($params['skip']) === true) { continue; }

                if (isset($params['value']) === true) { 
                    if (is_bool($params['value']) === true) {
                        $result[$key] = (int) ($params['value'] ? 1 : 0);
                    } else {
                        $result[$key] = $params['value'];
                    }
                    continue; 
                }

                switch (strtolower($params['callback'])) {
                    case 'clear':
                        if (isset($params['xPath'])) {
                            $doc->clearNode($params['xPath']);
                        }
                    break;

                    case 'sitemap':
                        if ($params['dataType'] === 'date') {
                            $result[$key] = meDate($date, (isset($params['tag']) ? $params['tag'] : ''));
                        } else {
                            $result[$key] = $localURL;
                        }
                    break;

                    case 'summarylist':
                        if (isset($params['xPath']) === true && isset($params['fields']) === true) {
                            $result[$key] = $this->findSummaryList($doc, $params['xPath'], $params['fields']);
                        }
                    break;

                    default:
                        $result[$key] = $this->executeCallback($doc, $params, false);
                }
            }
            $doc->close();

            $this->log['links_worked_out'][] = $link;

            $this->saveDataInJsonFile($pathBody . toSnake(castingPath($localURL, true)) . '.json', $result);
            $result = [];
        }

        $this->saveDataInJsonFile('resource.json', $doc->getResource());
        $this->saveDataInJsonFile('scripts.json', $doc->getScripts());
        $this->completeProject();
        return true;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getLog(): array
    {
        return $this->log;
    }

    public function getPath(): string
    {
        return $this->projectFolder;
    }

    // Private functions

    private function open(string $name): void
    {
        if (! is_file($file = Paths::IMPORT . toSnake($name) . '.json')) {
            throw new ErrorException('Error! Missing project ID.');
        }

        if (! $options = jsonDecode(file_get_contents($file))) {
            throw new ErrorException('Error! Missing project ID.');
        }

        if (isset($options['locale']) && isset($options['localRootLink']) && isset($options['linkToSource']) && 
            isset($options['sitemapSourceURL']) && isset($options['fields']) && 
            is_array($options['fields']) && $options['fields']) {
                $this->options = $options;
                $this->options['locale']         = $options['locale'] ?? APP_DEFAULT_LOCALE;
                $this->options['findChildsMode'] = $options['findChildsMode'] ?? false;
                $this->options['updateMode']     = $options['updateMode'] ?? false;
                $this->runProject($name);
        } else {
            throw new ErrorException('Error! Missing project ID.');
        }
    }

    private function findSummaryList(Document &$doc, string $query = '', array $arrayQueryFields = []): array
    {
        if (! $arrayQueryFields) { return []; }
        if (! is_object($nodeList = $doc->query(null, $query))) { return []; }
        if (! $nodeList->hasChildNodes()) { return []; }

        $i = 0;    
        $result = [];
        $doc->setNode();
        foreach ($nodeList->childNodes as $childNode) {
            $result[$i] = [];
            $res = false;

            foreach ($arrayQueryFields as $field => &$params) {
                if (isset($params['skip']) === true) { continue; }

                if (isset($params['value']) === true) { 
                    if (is_bool($params['value']) === true) {
                        $result[$i][$field] = (int) ($params['value'] ? 1 : 0);
                    } else {
                        $result[$i][$field] = $params['value'];
                    }
                    continue; 
                }

                if ($params['callback'] === 'clear') {
                    if (isset($params['xPath'])) {
                        $doc->clearNode($params['xPath']);
                    }
                    continue;
                }

                if (isset($params['xPath']) && $params['xPath']) { 
                    $doc->setNode($doc->query($childNode, $params['xPath']));
                } else {
                    $doc->setNode($childNode);
                }
                if ($doc->notNullNode() === false) { continue; }

                $res = true;
                $result[$i][$field] = $this->executeCallback($doc, $params, true);
                $doc->setNode();
            }
            if ($res === true) { ++$i; }
        }
        
        return $result;
    }

    private function executeCallback(Document &$doc, array $value, bool $ignoreXPath): string
    {
        if (! $value || is_array($value) === false) { return ''; }
        $callback = 'find';
        $callback .= (isset($value['callback']) === true ? ucfirst($value['callback']) : '');
        if (method_exists($doc, $callback) === false) { return ''; }

        $params = ((isset($value['params']) && $value['params']) ? explode('|', $value['params'], 3) : []);

        $doc->clearAttrHidden = (isset($value['clearAttrHidden']) ? (bool) $value['clearAttrHidden'] : false);

        if (isset($value['xPath']) && $ignoreXPath === false) { $doc->findXPath($value['xPath']); }

        if (isset($value['dataType']) && ($callback === 'find' || $callback === 'findText')) {
            $return = 'outerHtml';
            if ($callback === 'findText' || ! $value['dataType'] || $value['dataType'] === 'text') {
                if (isset($value['innerReturn'])) {
                    $return = 'innerText';
                } else {
                    $return = 'text';
                }
            } else if (isset($value['innerReturn'])) {
                $return = 'html';
            }
            $doc->setReturnResult($return, (isset($value['list']) ? true : false));
        }
        
        if (! $len = count($params)) { return $doc->{$callback}(); }

        if ($callback === 'findH1') { $params[0] = (int) $params[0]; }

        if ($len === 1) { return $doc->{$callback}(trim(stripslashes($params[0]))); }
        if ($len === 2) { return $doc->{$callback}(trim(stripslashes($params[0])), trim(stripslashes($params[1]))); }
        return $doc->{$callback}(trim(stripslashes($params[0])), trim(stripslashes($params[1])), trim(stripslashes($params[2])));
    }

    /**
     * Get links, Parses the Sitemap file.
     */
    private function getSitemap(): array
    {
        $file = $this->projectFolder . DIRECTORY_SEPARATOR . self::SITEMAP_FILENAME;
        if (! is_file($file)) { return []; }
        if (! $text = file_get_contents($file)) { return []; }

        $result = jsonDecode($text);
        if ($this->log['links_worked_out'] && is_array($this->log['links_worked_out'])) {
            foreach($result as $link => $date) {
                if (in_array($link, $this->log['links_worked_out']) === true) {
                    unset($result[$link]);
                }
            }
        }
        return $result;
    }
    
    private function getLinksFromSitemap(): array
    {
        if ($result = $this->getSitemap()) { return $result; }

        deletePath($this->projectFolder, false);
        if (! $filter = toUrl(parse_url($this->options['linkToSource'], PHP_URL_PATH))) { return []; }
        $url = $this->options['sitemapSourceURL'];
        if (! $res = parse_url($url)) { return []; }
        if (! $res['scheme']) { return []; }
        $filter = strtolower($res['scheme']) . '://' . strtolower(trim($res['host'], '/ ')) . '/' . $filter;
        if (! $xmlData = WebDoc::load($url)) { return []; }

        $this->log['build_start']   = meDate();
        $this->log['build_end']     = '';

        if ($level = (int) (! $this->options['findChildsMode'] ? 0 : 
            (is_bool($this->options['findChildsMode']) ? 9999 : $this->options['findChildsMode'])
        )) {
            $filter .= '/';
            $slice = strlen($filter); 
            $pos = 1;
            while ($pos = mb_stripos($xmlData, '<loc>' . $filter, $pos)) {
                $pos += 5;
                if (! $data = $this->findDataFromSitemap($xmlData, $pos)) { continue; }
                $link = key($data);
                if (! $str = trim(substr($link, $slice), '/')) { continue; }
                if ($level !== 9999 && substr_count('/' . $str, '/') !== $level) { continue; }
                $result[$link] = $data[$link];
            }

            $this->saveDataInJsonFile(self::SITEMAP_FILENAME, $result);
            return $result;
        }

        if (! $pos = mb_stripos($xmlData, '<loc>' . $filter . '</loc>')) { 
            $filter .= '/'; 
            if (! $pos = mb_stripos($xmlData, '<loc>' . $filter . '</loc>')) { return []; }
        }
        $pos += 5;
        if (! $data = $this->findDataFromSitemap($xmlData, $pos)) { return []; }
        $link = key($data);
        $result[$link] = $data[$link];

        $this->saveDataInJsonFile(self::SITEMAP_FILENAME, $result);
        return $result;
    }

    private function findDataFromSitemap(string &$xmlData,  int $pos): array
    {
        if (! $endPos = mb_stripos($xmlData, '</loc>', $pos)) { return []; }
        $link = mb_substr($xmlData, $pos, ($endPos - $pos));
        $date = '';

        if ($start = mb_strrpos(mb_substr($xmlData, 0, $pos), '<url>')) {
            if ($start = mb_stripos($xmlData, '<lastmod>', $start)) {
                $start += 9;
                if ($endPos = mb_stripos($xmlData, '</', $start)) { 
                    $date = mb_substr($xmlData, $start, ($endPos - $start));
                }
            }
        }
        return [$link => $date];
    }

    private function runProject(string $name): void
    {
        $path = Paths::IMPORT . toCamelCase($name) . DIRECTORY_SEPARATOR . strtolower(isset($this->options['locale']) ? $this->options['locale'] : APP_DEFAULT_LOCALE);
        if (createPath($path) === false) { 
            throw new ErrorException('Error! can`t create project folder.');
        }

        $this->projectFolder = $path;
        $path .= DIRECTORY_SEPARATOR . 'log.json';
        if (is_file($path) === true) {
            $this->log = jsonDecode(file_get_contents($path));
            return;
        }
        $this->logNull();
    }

    private function completePath(): void
    {
        if (createPath($this->projectFolder . DIRECTORY_SEPARATOR . 'body') === false) { 
            throw new ErrorException('Error! can`t create project folder.');
        }
    }

    private function completeProject(bool $allClose = true): void
    {
        if (is_dir($this->projectFolder) === false) { return; }
    
        if ($allClose === true) {
            $this->log['build_end'] = meDate();
            $this->log['links_worked_out'] = [];
            deletePath($this->projectFolder . DIRECTORY_SEPARATOR . 'sitemap.json');
        }

        $this->saveDataInJsonFile('log.json', $this->log);
    }

    private function logNull(): void
    {
        $this->log = [
            'build_start' => '',
            'build_end' => '',
            'import_start' => '',
            'import_end' => '',
            'links_worked_out' => []
        ];
    }

    private function saveDataInJsonFile(string $fileName, array $data): void
    {
        $fileName = $this->projectFolder . DIRECTORY_SEPARATOR . $fileName;
        $dir = previousUrl($fileName, DIRECTORY_SEPARATOR);
        if (createPath($dir) === false) { return; }
        file_put_contents($fileName, jsonEncode($data, false) . PHP_EOL, LOCK_EX);
    }
}
