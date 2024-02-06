<?php

declare(strict_types=1);
/**
 * @author  Aspada.ru
 * @license http://www.opensource.org/licenses/bsd-license.php BSD 3-Clause License
 */
namespace Sygecon\AdminBundle\Libraries\HTML;

use CodeIgniter\HTTP\CLIRequest;
use Config\App;
use App\Config\Boot\NestedTree;
use Sygecon\AdminBundle\Config\Paths;
use Sygecon\AdminBundle\Models\Catalog\PagesModel;
use Sygecon\AdminBundle\Libraries\HTML\Dom\Parser;
use Sygecon\AdminBundle\Libraries\HTML\WebDoc;
use ErrorException;
use Throwable;

/**
 * This is the default parser for the selector.
 */
final class Import
{
    private const RESOURCE_FILE_NAME = 'resource.json';

    private const DEFAULT_SHEET_NAME = 'page';
    private const DEFAULT_LAYOUT_ID = 1;

    // private const QUERY_SHEET_NAME = 'SELECT layouts.sheet_name FROM layouts LEFT JOIN catalog_layout ON catalog_layout.layout_id = layouts.id WHERE catalog_layout.node_id = %d LIMIT 1';

    private $options; 
    private $path;
    private $log; 

    private $model;

    private int $idLayout = 0;

    // private $request;

     /**
     * Constructor
     * @param string $name
     */
    public function __construct() {
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
        
        helper(['model', 'path', 'files']);
    }

    public function build(string $name = ''): void
    {
        if (! $name) {
            throw new ErrorException('Error! Missing project ID.');
        }
        $this->open($name);
        
        if (is_dir($this->path) === false) { return; }
        if (! isset($this->log['build_end']) || ! $this->log['build_end']) { return; }
        
        $localURL = $this->options['localRootLink'] ?? '';
        if (! $localURL) { return; }
        $localURL = toUrl(parse_url($localURL, PHP_URL_PATH));
        $modelName = $this->options['modelName'] ?? '';
        $langName = $this->options['locale'] ?? APP_DEFAULT_LOCALE;

        $this->start();
        foreach ($this->getFiles() as $fileName) {
            if (isset($this->log['links_worked_out'][$fileName]) === true) { continue; }

            if ($this->goToImport($fileName, $langName, $modelName) === true) {
                $this->log['links_worked_out'][$fileName] = meDate();
                $this->saveDataInJsonFile('log.json', $this->log);
                $this->deleteDataFile('body' . DIRECTORY_SEPARATOR . ltrim($fileName, '/\\ '));
            }
        }
        $this->complete();
    }

    //
    private function open(string $name): void
    {
        $this->idLayout = (int) 0;
        try {
            $parser = new Parser($name);
            $this->path     = $parser->getPath();
            $this->options  = $parser->getOptions();
            $this->log      = $parser->getLog();
            $this->model    = new PagesModel();
        } catch (Throwable $th) {
            throw new ErrorException($th->getMessage());
        }
    }

    private function goToImport(string $fileName, string $langName, string $modelName): bool
	{
        $pageId = (int) 0;
        $path = substr($fileName, 0, -5);

        if (! $data = $this->getDataFromJsonFile('body' . DIRECTORY_SEPARATOR . ltrim($fileName, '/\\ '))) { return false; }
        
        if ($nodeId = $this->getIdFromUrl($path)) {
            if (! $pageId = $this->model->getPageId($nodeId, $langName)) { return false; }
        } else {
            if (! $pageId = $this->createPage(
                toUrl($path), 
                $langName, 
                dataKeyIntersect($data, NestedTree::PAGE_PROPERTY_COLUMNS)
            )) { return false; }

            if (! $row = $this->model->find($pageId, 'node_id')) { return false; }

            $nodeId = (int) $row->node_id; 
            unset($row->node_id, $row);
        }
        
        return $this->update($nodeId, $pageId, $modelName, $data);
    }

    // Изменение данных страницы
    private function update(int $nodeId = 0, int $pageId = 0, string $sheet = '', array $data = []): bool
	{
        if (! $pageId) { return false; } 
        $result = false;

        if (isset($data['icon'])) {
            if (! $nodeId) { return false; }

            $this->model->db->transStart();
            $this->model->db->query(
                'UPDATE ' . NestedTree::TAB_NESTED . ' SET icon = "' . $data['icon'] . '"' . 
                ' WHERE tree = ' . $this->model::TREE_ID . ' AND id = ' . $nodeId
            );
            $this->model->db->transComplete();
            unset($data['icon']);
        }
        if (! $data) { return false; }

        if ($dataPage = dataIntersect($data, $this->model::ALLOWED_FIELDS)) {
            $result = $this->model->saveData((int) $pageId, $dataPage);
        }

        if (! $data) { return $result; }
        if (! $sheet) { return $result; }
        if (! is_object($model = $this->createNewClass($sheet))) { return $result; }

        $fields = &$this->options['fields'];
        foreach($data as $key => &$value) {
            if (isset($fields[$key]) === false) { 
                unset($data[$key]);
                continue; 
            }
            // Field as a relation to another node
            if (isset($fields[$key]['relation']) === true) { 
                $data[$key] = $this->dataAsRelationship(
                    is_array($value) === true ? $value : jsonDecode($value)
                );
            }
        }

        return $model->setData((int) $pageId, $data);
    }

    // Upload resource files to a shared folder
    private function uploadResourceFiles(): void
    {
        if (! $data = $this->getDataFromJsonFile(self::RESOURCE_FILE_NAME)) { return; }

        $rootLink = '/' . trim(str_replace('\\', '/', strtolower(Paths::ROOT_PUBLIC_PATH)), '/') . '/';
        $lenRoorPath = strlen($rootLink);

        foreach ($data as $type => &$value) {
            foreach ($value as $url) {
                if (! $res = WebDoc::load($url)) { continue; }
                $localLink = parse_url($url, PHP_URL_PATH);

                if ($type === 'script') {
                    if (substr($localLink, 0, ($lenRoorPath + 4)) !== $rootLink . 'js/') { 
                        if (substr($localLink, 0, 4) !== '/js/') { $localLink = $rootLink . 'js' . $localLink; }
                    }
                }

                if ($type === 'style') {
                    if (substr($localLink, 0, ($lenRoorPath + 5)) !== $rootLink . 'css/') { 
                        if (substr($localLink, 0, 7) === '/style/') { $localLink = $rootLink . 'css' . substr($localLink, 6); }
                        else
                        if (substr($localLink, 0, 5) !== '/css/') { $localLink = $rootLink . 'css' . $localLink; }
                    }
                }

                if (substr($localLink, 0, $lenRoorPath) !== $rootLink) { 
                    $localLink = $rootLink . ltrim($localLink, '/');
                }

                $fileName = FCPATH . castingPath($localLink, true);
                $dir = previousUrl($fileName, DIRECTORY_SEPARATOR);
                if (createPath($dir) === false) { continue; }

                file_put_contents($fileName, $res, LOCK_EX);
            }
        }
        $this->deleteDataFile(self::RESOURCE_FILE_NAME);
    }

    // Field as a relation to another node
    private function dataAsRelationship(array $data): array
    {
        $pages = [];
        foreach ($data as $value) {
            if (isset($value['href']) === false) { continue; }
            if ($id = $this->getIdFromUrl($this->normalizeLink($value['href']))) { 
                $pages[] = ['id' => (int) $id];
            }
        }
        return $pages;
    }

    private function normalizeLink(string $link): string
    {
        $link = toUrl($link);
        if (MULTILINGUALITY === false) { return $link; }
        if (! $pos = strpos($link, '/')) { return $link; }
        $segment = substr($link, 0, $pos);
        if (isset(SUPPORTED_LOCALES[$segment]) === true) { 
            ++$pos;
            return substr($link, $pos);
        }
        return $link;
    }

    private function createPage(string $pageLink, string $langName, array $data): int
    {
        if (! $pageLink) { return (int) 0; }
        $parentId = (int) 0;
        $link = ltrim(previousUrl($pageLink), '/');
        $name = getName($pageLink);

        if ($link !== $name) {
            if (! $parentId = $this->getIdFromUrl($link)) { return (int) 0; }
        }
        $data['name'] = $name;
        if (isset($data['title']) === false || ! $data['title']) {
            $data['title'] = (isset($data['description']) === true && $data['description'] 
                ? $data['description'] 
                : $name
            );
        }
        $data['parent'] = (int) $parentId;
        $data['layout_id'] = $this->getLayoutId(($this->options['modelName'] ?? self::DEFAULT_SHEET_NAME));

        return $this->model->create($data, $langName);
    }

    private function getLayoutId(string $nameSheet): int
    {
        if ($this->idLayout) { return $this->idLayout; }
        $this->idLayout = (int) self::DEFAULT_LAYOUT_ID;

        $row = $this->model->db->query(
            'SELECT id ' . NestedTree::TAB_LAYOUT . ' WHERE sheet_name = "' . $nameSheet . '"'
        )->getRow();

        if (isset($row)) { $this->idLayout = (int) $row->id; }
        return $this->idLayout;
    }

    private function getFiles(): array 
    {
        $dir = rtrim($this->path, '\\/ ') . DIRECTORY_SEPARATOR . 'body';
        if (! is_dir($dir)) { return []; }
        $result = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileInfo) {
            if ($fileInfo->isDir()) { continue; }
            if ($fileInfo->getExtension() != 'json') { continue; }
            $result[] = $files->getSubPathName(); //substr($info->getRealPath(), $len);
        }

        usort($result, function ($a, $b) { 
            $n1 = explode(DIRECTORY_SEPARATOR, $a);
            $n2 = explode(DIRECTORY_SEPARATOR, $b);
            if ($n1 === $n2) { return $a > $b; }
            return $n1 > $n2;
        });

        return $result;
    }
    
    private function start(): void
    {
        $this->log['import_start'] = meDate();
        $this->saveDataInJsonFile('log.json', $this->log);
        $this->idLayout = (int) 0;
    }

    private function complete(): void
    {
        $this->uploadResourceFiles();
        $this->log['import_end'] = meDate();
        $this->saveDataInJsonFile('log.json', $this->log);
        $this->idLayout = (int) 0;
    }

    private function saveDataInJsonFile(string $fileName, array $data): void
    {
        if (is_dir($this->path) === false) { return; }

        file_put_contents($this->path . DIRECTORY_SEPARATOR . $fileName, jsonEncode($data, false) . PHP_EOL, LOCK_EX);
    }

    private function getIdFromUrl(string $path): int
    {
        if ($path === '' || $path === '/' || $path === DIRECTORY_SEPARATOR) { $path = 'index'; }
        $file = NestedTree::PATH_INDEX . DIRECTORY_SEPARATOR . castingPath($path, true) . DIRECTORY_SEPARATOR . NestedTree::FILE_ID;
        $file = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file);
        if (! is_file($file)) { return (int) 0; }
        return include $file;
    }

    private function createNewClass(string $sheet): ?Object
    {
        if (! $modelName = getClassModelName($sheet)) { return null; }
        if (enum_exists($modelName, false)) { return null; }
        return new $modelName();
    }

    private function deleteDataFile(string $fileName): bool
    {
        return deleteFile($this->path . DIRECTORY_SEPARATOR . $fileName);
    }

    private function getDataFromJsonFile(string $fileName): array
    {
        $fileName = rtrim($this->path, '/\\ ') . DIRECTORY_SEPARATOR . $fileName;
        if (! is_file($fileName)) { return []; }
        if (! $jsonData = file_get_contents($fileName)) { return []; }
        return jsonDecode($jsonData);
    }

    // private function urlToName(string $url): string
    // {
    //     return checkFileName(str_replace('\\/', '_', $url));
    // }
}