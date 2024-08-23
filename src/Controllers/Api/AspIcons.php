<?php
namespace Sygecon\AdminBundle\Controllers\Api;

use CodeIgniter\Controller;
use Sygecon\AdminBundle\Config\Paths;
use Throwable;

final class AspIcons extends Controller
{
    public function me_fa(): string
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
        return $this->successfulResponse($result);
    }

    public function me_fab(): string 
    {
        return $this->successfulResponse(
            $this->getDir('brands', 'fab-')
        );
    }

    public function me_far(): string 
    {
        return $this->successfulResponse(
            $this->getDir('regular', 'far-')
        );
    }

    public function me_fas(): string 
    {
        return $this->successfulResponse(
            $this->getDir('solid', 'fas-')
        );
    }

    public function me_fam(): string 
    {
        return $this->successfulResponse(
            $this->getDir('main', 'fam-')
        );
    }

    public function me_faf(): string 
    {
        return $this->successfulResponse(
            $this->getDir('flags', 'faf-')
        );
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

    private function successfulResponse(mixed $response): string
	{
		if (! $this->request->isAJAX()) return jsonEncode($response, false);
		return jsonEncode(['status' => 200, 'message' => $response], false);
	}
}