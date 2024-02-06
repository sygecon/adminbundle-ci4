<?php
namespace Sygecon\AdminBundle\Controllers\Api;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;
use Sygecon\AdminBundle\Config\Paths;
use Throwable;

final class AspIcons extends Controller
{
    use ResponseTrait;

    public function me_fa(): ResponseInterface
    {
        $result = $this->getDir('solid', 'fas-');
        $buffer = $this->getDir('regular', 'far-');
        foreach ($buffer as $key => $value) {
            $result[] = $value;
            unset($buffer[$key]);
        }
        $buffer = $this->getDir('brands', 'fab-');
        foreach ($buffer as $key => $value) {
            $result[] = $value;
            unset($buffer[$key]);
        }
        $buffer = $this->getDir('flags', 'faf-');
        foreach ($buffer as $key => $value) {
            $result[] = $value;
            unset($buffer[$key]);
        }
        unset($buffer);
        return $this->respond(jsonEncode($result, false), 200);
    }

    public function me_fab(): ResponseInterface 
    {
        return $this->respond(jsonEncode($this->getDir('brands', 'fab-'), false), 200);
    }

    public function me_far(): ResponseInterface 
    {
        return $this->respond(jsonEncode($this->getDir('regular', 'far-'), false), 200);
    }

    public function me_fas(): ResponseInterface 
    {
        return $this->respond(jsonEncode($this->getDir('solid', 'fas-'), false), 200);
    }

    public function me_fam(): ResponseInterface 
    {
        return $this->respond(jsonEncode($this->getDir('main', 'fam-'), false), 200);
    }

    public function me_faf(): ResponseInterface 
    {
        return $this->respond(jsonEncode($this->getDir('flags', 'faf-'), false), 200);
    }

    private function getDir (string $dir, string $prep): array 
    {
        if (!isset($dir) || !isset($prep) || !$dir) { return []; }
        $result = [];
		try {
			$fp = @opendir(Paths::ICONS . $dir); {
                while (false !== ($file = readdir($fp))) {
                    $fileInfo = pathinfo($file);
                    $ext = mb_strtolower($fileInfo['extension']); 
                    $fname = $fileInfo['filename'];
                    if ($ext === 'svg') {
                        $result[] = ['name' => $prep . $fname, 'file' => $dir . '/' . $file, 'title' => ucfirst($fname)];
                    }
                }
				closedir($fp);
			}
            return $result;
		}
		catch (Throwable $fe) { return $result; }
    }
}