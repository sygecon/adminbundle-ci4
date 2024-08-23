<?php
namespace Sygecon\AdminBundle\Controllers\Api;

use CodeIgniter\Controller;
use Sygecon\AdminBundle\Libraries\Resource\Receive as ResGet;
use Sygecon\AdminBundle\Libraries\Resource\Action as ResPost;

final class AspResource extends Controller
{
    protected const TABLES = [
        'control' => [
            'control_resources', 
            'control_block_resources'
        ],
        'app' => [
            'resources', 
            'block_resources'
        ]
    ];

    public function me_style(int $block = 0, string $tab = 'app'): ?string 
    {
        $result = $this->builder((int) $block, 'style', $tab);
        if (! $this->request->isAJAX()) return $result;
        return jsonEncode(['status' => 200, 'message' => $result], false);
    }

    public function me_script(int $block = 0, string $tab = 'app'): ?string 
    {
        $result = $this->builder((int) $block, 'script', $tab);
        if (! $this->request->isAJAX()) return $result;
        return jsonEncode(['status' => 200, 'message' => $result], false);
    }

    protected function builder(int $block = 0, string $type = '', string $tab = ''): ?string 
    {
        if (! $block) return null;
        if (! $type) return null;
        if (isset(self::TABLES[$tab]) === false) return null;
        //$tables = ($tab !== 'control' ? self::TABLES['app'] : self::TABLES['control']);
        $tables = self::TABLES[$tab];
        if (strtolower($this->request->getMethod()) === 'get') {
            $resurce = new ResGet($tables[0], $tables[1]);
            return $resurce->getAll((int) $block, $type);
        }

        $data = $this->request->getRawInput();
        if ($data && is_array($data)) {
            $resurce = new ResPost($tables[0], $tables[1]);
            return $resurce->build((int) $block, $type, $data);
        }
        return null;
    }
}
