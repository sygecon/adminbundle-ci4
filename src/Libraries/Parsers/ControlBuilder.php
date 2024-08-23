<?php

namespace Sygecon\AdminBundle\Libraries\Parsers;

use CodeIgniter\Config\Services;
use Sygecon\AdminBundle\Config\Paths;
use Throwable;

use function chr;
use function count;
use function explode;
use function trim;
use function ltrim;
use function rtrim;
use function strripos;
use function ucfirst;
use function substr_count;
use function substr;
use function strlen;
use function mb_substr;
use function mb_strpos;
use function mb_strrpos;
use function str_replace;
use function is_file;
use function is_string;
use function is_array;
use function in_array;
// use function realpath;
use function array_key_exists;
use function file_put_contents;
use function unlink;
use function jsonEncode;
use function command;
use function helper;
use function mb_ucfirst;
use function cleaningText;
// use function checkUrl;
use function castingPath;
use function toClassUrl;
use function jsonDecode;
use function baseReadFile;
use function baseWriteFile;
use function readingDataFromFile;
use function writingDataToFile;
use function toUrl;

final class ControlBuilder {

    private const NEEDLE_CLASS    = 'Boot';
    private const NAMESPACE       = 'App';
    private const CLASS_NAMESPACE = 'Controllers';
    private const TYPE_CLASS      = ['personal', 'group', 'first'];
    private const FILTER_CLASS    = ['model', 'models', 'boot', 'controller', 'controllers', 'app', 'errors', 'admin', 'base', 'basecontroller', 'admincontroller', 'appcontroller', 'basecontroller'];
    
    private const APP_ROUTE = APPPATH . 'Config' . DIRECTORY_SEPARATOR . 'Boot' . DIRECTORY_SEPARATOR . 'routes.php';

    private const ROUTE_PREFIX_LANG = '{locale}/';
    private const ROUTE_FORMAT      = '$routes->get' . "('%s', [%s::class, '%s']);\n";
    public const ROUTE_CALLBACK     = ['index', 'list'];
    
    private bool $addOneRoute       = true;
    private bool $multiLang         = false;

    private string $prefix          = '';

    private $titleTab               = null;

    /* Param String $Prefix */
    public function __construct(?string $prefix = null) 
    {
        helper('path');

        $this->addOneRoute  = true;
        $this->multiLang    = false;
        if (defined('SUPPORTED_LOCALES') && is_array(SUPPORTED_LOCALES) && count(SUPPORTED_LOCALES) > 1) {
            $this->multiLang = true;
            if (defined('DEFAULT_LANGUAGE_IS_EMPTY')) {
                $this->addOneRoute = DEFAULT_LANGUAGE_IS_EMPTY;
            }
        }

        if ($prefix !== null) { $this->prefix = $prefix; }
    }

    public function meLinkToRoute(string $slug = '', string $control = '', string $callback = 'index', string $segment = ''): string 
    {
        $result = '';
        if (! $control) return $result;
        
        if (! $callback) {
            $callback = (! $segment ? self::ROUTE_CALLBACK[0] : self::ROUTE_CALLBACK[1]);
        } else 
        if (! $segment && $callback !== self::ROUTE_CALLBACK[0]) {
            $segment = '(:any)';
        }
        if ($segment) $callback .= '/$1';
        
        $contrl = '\\' . self::NAMESPACE . '\\' . self::CLASS_NAMESPACE . '\\' . $control;
        
        if ($this->addOneRoute === true) {
            $class = $slug;
            if ($segment) $class .= '/';
            $class .= $segment;
            $result .= sprintf(self::ROUTE_FORMAT, $class, $contrl, $callback);
        }

        if ($this->multiLang) {
            $class = self::ROUTE_PREFIX_LANG . $slug;
            if ($slug && $segment) $class .= '/';
            $class .= $segment;
            $result .= sprintf(self::ROUTE_FORMAT, $class, $contrl, $callback);
        }
        return $result;
    }

    /* Наименование из базы как Контроллер  */
    public function baseToName(string $name = '', string $sep = '|'): string 
    {
        return trim(str_replace('_', $sep, $name), $sep . ' ');
    }

    /* Создание Контроллера */
    public function insert(array $data = []): bool 
    {
        if (isset($data['name']) === false || $this->checkClassName($data['name']) === false) {
            return false;
        }

        $className = $this->checkUrl($data['name']);
        if ($this->getFile($className) === true) { return false; }

        if ($this->createController($className, (isset($data['model']) ? $data['model'] : ''))) {
            $buf = (isset($data['title']) ? mb_ucfirst(cleaningText($data['title'])) : '');
            $this->titleClassName($this->classToName($this->nameToClass($className)), $buf);
            return true;
        }
        return false;
    }

    /* Изменение Контроллера */
    public function update(string $name = '', array $data = []): bool 
    {
        if (! $name) { return false; }
        $fileName = $this->getFile($name);
        if (is_file($fileName) === false) { return false; }
        if (array_key_exists('title', $data) === false) {
            if (isset($data['data_control']) === false) { return false; }

            if (file_put_contents($fileName, trim($data['data_control']) . PHP_EOL, LOCK_EX) !== false) { 
                return true; 
            }
            return false;
        }

        if ($data['title'] === 'delete') { $data['title'] = ''; }
        $this->titleClassName($name, $data['title']);

        // Переименование
        if (isset($data['name']) === false || $this->checkClassName($data['name']) === false) {
            return true;
        }
        if (! $newFile = $this->urlToFileName($data['name'])) { return true; }
        if (strtolower($newFile) === strtolower($fileName)) { return true; }

        $go = false;
        
        $oldClass = $this->nameToClass($name);
        $newClass = $this->nameToClass($data['name']);

        $e = strrpos($oldClass, DIRECTORY_SEPARATOR); 
        $oldNameSpace = substr($oldClass, 0, $e);
        $oldName = substr($oldClass, $e + 1);

        $e = strrpos($newClass, DIRECTORY_SEPARATOR); 
        $newNameSpace = substr($newClass, 0, $e);
        $newName = substr($newClass, $e + 1);

        $changeNameSpace = (strtolower($newNameSpace) !== strtolower($oldNameSpace) ? true: false);
        $go = $changeNameSpace;
        $rename = (strtolower($newName) !== strtolower($oldName) ? true: false);
        if ($rename === true) { $go = true; }
        
        if ($go === false) { return true; }

        $dir = dirname($newFile);
        if (is_dir($dir) === false) { 
            helper('files');
            createPath($dir);
        }
        if (is_dir($dir) === false) { return true; }
        if (! rename($fileName, $newFile)) { return true; }

        if ($dataFile = readingDataFromFile($newFile)) {
            if ($pos = mb_strpos($dataFile, '{')) { 
                $buffer = mb_substr($dataFile, 0, $pos);
                $buffer = str_replace(' ;', ';', str_replace('  ', ' ', $buffer));
                $old = [];
                $new = [];
                if ($changeNameSpace === true) {
                    $old[] = 'namespace ' . $oldNameSpace . ';';
                    $new[] = 'namespace ' . $newNameSpace . ';';
                }
                if ($rename === true) {
                    $old[] = 'class ' . $oldName;
                    $new[] = 'class ' . $newName;
                }

                $buffer = str_replace($old, $new, $buffer);
                $buffer .= mb_substr($dataFile, $pos);

                if (writingDataToFile($newFile, $buffer) === true) {
                    if ($buffer = readingDataFromFile(self::APP_ROUTE)) {
                        $old = [
                            chr(39) . $oldClass . '::',
                            chr(39) . str_replace('/', '\\', toClassUrl($name)) . '::'
                        ];
                        $new = [
                            chr(39) . $newClass . '::',
                            chr(39) . str_replace('/', '\\', toClassUrl($data['name'])) . '::'
                        ];
                        if ($buffer = str_replace($old, $new, $buffer)) {
                            writingDataToFile(self::APP_ROUTE, $buffer, false);
                        }
                    }
                }
            }
        }

        if ($data['title']) {
            $this->titleClassName($data['name'], $data['title']);
        }
        return true;
    }

    /* Удаление Контроллера */
    public function delete(string $url = ''): bool 
    {
        if (! $name = $this->checkUrl($url)) { return false; }
        $fileName = $this->getFile($name);
        if (is_file($fileName))
        try {
            unlink($fileName);
            $fileName = $this->classToName($this->nameToClass($name));
            $this->titleClassName($fileName, 'delete');
            // Удаление строки с контроллером из файла Роутера
            $this->removeClassFromRoute($name);
            return true;
        } catch (Throwable $th) { return false; }
    }

    /* Добавление Url в файл Роутера  */
    public function addUrlToRoute(string $url = '', string $name = '', string $suffix = '', bool $isList = true): void 
    {
        if (! $name) return;
        if (in_array(strtolower($name), self::FILTER_CLASS)) return;
        if (! $class = str_replace('/', '\\', toClassUrl(trim($name, '/\\'), $suffix))) return; 

        $url        = trim(toUrl($url), '/ ');
        $dataRoute  = readingDataFromFile(self::APP_ROUTE);
        $change     = false;

        if (mb_strpos($dataRoute, chr(39) . $url . chr(39)) === false) { 
            $dataRoute .= $this->meLinkToRoute($url, $class, self::ROUTE_CALLBACK[0]);
            $change = true; 
        }

        if ($isList === true && mb_strpos($dataRoute, chr(39) . $url . '/') === false) { 
            $dataRoute .= $this->meLinkToRoute($url, $class, self::ROUTE_CALLBACK[1]);
            $change = true;
        }

        if ($change === true) {
            $dataRoute .= "\n";
            writingDataToFile(self::APP_ROUTE, $dataRoute, false); 
        }
    }

    /* Удаление Url из файла Роутера  */
    public function removeUrlToRoute(string $url = ''): void 
    {
        $url = trim($url, '/ ');
        if ($url === '' || $url === 'index') { $url = '/'; }
        $url = chr(39) . $url;
        $dataRoute = readingDataFromFile(self::APP_ROUTE);
        $write = false;
            
        $this->removingLinesFromText($url . chr(39), $dataRoute, $write);
        if ($url === '/') { $url = ''; }
        $this->removingLinesFromText($url . '/', $dataRoute, $write);

        if ($write === true) { writingDataToFile(self::APP_ROUTE, $dataRoute, false); }
    }

    /**
     * @return string The path to the file, or '' if not found.
     */
    public function getFile(string $url = ''): string 
    {
        $fileName = $this->urlToFileName($url);
        if ($fileName && is_file($fileName) === true && is_readable($fileName) === true) { return $fileName; }
        return '';
    }

    public function nameToClass(string $name = ''): string 
    {
        $classname = toClassUrl($name);
        if (! $classname) { return ''; }
        return self::NAMESPACE . '\\' . self::CLASS_NAMESPACE . '\\' . str_replace('/', '\\', $classname);
    }

    /* Поиск по URL */
    public function find(?string $url = null): array 
    {
        $result = [];
        if (is_string($url)) {
            $result = ['class' => '', 'file' => '', 'type' => '', 'level' => 0];
            $listLink = explode('/', $this->checkUrl($url));
            if (count($listLink) === 1 && empty($listLink[0])) { return $result; }
            $filtrClass = [$this->isFullClass($listLink, null), $this->isFullClass($listLink, true), $this->isFullClass($listLink, false)];
        }
        $needle = '\\' . self::CLASS_NAMESPACE . '\\' . self::NEEDLE_CLASS . '\\';
        $levelParent = substr_count('\\', trim(self::NAMESPACE, '\\') . '\\' . trim(self::CLASS_NAMESPACE, '\\') . '\\');
        $locator = Services::locator();
        $files = $locator->listNamespaceFiles(self::NAMESPACE, self::CLASS_NAMESPACE);
        foreach ($files as &$file) {
            if (! is_file($file)) { continue; }
            if (! $classnameOrEmpty = $locator->getClassname($file)) { continue; }
            if (strripos($classnameOrEmpty, $needle) !== false) { continue; }
            $level = (substr_count('\\', trim($classnameOrEmpty, '\\')) - $levelParent);
            /** @var class-string $classname */
            $classname = $classnameOrEmpty;
            $name = $this->classToName($classnameOrEmpty);
            if (in_array(strtolower($name), self::FILTER_CLASS)) { continue; }
            if (isset($filtrClass)) {
                if ($filtrClass[0] === $classname) {
                    $result = ['class' => $classname, 'file' => $file, 'type' => self::TYPE_CLASS[0], 'level' => $level];
                    break;
                } else if ($filtrClass[1] === $classname) {
                    $result = ['class' => $classname, 'file' => $file, 'type' => self::TYPE_CLASS[1], 'level' => $level];
                    break;
                } else if ($filtrClass[2] === $classname) {
                    $result = ['class' => $classname, 'file' => $file, 'type' => self::TYPE_CLASS[2], 'level' => $level];
                    break;
                }
            } else {
                $result[] = ['name' => $name, 'title' => $this->titleClassName($name), 'level' => $level];
            }
        }
        return $result;
    }

    public function titleClassName(string $className = '', ?string $title = null): string 
    {
        if (! is_array($this->titleTab)) {
            try { $this->titleTab = jsonDecode(baseReadFile(Paths::CLASS_TITLE)); } 
            catch (Throwable $th) {
                if ($title === null) { return ''; }
                $this->titleTab = [];
            }
        }
        if ($className && isset($this->titleTab) && is_array($this->titleTab)) {
            if ($title !== null) {
                if (isset($this->titleTab[$className])) {
                    if ($this->titleTab[$className] === $title) { return ''; } 
                    else if ($title === 'delete') { unset($this->titleTab[$className]); }
                }
                if ($title !== 'delete') { $this->titleTab[$className] = $title; }
                
                baseWriteFile(Paths::CLASS_TITLE, jsonEncode($this->titleTab, false));
            } else if (isset($this->titleTab[$className])) {
                return $this->titleTab[$className];
            }
        }
        return '';
    }

    private function urlToClass($links): string 
    {
        $class = '';
        if (is_array($links)) { 
            $array = $links; 
        } else 
        if (is_string($links)) { 
            $array = explode('/', $links); 
        }

        if (count($array) === 1 && empty($array[0])) { 
            unset($array[0], $array); 
        } else {
            $len = count($array);
            --$len;
            foreach ($array as $i => $link) {
                if (!empty($link)) {
                    if ($len === $i) { $class .= $this->prefix; }
                    $class .= ucfirst($link) . '\\';
                }
                unset($array[$i]);
            }
            unset($array);
        }
        return (!$class) ? $this->prefix . 'Index' : trim($class, '\\');
    }

    private function isClass(array &$listLink, ?bool $isParentPath): string 
    {
        if (! $len = count($listLink)) return $this->prefix . 'Index';
        if ($isParentPath === false) return $this->urlToClass([$listLink[0]]); 
        --$len;
        $links = $listLink;
        if ($isParentPath === true) unset($links[$len]);
        return $this->urlToClass($links);
    }

    private function isFullClass(array &$listLink, ?bool $isParentPath): string 
    {
        $class = $this->isClass($listLink, $isParentPath);
        return (empty($class) ? self::NAMESPACE . '\\' . self::CLASS_NAMESPACE : self::NAMESPACE . '\\' . self::CLASS_NAMESPACE . '\\' . $class);
    }

    private function checkUrl(string $url): string 
    {
        if (! $url = toClassUrl($url, '')) return '';

        $typeClass = self::CLASS_NAMESPACE;
        $len = strlen(self::NAMESPACE) + 1;
        if (substr($url, 0, $len) === self::NAMESPACE . '/') $url = substr($url, $len);
        $len = strlen($typeClass);
        ++$len;

        if (substr($url, 0, $len) === $typeClass . '/') $url = substr($url, $len);
        if (substr($url, 0, strlen(self::NEEDLE_CLASS)) === self::NEEDLE_CLASS) return '';
        return trim($url, '/');
    }

    private function classToName(string $class): string 
    {
        return str_replace('\\', '|', trim(substr($class, strlen(self::NAMESPACE . '\\' . self::CLASS_NAMESPACE . '\\')), '\\' . ' '));
    }

    private function createController(string $name, string $model): bool 
    {
        if ($name === '') { return false; }
        $command = 'make:app-controller ' . $name;
        try {
            if ($model) { $command .= ' --model ' . $model; }
            command($command);
            return true; 
        } catch (Throwable $th) { return false; }
    }

    /* Хранение в Базе / Файл Макета
        private function nameToBase(string $name = ''): string 
        {
            return trim(str_replace(['|', '/', '\\'], '_', $name), '_ ');
        }
        Removing lines from text
    */
    private function removingLinesFromText(string $search, string &$data, bool &$result): void
    {
        while ($pos = mb_strpos($data, $search)) {
            ++$pos;
            $start = mb_strrpos(mb_substr($data, 0, $pos), '$routes->') - 1;
            if ($pos > $start && $start > 4) {
                $pos = mb_strpos($data, ';', $pos);
                if ($pos > $start) {
                    $result = true;
                    ++$pos;
                    $data = rtrim(mb_substr($data, 0, $start)) . "\n" . ltrim(mb_substr($data, $pos));
                }
            }
        }
    }

    /* Delete string with Class Controller from file Route */
    public function removeClassFromRoute(string $name): void 
    {
        if (! $class = toClassUrl(trim($name, '/\\'))) { return; }
        $dataRoute = readingDataFromFile(self::APP_ROUTE);

        $write = false;
        $this->removingLinesFromText(chr(39) . $class . '::', $dataRoute, $write);
        $class = '\\' . self::NAMESPACE . '\\' . self::CLASS_NAMESPACE . '\\' . $class;
        $this->removingLinesFromText(chr(39) . $class . '::', $dataRoute, $write);

        if ($write === true) { writingDataToFile(self::APP_ROUTE, $dataRoute, false); }
    }

    /* Url class to File Name */
    private function urlToFileName(string $url): string
    {
        if (! $url) { return ''; }
        $file = castingPath(toClassUrl($url), true);
        if (strripos($file, self::CLASS_NAMESPACE . DIRECTORY_SEPARATOR . self::NEEDLE_CLASS . DIRECTORY_SEPARATOR) !== false) {
            return APPPATH . $file . '.php';
        }
        return APPPATH . self::CLASS_NAMESPACE . DIRECTORY_SEPARATOR . $file . '.php';
            
    }

    private function checkClassName(string $className): bool
    {
        if (! $str = strtolower(toClassUrl($className))) { return false; }
        return (bool) (in_array($str, Paths::FORBID_CLASS_NAMES) === false);
    }
}
