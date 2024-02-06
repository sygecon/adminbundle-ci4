<?php
declare(strict_types=1);
namespace Sygecon\AdminBundle\Libraries\Update;

use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\CLI\CLI;
use Config\App;
use Sygecon\AdminBundle\Config\Update as Config;
use Throwable;

final class Transaction
{
    public function __construct()
    {
        $config = new App();
        try {
            $request = new CLIRequest($config);
        } catch (Throwable $th) {
            throw new \ErrorException('Error! ' . lang('Admin.error.notHavePermission'));
        }
        helper(['path', 'files', 'match']);
    }

    public function build(array $data = []): void
    {
        foreach($data as $name => $callback) {
            if (! $callback || is_numeric($callback) === true) { continue; }
            $callback .= 'Update';
            if (method_exists($this, $callback) === false) { continue; }
            CLI::write('Performing file operations with the library: ' . $name);
            $this->$callback($name);
        }
    }

    // Copy Library config files
    private function configUpdate(string $name): void
    {
        $srcPath = Config::pathWithVendor($name) . 
            DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Config';
        if (is_dir($srcPath) === false) { return; }
        $srcPath .= DIRECTORY_SEPARATOR;
        $dstPath = APPPATH . 'Config' . DIRECTORY_SEPARATOR;

        foreach (Config::FILTER_CONFIG as $fileName => $value) {
            $src = $srcPath . $fileName;
            $dst = $dstPath . $fileName;

            if (is_dir($src) === true) { copyPath($src, $dst); }
            else
            if (file_exists($src .= '.php') === true) { 
                $dst .= '.php';
                if (! $value || is_array($value) === false) { copy($src, $dst); } 
                else 
                { $this->setConfig($value, $src, $dst); }
            }
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
            $this->copyFiles($srcPath . $name, $dstPath . $name, [], true);
        }
    }

    // Copy Framework system files
    private function systemUpdate(string $name): void
    {
        $vendorPath = Config::pathWithVendor($name);
        if (is_dir($vendorPath) === false) { return; }
        $rootPath = ROOTPATH;

        // Copy Root files
        $this->copyFiles($vendorPath, $rootPath, Config::FILTER_FILENAME, true);
        
        $vendorPath .= DIRECTORY_SEPARATOR;
        copy(
            $vendorPath . 'public' . DIRECTORY_SEPARATOR . 'index.php', 
            $rootPath . 'public' . DIRECTORY_SEPARATOR . 'index.php'
        );
        copyPath($vendorPath . 'system', $rootPath . 'system');

        $vendorPath .= basename(APPPATH) . DIRECTORY_SEPARATOR . 'Config';
        $rootPath = APPPATH . 'Config' . DIRECTORY_SEPARATOR;

        $this->copyFiles($vendorPath, $rootPath, Config::FILTER_FRAMEWORK_CONFIG, false);
    }

    private function copyFiles(string $srcDir, string $dstDir, array $filter, bool $onlyFile): void
    {
        if (! $handle = opendir($srcDir)) { return; }

        while (($fileName = readdir($handle)) !== false) {
            if ($fileName !== '.' && $fileName !== '..') {
                $sourceFile = $srcDir . DIRECTORY_SEPARATOR . $fileName;
                $destFile = $dstDir . $fileName;

                if (is_dir($sourceFile) === true) {
                    if ($onlyFile === false) { copyPath($sourceFile, $destFile); }
                } else
                if (! $filter || isset($filter[$fileName]) === false) { 
                    copy($sourceFile, $destFile); 
                } else {
                    $this->setConfig($filter[$fileName], $sourceFile, $destFile);
                }
            }
        }
        closedir($handle);
    }

    private function setConfig(array $data, string $srcFile, string $dstFile): void
    {
        if (file_exists($dstFile) === false) { 
            copy($srcFile, $dstFile);
            return;
        }
        if (! $data) { return; }
        if (is_array($data) === false) { return; }
        if (! $srcText = file_get_contents($srcFile)) { return; }
        
        if ($dstText = file_get_contents($dstFile)) {
            addUseClassToConfig($dstText, $srcText);

            foreach($data as $attr => $value) {
                if (! $attr) { continue; }
                $value = trim($value);
                $str = readParamFromConfig($dstText, $attr);
                if ($str === null) { continue; }

                if (! $str && is_numeric($str) === false) { $str = "''"; }
                $res = writeParamToConfig($srcText, $attr, $str);
                
                if ($res === false && $value && is_numeric($value) === false) {
                    $str = $value . ' $' . $attr . ' = ' . $str;
                    addParamToConfig($srcText, $str); 
                }
            }
        }
        file_put_contents($dstFile, $srcText, LOCK_EX);
    }
}