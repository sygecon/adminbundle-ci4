<?php 
namespace Sygecon\AdminBundle\Libraries\Control;
/**
 * Creates and provides access to a node tree of specific system directories.
 *
 * @author Panin Aleksei S <https://github.com/sygecon>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
*/
use Sygecon\AdminBundle\Config\Paths;
use Throwable;

final class Finder 
{
    protected array $config = [
        'controllers' => [
            'root' => APPPATH . 'Controllers',
            'filter' => 'Boot'
        ],
        'models' => [
            'root' => APPPATH . 'Models',
            'filter' => 'Boot'
        ],
        'library' => [
            'root' => APPPATH . 'Libraries', // . DIRECTORY_SEPARATOR . 'Control',
            'filter' => 'Boot'
        ],
        'layout' => [
            'root' => APPPATH . PATH_TEMPLATE . DIRECTORY_SEPARATOR . PATH_LAYOUT, // . DIRECTORY_SEPARATOR . 'Control',
            'filter' => ''
        ]
    ];

    private bool $asTree = true; // Как Tree дерево или List для Select Option

    private int $lenRoot = 0;
    private array $param = [];
    private $titleTab = null;

    public function get(string $var = 'controllers', bool $asTree = true, string $path = ''): array 
    {
        $id     = (int) 0;
        $path   = castingPath($path, true);
        $var    = castingPath($var, true);
        if (! $var) { return []; }
        if (! isset($this->config[$var])) { return []; }

        $this->asTree           = $asTree;
        $this->lenRoot          = 0;
        $this->param            = $this->config[$var];
        $this->param['root']    = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->param['root']);
        $this->lenRoot          = strlen($this->param['root']);

        $folder = ($path ? $this->param['root'] . DIRECTORY_SEPARATOR . $path : $this->param['root']);
        if ($this->param['filter']) {
            $this->param['filter'] = rtrim($this->param['root'], '\\/ ') . DIRECTORY_SEPARATOR . trim($this->param['filter'], '\\/ ');
        }
        if (! $this->lenRoot) { return []; }
        if (! is_dir($folder)) { return []; }

        $items = [];
        if($this->asTree === true) {
            $id = (int) 1;
            $dir = trim(substr($folder, $this->lenRoot), DIRECTORY_SEPARATOR);
            $value = (!empty($dir) ? str_replace(DIRECTORY_SEPARATOR, '\\', $dir) : '');
            $level = (!empty($dir) ? (int) substr_count($dir, DIRECTORY_SEPARATOR) : (int) 0);
            
            $items[] = [
                'id' => $id, 
                'parent' => (int) 0, 
                'value' => $value, 
                'name' => pathinfo($folder, PATHINFO_BASENAME), 
                'title' => $this->titleClassName($value), 
                'path' => pathinfo($folder, PATHINFO_DIRNAME), 
                'level' => $level, 
                'type' => 1
            ];   
            ++$level;
        }

        if(! $id = $this->listFolders($items, $folder, $id)) { return $items; }

        if($this->asTree === true) { 
            $this->listFiles($items, $id); 
            $this->multiSort($items, 'parent', SORT_ASC, SORT_NUMERIC);
            return $items;
        }
        $this->multiSort($items, 'group', SORT_ASC, SORT_STRING);

        return $items;
    }

    private function listFolders(array &$items, string $dir, int $id): int 
    {
        if (! $this->lenRoot) { return 0; }
        $dir = rtrim($dir, '\\/ ');
        if(! is_dir($dir)) { return 0; }
        if(! $handle = opendir($dir)) { return 0; }

        $parent = $this->getParent($items, $dir);
        $path = trim(substr($dir, $this->lenRoot), DIRECTORY_SEPARATOR);
        $val = ($path ? str_replace(DIRECTORY_SEPARATOR, '|', $path) : '');

        if($this->asTree === true) {
            $level = ($path ? (int) substr_count($path, DIRECTORY_SEPARATOR) : (int) 0);
            ++$level;
        } else {
            $group = ($path ? str_replace(DIRECTORY_SEPARATOR, '&#92;', $path) : ' ');
        }

        while(($name = readdir($handle)) !== false) {
            if($name === '.' || $name === '..') { continue; }

            $path = $dir . DIRECTORY_SEPARATOR . $name; 
            if(is_dir($path) === true) {
                if($this->param['filter'] === $path) { continue; }

                if($this->asTree === true) {
                    ++$id;
                    $value = (empty($val) ? $name : $val . '|' . $name);
                    $items[$id] = [
                        'id' => $id, 
                        'parent' => (int) $parent, 
                        'value' => str_replace('|', '\\', $value), 
                        'name' => $name, 
                        'title' => $this->titleClassName($value), 
                        'path' => $dir, 
                        'level' => $level, 
                        'type' => 1
                    ];
                }

                $this->listFolders($items, $path, $id);
            } else 
            if($this->asTree === false
                && strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'php'
                && $this->checkClassName($name) === true 
            ) {
                    $fileName = pathinfo($name, PATHINFO_FILENAME);
                    $value = (empty($val) ? $fileName : $val . '|' . $fileName);

                    $items[] = [
                        'value' => str_replace('|', '\\', $value), 
                        'group'=> $group, 
                        'title' => $this->titleClassName($value), 
                        'name' => $fileName
                    ];
            }
        }
        closedir($handle);

        return (int) $id;
    }

    private function listFiles(array &$items, int $id): void 
    {
        if(! count($items)) { return; }
        $files = [];

        foreach($items as $i => $folder) {
            $dir = $folder['path'] . DIRECTORY_SEPARATOR . $folder['name'];
            if(is_dir($dir)) {
                if($handle = opendir($dir)) {
                    $parent = (int) $folder['id'];
                    $path = trim(substr($dir, $this->lenRoot), DIRECTORY_SEPARATOR);
                    $val = (!empty($path) ? str_replace(DIRECTORY_SEPARATOR, '|', $path) : '');
                    $level = (!empty($path) ? (int) substr_count($path, DIRECTORY_SEPARATOR) : (int) 0);
                    ++$level;
                    while(($name = readdir($handle)) !== false) {
                        if($name === '.' || $name === '..') { continue; }
                        if($this->checkClassName($name) === false || is_file($dir . DIRECTORY_SEPARATOR . $name) === false) {
                            continue; 
                        }
                        if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'php') {
                            ++$id;
                            $fileName = pathinfo($name, PATHINFO_FILENAME);
                            $value = (empty($val) ? $fileName : $val . '|' . $fileName);
                            $files[$id] = [
                                'id' => $id, 
                                'parent' => $parent, 
                                'value' => str_replace('|', '\\', $value), 
                                'name' => $fileName, 
                                'title' => $this->titleClassName($value), 
                                'level' => $level, 
                                'type' => 2
                            ];
                        }
                    }
                    closedir($handle);
                }
            }
            if (isset($items[$i]['path'])) { unset($items[$i]['path']); }
        }
        if (count($files) !== 0) { $items = array_merge($items, $files); }
    }

    private function titleClassName(string $className = ''): string 
    {
        if (! $className) { return ''; }
        helper('path');
        if (! is_array($this->titleTab)) {
            try { 
                $this->titleTab = jsonDecode(baseReadFile(Paths::CLASS_TITLE)); 
            } catch (Throwable $th) { 
                $this->titleTab = []; 
                return $className;
            }
        }
        if (! isset($this->titleTab)) { return $className; }
        if (! $this->titleTab) { return $className; }

        $id = trim(str_replace(['\\', '/'], '|', $className), '|');
        if (isset($this->titleTab[$id])) { return $this->titleTab[$id]; }
        return $className;
    }

    private function getParent(array &$items, string $dir): int 
    {
        foreach($items as $item) {
            if (isset($item['path']) && $dir === $item['path'] . DIRECTORY_SEPARATOR . $item['name']) {
                return (int) $item['id']; 
            }
        }
        return (int) 0;
    }

    /**
     * Sorts the given array, based on the given sorts.
     * @return array
     */
    private function multiSort(array &$rows, string $field, int $direction, int $type) 
    {
        return array_multisort(array_column($rows, $field), $direction, $type, $rows);
    }

    private function checkClassName(string $className = ''): bool
    {
        if (! $str = basename(strtolower(trim($className)), '.php')) { return false; }
        return (bool) (in_array($str, Paths::FORBID_CLASS_NAMES) === false);
    }
}