<?php
namespace Sygecon\AdminBundle\Controllers\Api;

use CodeIgniter\Controller;
use Sygecon\AdminBundle\Config\Paths;
use Sygecon\AdminBundle\Config\PageTypes;

use Throwable;

use function helper;
use function checkFileName;
use function castingPath;
use function decodeTextBase64;
use function toUrl;
use function user_id;
use function cleaningText;

use function copyFile;
use function renameFile;
use function deletePath;
use function createPath;

use function array_shift;
use function jsonEncode;
use function array_splice;
use function explode;
use function implode;
use function in_array;
use function strtolower;
use function ucfirst;
use function pathinfo;
use function filesize;
use function file_exists;
use function file_put_contents;
use function is_file;
use function is_dir;
use function opendir;
use function readdir;
use function closedir;
use function rename;
//use function getPathTheme;

final class AspFm extends Controller 
{
    protected string $link = '';
    protected string $path = '';
    protected string $type = '';
    protected string $root = '';
    protected bool $isUser = false;

    protected array $postData = [];

    protected $helpers = ['files'];

    // public function initController(
    //     \CodeIgniter\HTTP\RequestInterface $request,
    //     \CodeIgniter\HTTP\ResponseInterface $response,
    //     \Psr\Log\LoggerInterface $logger
    // ) {
    //     parent::initController($request, $response, $logger);
    // }

    public function me_files(): string
    {
        $fileData = ['folders' => [], 'files' => []];
        if ($this->getRequest()) {
            $this->getDir($fileData);
            if (isset($this->postData['get_filter']))
                $fileData['filter'] = PageTypes::FILE_FILTER_EXT;
        }
        return $this->successfulResponse($fileData, true);
    }

    public function me_rename() 
    {
        if ($this->getRequest()) {
            if (isset($this->postData['fname']) && isset($this->postData['fname_new'])) {
                $result = renameFile($this->path, $this->postData['fname'], $this->postData['fname_new']);
                return $this->successfulResponse($result ? $result : false);
            }
        }
        return $this->successfulResponse(false);
    }

    public function me_copy(): string 
    {
        $fileData = [];
        if ($this->getRequest()) {
            if (isset($this->postData['fname']) && isset($this->postData['source'])) {
                $sourcePath = $this->getPath(castingPath($this->postData['source'], true));
                unset($this->postData['source']);
                if ($this->path !== $sourcePath) {
                    $move = false;
                    if (isset($this->postData['delete'])) {
                        unset($this->postData['delete']);
                        $move = true;
                    }
                    $sourcePath .= DIRECTORY_SEPARATOR;
                    $filter = PageTypes::FILE_FILTER_EXT[$this->type];
                    foreach ($this->postData['fname'] as $id => $val) {
                        $fn = checkFileName($val);
                        if (copyFile($sourcePath . $fn, $this->path, $move)) {
                            $fn = $this->path . DIRECTORY_SEPARATOR . $fn;
                            $fileInfo = pathinfo($fn);
                            if ($fdata = $this->setFileData($fileInfo, filesize($fn), $filter)) {
                                $fileData[] = $fdata;
                                // $fileData[] = ['name' => $fileInfo['filename'], 'ext' => mb_strtolower($fileInfo['extension']), 
                                //     'size' => $fs, 'src' => $this->link . $fileInfo['filename']
                                // ];
                            } else if (is_file($fn)) { deletePath($fn); }
                            array_splice($fileInfo, 0);
                        }
                        unset($this->postData['fname'][$id]);
                    }
                    unset($this->postData['fname'], $fileInfo);
                }
            }
        }
        return $this->successfulResponse($fileData, true);
    }

    public function me_upload(): string 
    {
        $fileData = [];
        helper('request');
        if ($this->getRequest()) {
            if (isset($this->postData['fname']) && isset($this->postData['fdata'])) {
                $filter = PageTypes::FILE_FILTER_EXT[$this->type];
                foreach ($this->postData['fname'] as $id => $val) {
                    $fn = castingPath($val, true);
                    $data = decodeTextBase64($this->postData['fdata'][$id]);
                    if (isset($data) && isset($fn) && $fn && $data) {
                        $fn = $this->path . DIRECTORY_SEPARATOR . $fn;
                        deletePath($fn . '.bak');
                        if (file_exists($fn)) rename($fn, $fn . '.bak');
                        if (file_put_contents($fn, $data . PHP_EOL, LOCK_EX) !== false) {
                            $fileInfo = pathinfo($fn);
                            if ($fdata = $this->setFileData($fileInfo, filesize($fn), $filter)) {
                                $fileData[] = $fdata;
                            }
                            array_splice($fileInfo, 0);
                        } else if (file_exists($fn . '.bak')) {
                            rename($fn . '.bak', $fn);
                        }
                    }
                    unset($this->postData['fdata'][$id], $this->postData['fname'][$id], $data);
                }
                unset($this->postData['fdata'], $this->postData['fname']);
            }
        }
        return $this->successfulResponse($fileData, true);
    }

    public function me_delete(): bool 
    {
        if ($this->getRequest()) {
            if (is_array($this->postData['fname'])) {
                foreach ($this->postData['fname'] as $fname) {
                    deletePath($this->path . DIRECTORY_SEPARATOR . $fname);
                }
                return $this->successfulResponse(true);
            }
        }
        return $this->successfulResponse(false);
    }

    public function me_create() 
    {
        if ($this->getRequest()) {
            if (isset($this->postData['fname'])) {
                $name = checkFileName($this->postData['fname']);
                unset($this->postData['fname']);
                if ($name) {
                    if (isset($this->postData['ext'])) {
                        $name .= '.' . strtolower(checkFileName($this->postData['ext']));
                        unset($this->postData['ext']);
                        if (createFile($this->path . DIRECTORY_SEPARATOR . $name, '')) {
                            return $this->successfulResponse($this->link . $name);
                        }
                    } else {
                        return $this->successfulResponse(createPath($this->path . DIRECTORY_SEPARATOR . $name));
                    }
                }
            }
        }
        return $this->successfulResponse(false);
    }

    private function getPath(string $path = ''): string 
    {
        if (! $this->type) return '';

        $curPath = FCPATH;
        $this->isUser = false;

        if (! $this->postData['pip']) return $curPath;

        $buf = cleaningText($this->postData['pip']);
        if ($buf !== SLUG_ADMIN) { /// ! Если не из админки ...
            $id = user_id();
            if (isset($id) === true && $id && is_numeric($id) === true) 
                $curPath = Paths::byUserID((int) $id);
            else
                return '';
        }

        // helper('path');
        // baseWriteFile('log.txt', $this->root);

        if ($this->root !== '') {
            $curPath .= $this->root . DIRECTORY_SEPARATOR;
            $this->link = '/' . toUrl($this->root) . '/';
        }

        if ($this->link === '') { $this->link = '/'; }

        if ($root = (PageTypes::FILE_PATH[$this->type] ?? '')) {
            $root = castingPath($root, true);
            if ($path && strtolower(substr($path, 0, strlen($root))) === strtolower($root)) {
                $root = '';
            } else {    
                $curPath .= $root;
                $this->link .= toUrl($root) . '/';
            }
        }

        if ($path) {
            $curPath .= DIRECTORY_SEPARATOR . $path;
            $this->link .= toUrl($path) . '/';
        }
        $this->link = str_replace('//', '/', $this->link);
        // baseWriteFile('log-1.txt', $this->link . ' = ' . $curPath);
        return $curPath;
    }

    private function getRequest(): bool 
    {
        $this->path = '';
        $this->link = '';
        $this->type = '';
        if (! $this->postData = $this->request->getPost()) { return false; }
        if (! isset($this->postData['pip'])) { return false; }
        if (! isset($this->postData['type'])) { return false; }

        $buf = '';
        if (isset($this->postData['path'])) {
            $buf = castingPath($this->postData['path'], true);
            unset($this->postData['path']);
        }
        
        $type = explode('-', checkFileName($this->postData['type']));
        unset($this->postData['type']);

        if ($type !== []) {
            $this->type = array_shift($type);
            if ($type !== []) {
                if ($type[0] == 'top') { array_shift($type); }
                $this->root = implode(DIRECTORY_SEPARATOR, $type);
            } else if ($this->type === 'image_pageicon') { 
                $this->root = Paths::PAGE_ICONS; 
            }
            // else {
            //     $this->root = getPathTheme(DIRECTORY_SEPARATOR);
            // }
        }
        if (! $this->type) { return false; }

        $this->path = $this->getPath($buf);
        return true;
    }

    private function getDir(&$fileData): void 
    {
        if (! $this->type) { return; }
        if (! $this->path) { return; }
        if (! $filter = PageTypes::FILE_FILTER_EXT[$this->type] ?? '') { return; }

        try {
            if ($handle = opendir($this->path)) {
                $sourceDir = $this->path . DIRECTORY_SEPARATOR;
                while (($file = readdir($handle)) !== false) {
                    if ($file[0] !== '.') {
                        $fileInfo = pathinfo($file);
                        if (is_dir($sourceDir . $file)) {
                            $fname = $fileInfo['filename'];
                            $fileData['folders'][] = ['name' => $fname, 'title' => ucfirst($fname)];
                        } else if ($fdata = $this->setFileData($fileInfo, filesize($sourceDir . $file), $filter)) {
                            $fileData['files'][] = $fdata;
                        }
                        array_splice($fileInfo, 0);
                    }
                }
                closedir($handle);
            } else {
                createPath($this->path);
            }
        } catch (Throwable $th) { return; }
    }

    private function setFileData(&$fileInfo, $filSize, $filter): ?array 
    {
        $ext = strtolower($fileInfo['extension']);
        $name = $fileInfo['filename'];
        if ($ext === 'js' || $ext === 'css' || $ext === 'scss') {
            $src = strtolower(PageTypes::THEME_MINIFY_FILE_NAME . '.');
            if (strtolower(substr($name, 0, strlen($src))) === $src) { return false; }
        }
        if (in_array($ext, $filter)) {
            $fsize = (!$filSize ? (int) 0 : $filSize);
            $src = $this->link . $name;
            if (!$this->isUser) { $src .= '.' . $ext; }
            return [
                'name' => $name,
                'ext' => $ext,
                'size' => $fsize,
                'src' => $src
            ];
        }
        return false;
    }

    private function successfulResponse(mixed $response, bool $IsEncode = false): string
	{
		$result = ($IsEncode === true ? jsonEncode($response, false) : $response);
        if (! $this->request->isAJAX()) return $result;
		return jsonEncode(['status' => 200, 'message' => $result], false);
	}
}
