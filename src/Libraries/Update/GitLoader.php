<?php 

declare(strict_types=1);
namespace Sygecon\AdminBundle\Libraries\Update;

use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\CLI\CLI;
use Config\App;
use Sygecon\AdminBundle\Config\Update as Config;
use Sygecon\AdminBundle\Libraries\HTML\WebDoc;
use PharData;
use Throwable;

use function trim;
use function rtrim;
use function count;
use function str_replace;
use function preg_replace;
use function strpos;
use function explode;
use function substr;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function unlink;
use function rename;
use function opendir;
use function readdir;
use function closedir;
use function is_dir;
use function dirname;
use function helper;
use function jsonDecode;
use function jsonEncode;
use function deletePath;
use function createPath;

final class GitLoader
{
    private const URL_PREFIX  = 'https://api.github.com/repos/';
    private const URL_SUFIX   = '/releases';

    public function __construct()
    {
        $config = new App();
        try {
            $request = new CLIRequest($config);
        } catch (Throwable $th) {
            throw new \ErrorException('Error! ' . lang('Admin.error.notHavePermission'));
        }

        helper('files');
    }

    public function build(bool $withReturn = true): array
    {
        $log = [];
        $result = [];
        if (file_exists(Config::LOG_FILE) === true) {
            $log = jsonDecode(file_get_contents(Config::LOG_FILE));
        }
        $count = 0;
        foreach(Config::$autoloader as $name => $value) {
            if (! $name) { continue; }
            if (! $value) { continue; }
            if (! $data = $this->getResponse($name)) { continue; }
            if (isset($data->tag_name) === false) { continue; }
            if (! $newVer = $this->clear($data->tag_name)) { continue; }

            $curVer = ($log[$name] ?? '');
            if ($this->checkVersion($curVer, $newVer) === true) {
                $res = false;
                $isZip = true;

                CLI::write('Found a new version of the library: ' . $name . ' ' . $curVer);

                $uri = (isset($data->zipball_url) ? $this->clear($data->zipball_url) : '');
                if ($uri) {
                    $fileArc = $this->arcFileName($name, $isZip);
                    $res = $this->downloadUrl($uri, $fileArc);
                }
                
                if ($res === false) {
                    $isZip = false;
                    $uri = (isset($data->tarball_url) ? $this->clear($data->tarball_url) : '');
                    if ($uri) {
                        $fileArc = $this->arcFileName($name, $isZip);
                        $res = $this->downloadUrl($uri, $fileArc);
                    }
                }

                if ($res === true && $this->decompress($fileArc, $name, $isZip)) { 
                    $log[$name] = (string) $newVer;
                    if ($withReturn === true) { $result[$name] = $value; }
                    ++$count;

                    CLI::write('A new version ' . $newVer . ' of the library has been uploaded: ' . $name);
                }
            }
        }

        deletePath(Config::WORK_FOLDER . 'tmp');
        file_put_contents(Config::LOG_FILE, jsonEncode($log, true), LOCK_EX);
        
        CLI::write('The process is complete! ' . $count . ' new versions of libraries uploaded');

        return $result;
    }

    private function getResponse(string $name = ''): ?object
    {
        $uri = self::URL_PREFIX . Config::normalizeUri($name) . self::URL_SUFIX;

        if (! $text = WebDoc::load($uri)) { return null; }
        if (! $data = jsonDecode((string) $text, false)) { return null; }
        return $data[0];
    }

    // Проверка версий
    private function checkVersion(string $curVer = '', string $newVer = ''): Bool
    {
        if (! $newVer) { return false; }
        if (! $curVer) { return true; }

        if (0 > $this->versionComparison($curVer, $newVer)) { return true; }
        return false;
    }

    private function clearVer(string $ver): string
    {
        $str = str_replace(['.alpha', '.beta', '.rc'], ['.1', '.2', '.3'], 
            str_replace([',', '-'], '.', $ver)
        );
        return trim(preg_replace('/[^0-9.]/i', '', $str), ' .');
    }

    private function versionComparison(string $ver1, string $ver2): int
    {
        $v1 = explode('.', $this->clearVer($ver1));
        $v2 = explode('.', $this->clearVer($ver2));
        $len1 = count($v1);
        
        if (! $len2 = count($v2)) {
            if ($len1 !== 0) { return 1; }
            return 0;
        } 
        if ($len1 === 0) { return -1; }

        if ($len1 < $len2) { 
            foreach($v1 as $key => $val) { 
                $n1 = (int) $val;
                $n2 = (int) $v2[$key];
                if ($n1 > $n2) { return 1; }
                if ($n2 > $n1) { return -1; }
            }
            return 0;
        }

        foreach($v2 as $key => $val) { 
            $n2 = (int) $val;
            $n1 = (int) $v1[$key];
            if ($n1 > $n2) { return 1; }
            if ($n2 > $n1) { return -1; }
        }
        return 0;
    }

    private function downloadUrl(string $uri, string $fileName): bool
    {
        $dir = Config::WORK_FOLDER . 'tmp';
        if (! createPath($dir)) { return false; }

        if (! $data = WebDoc::load($uri)) { return false; } 
        if (file_put_contents($dir . DIRECTORY_SEPARATOR . $fileName, $data, LOCK_EX)) { return true;}
        return false;
    }

    private function arcFileName(string $name, bool $isZip): string
    {
        return substr(hash('sha256', $name, false), 0, 12) . ($isZip ? '.zip' : '.tar.gz');
    }

    private function decompress(string $fileName, string $name, bool $isZip): bool
    {
        $pathDest = Config::pathWithVendor($name); 

        $dir = Config::WORK_FOLDER . 'tmp' . DIRECTORY_SEPARATOR;

        $path = rtrim(substr($fileName, 0, strpos($fileName, '.')), '.');
        $fileName = $dir . $fileName;
        if (file_exists($fileName) === false) { return false; }

        if ($isZip === false) {
            // декомпрессия из gz
            try { 
                $arc = new PharData($fileName);
                $arc->decompress(); // создание files.tar
                unlink($fileName); 
            } catch (Throwable $th) { 
                $isZip = false;
            }

            $fileName = $dir . $path . '.tar';
            if (file_exists($fileName) === false) { return false; }
        }
        
        // распаковка
        $dir .= $path;
        try { 
            $phar = new PharData($fileName);
            $phar->extractTo($dir);
            unlink($fileName); 
        } catch (Throwable $th) { 
            $isZip = false;
        }

        if (is_dir($dir) === false) { return false; }
        $result = false;
        // Перемещение
        if ($handle = opendir($dir)) {
            while (($path = readdir($handle)) !== false) {
                if ($path !== '.' && $path !== '..') { 
                    $fileName = $dir . DIRECTORY_SEPARATOR . $path;

                    if (is_dir($fileName) === true) {
                        if (is_dir($pathDest) === true) {
                            deletePath($pathDest);
                        } else if (is_dir(dirname($pathDest)) === false) {
                            createPath(dirname($pathDest));
                        }

                        $result = rename($fileName, $pathDest);
                        break;
                    }
                } 
            }
            closedir($handle);
            deletePath($dir);
        }
        
        return $result;
    }

    private function clear(string $text): string
    {
        return trim(trim($text), '."/');
    }
}
