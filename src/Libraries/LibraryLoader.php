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
        try {
            $configApp = new App();
            $request = new CLIRequest($configApp);
            $this->editor = new Compare();
        } catch (Throwable $th) {
            throw new \ErrorException('Error! ' . lang('Admin.error.notHavePermission'));
        }

        helper(['path', 'files', 'match']);
    }

    public function build(array $data = []): void
    {
        foreach($data as $name => $callback) {
            if ($callback && is_numeric($callback) === false) { 
                $callback .= 'Update';
                if (method_exists($this, $callback) === false) { continue; }
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
            $this->copyFiles($srcPath . $name, $dstPath . $name, [], true, true, false);
        }
    }

    // Copy Framework system files
    private function systemUpdate(string $name): void
    {
        $this->editor->setPath($name, 'app');
        $vendorPath = Config::pathWithVendor($name);
        if (is_dir($vendorPath) === false) { return; }
        $rootPath = ROOTPATH;

        // Copy Root files
        $this->copyFiles($vendorPath, $rootPath, Config::FILTER_FILENAME, true, true, false);
        
        $vendorPath .= DIRECTORY_SEPARATOR;
        copy(
            $vendorPath . 'public' . DIRECTORY_SEPARATOR . 'index.php', 
            $rootPath . 'public' . DIRECTORY_SEPARATOR . 'index.php'
        );
        copyPath($vendorPath . 'system', $rootPath . 'system');

        $vendorPath .= basename(APPPATH) . DIRECTORY_SEPARATOR . 'Config';
        $rootPath = APPPATH . 'Config' . DIRECTORY_SEPARATOR;
        
        $this->copyFiles($vendorPath, $rootPath, Config::FILTER_FRAMEWORK_CONFIG, false, false, false);
    }

    private function copyFiles(string $srcDir, string $dstDir, array $filter, bool $onlyFile, bool $notCompare, bool $deletePath): void
    {
        if (! $handle = opendir($srcDir)) { return; }
        while (($fileName = readdir($handle)) !== false) {
            if ($fileName !== '.' && $fileName !== '..') {
                $sourceFile = $srcDir . DIRECTORY_SEPARATOR . $fileName;
                $destFile = $dstDir . $fileName;

                if (is_dir($sourceFile) === true) {
                    if ($onlyFile === false) { 
                        CLI::write('Copying folder: ' . $fileName);
                        if ($deletePath === true) deletePath($destFile, false);
                        copyPath($sourceFile, $destFile); 
                    }
                } else
                if ($notCompare === false &&$filter && isset($filter[$fileName]) === true) { 
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
        closedir($handle);
    }
}