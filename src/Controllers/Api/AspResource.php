<?php
namespace Sygecon\AdminBundle\Controllers\Api;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;
use Sygecon\AdminBundle\Libraries\Resource\Receive as ResGet;
use Sygecon\AdminBundle\Libraries\Resource\Action as ResPost;

final class AspResource extends Controller {

    use ResponseTrait;

    protected const TABLES = [
        'control' => ['control_resources', 'control_block_resources'],
        'app' => ['resources', 'block_resources']
    ];

    public function me_style(int $block = 0, string $tab = 'app'): ResponseInterface 
    {
        $data = $this->builder((int) $block, 'style', $tab);
        return $this->respond($data, 200);
    }

    public function me_script(int $block = 0, string $tab = 'app'): ResponseInterface 
    {
        $data = $this->builder((int) $block, 'script', $tab);
        return $this->respond($data, 200);
    }

    protected function builder(int $block = 0, string $type = '', string $tab = ''): ?string 
    {
        if ($block && $type && isset(self::TABLES[$tab])) {
            //$tables = ($tab !== 'control' ? self::TABLES['app'] : self::TABLES['control']);
            $tables = self::TABLES[$tab];
            if ($this->request->getMethod() === 'get') {
                $resurce = new ResGet($tables[0], $tables[1]);
                return $resurce->getAll((int) $block, $type);
            }
            $data = $this->request->getRawInput();
            if ($data && is_array($data)) {
                $resurce = new ResPost($tables[0], $tables[1]);
                return $resurce->build((int) $block, $type, $data);
            }
        }
        return false;
    }
}
