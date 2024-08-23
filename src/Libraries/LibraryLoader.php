<?php
declare(strict_types=1);
/**
 * @author Panin Aleksei S <https://github.com/sygecon>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 */
namespace Sygecon\AdminBundle\Libraries;

use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\CLI\CLI;
use Config\App;
use Config\Boot\Update as Config;
use App\Libraries\Loader\Compare;
use Throwable;

final class LibraryLoader
{
    private $editor = null;

    public function __construct()
    {
        $config = new App();
        try {
            $request = new CLIRequest($config);
        } catch (Throwable $th) {
            throw new \ErrorException('Error! ' . lang('Admin.error.notHavePermission'));
        }
        helper(['path', 'files', 'match']);

        $this->editor   = new Compare();
    }

    public function build(array $data = []): void
    {
        $lastProc = false;
        foreach($data as $name => $callback) {
            if ($name === Config::IS_LAST_PROCESS) {
                $lastProc = true;
            } else {
                $this->makeProc($name, $callback);
            }
        }
        $this->frameworkUpdate($lastProc);
    }

    public function make(string $filter = '*'): void
    {
        $lastProc = false;
        foreach(Config::AUTOLOADER as $name => $callback) {
            if ($name === $filter || $filter === '*') {
                if ($name === Config::IS_LAST_PROCESS) {
                    $lastProc = true;
                } else {
                    $this->makeProc($name, $callback);
                }
            }
        }
        $this->frameworkUpdate($lastProc);
    }

    private function frameworkUpdate(bool $lastProc): void
    {
        if (! $lastProc) return;
        try {
            $this->systemUpdate();
        } catch (Throwable $th) {
            CLI::write('Error: ' . $th->getMessage());
        }
    }

    private function makeProc(string $name, mixed $callback): void
    {
        if ($callback && is_numeric($callback) === false) { 
            $callback .= 'Update';
            if (method_exists($this, $callback) === true) { 
                CLI::write('Performing file operations with the library: ' . $name);
                $this->$callback($name);
            }
        }
    }

    // Copy Library config files
    private function configUpdate(string $name): void
    {
        $this->editor->setPath($name, 'src')->namespaceCopy()->comClear();
        foreach (Config::FILTER_CONFIG as $fileName => $value) {
            CLI::write('Comparison of configuration file data: ' . $fileName);
            $this->editor->prepare($fileName)->make($value);
        }
    }

    private function publicUpdate(string $name): void
    {
        return;
        $src    = Config::pathWithVendor($name) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR;
        $dst    = ROOTPATH . 'public' . DIRECTORY_SEPARATOR;
        $list   = [
            'control' . DIRECTORY_SEPARATOR . 'assets' => true,
            'images' => false, 
            'vendors' => true
        ];

        foreach ($list as $path => $isDelele) {
            $srcPath  = $src . $path;
            $dstPath  = $dst . $path;
            $this->copyFiles($srcPath, $dstPath, [], false, true, $isDelele);
        }
    }

    // Copy Translations files
    private function langUpdate(string $name): void
    {
        $srcPath = Config::pathWithVendor($name) . DIRECTORY_SEPARATOR . 'Language';
        if (is_dir($srcPath) === false) { return; }
        $srcPath .= DIRECTORY_SEPARATOR;
        $dstPath = APPPATH . 'Language' . DIRECTORY_SEPARATOR;

        $data = [];
        if (defined('SUPPORTED_LOCALES') === true) {
            $data = array_keys(SUPPORTED_LOCALES);
        } else {
            $appConf = new App();
            $data = $appConf->supportedLocales;
        }

        foreach ($data as $name) {
            if (is_dir($srcPath . $name)) {
                $this->copyFiles($srcPath . $name, $dstPath . $name, [], true, true, false);
            }
        }
    }

    // Copy Framework system files
    private function systemUpdate(): void
    {
        $name = Config::IS_LAST_PROCESS;
        $this->editor->setPath($name, 'app');
        $vendorPath = Config::pathWithVendor($name);
        if (is_dir($vendorPath) === false) { return; }
        $rootPath = ROOTPATH;
        $src = $vendorPath . DIRECTORY_SEPARATOR;
        $dst = $rootPath;

        // copy Config path
        $srcApp = $src . basename(APPPATH) . DIRECTORY_SEPARATOR . 'Config';
        $dstApp = APPPATH . 'Config';
        createPath($dstApp);
        $dstApp .= DIRECTORY_SEPARATOR;
        $this->copyFiles($srcApp, $dstApp, Config::FILTER_FRAMEWORK_CONFIG, false, false, false);
        
        // copy System path
        copyPath($src . 'system', $dst . 'system', false);
        
        // Copy Root files
        $this->copyFiles($vendorPath, $rootPath, Config::FILTER_FILENAME, true, true, false);
        copy(
            $src . 'public' . DIRECTORY_SEPARATOR . 'index.php', 
            $dst . 'public' . DIRECTORY_SEPARATOR . 'index.php'
        );
    }

    private function copyFiles(string $srcDir, string $dstDir, array $filter, bool $onlyFile, bool $notCompare, bool $deletePath): void
    {
        $sourceDir  = rtrim($srcDir, '\\/ ');
        $destDir    = rtrim($dstDir, '\\/ ') . DIRECTORY_SEPARATOR;
        if (is_dir($sourceDir) === false) return;

        foreach (new \DirectoryIterator($sourceDir) as $fileInfo) {
            if ($fileInfo->isDot() === true) continue;

            $fileName   = $fileInfo->getFilename();
            $sourceFile = $sourceDir . DIRECTORY_SEPARATOR . $fileName;
            $destFile   = $destDir . $fileName;

            if ($fileInfo->isDir() === true) {
                if ($onlyFile === false) { 
                    CLI::write('Copying folder: ' . $fileName);
                    if ($deletePath === true) deletePath($destFile, false);
                    copyPath($sourceFile, $destFile); 
                }
            } else 
            if ($fileInfo->isFile() === true) {
                if ($notCompare === false && $filter && isset($filter[$fileName]) === true) { 
                    CLI::write('Comparison of configuration file data: ' . $fileName);
                    $this->editor
                        ->prepare(basename($fileName))
                        ->make($filter[$fileName]);
                } else {
                    CLI::write('Copying configuration file: ' . $fileName);
                    copy($sourceFile, $destFile); 
                }
            }
        }
    }
}