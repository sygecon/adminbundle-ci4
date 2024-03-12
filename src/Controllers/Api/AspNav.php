<?php
namespace Sygecon\AdminBundle\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use Sygecon\AdminBundle\Controllers\AdminController;

final class AspNav extends AdminController {

    private const VALID_KEY = 'permission';

    private const CACHE_PREFIX = 'Admin_apiNavAdmin_';

    private const PATH_NAV = 'control' . DIRECTORY_SEPARATOR . 'nav' . DIRECTORY_SEPARATOR;

    public function me_admin(string $slug = 'menu'): ResponseInterface 
    {
        
        if (! $user = auth()->user()) { return $this->fail(lang('Admin.IdNotFound')); }
        if (! $groups = $user->getGroups()) { return $this->fail(lang('Admin.IdNotFound')); }
        $group = $groups[0];
        $csh = self::CACHE_PREFIX . $slug . '_' . $group;
        if ($data = cache($csh)) { return $this->respond($data, 200); }

        helper('path');
        if (! $data = getDataFromJson(self::PATH_NAV . $slug)) { 
            return $this->fail(lang('Admin.IdNotFound')); 
        }
        // Пересбор
        if ($slug === 'sidebar') { 
            $items = jsonDecode($data);
            $data = '';
            foreach ($items as $i => &$item) {
                if (isset($item[self::VALID_KEY])) {
                    if ($user->can($item[self::VALID_KEY])) { 
                        $data .= $this->arrayToJson($item) . ',';
                    }
                }
                $item = [];
                unset($items[$i]);
            }
            $data = '[' . mb_substr($data, 0, -1) . ']';
            unset($items);
        }
        cache()->save($csh, $data, 600);
        return $this->respond($data, 200);
    }

    private function arrayToJson(array &$items): string
    {
        $result = '';
        foreach ($items as $key => &$value) {
            if ($key !== self::VALID_KEY) {
                $result .= '"' . $key . '":"' . $value . '",';
            }  
            unset($items[$key]);
        }
        return '{' . mb_substr($result, 0, -1) . '}';
    }
}
