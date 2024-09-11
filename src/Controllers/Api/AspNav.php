<?php
namespace Sygecon\AdminBundle\Controllers\Api;

use Sygecon\AdminBundle\Controllers\AdminController;

final class AspNav extends AdminController 
{
    private const VALID_KEY     = 'permission';
    private const CACHE_PREFIX  = 'Admin_apiNavAdmin_';
    private const PATH_NAV      = 'control' . DIRECTORY_SEPARATOR . 'nav' . DIRECTORY_SEPARATOR;

    public function me_admin(string $slug = 'menu'): string 
    {
        if (! $user = auth()->user()) return $this->successfulResponse('[]');
        if (! $groups = $user->getGroups()) return $this->successfulResponse('[]');
        $group = $groups[0];
        $csh = self::CACHE_PREFIX . $slug . '_' . $group;
        if ($data = cache($csh)) return $data;

        helper('path');
        if (! $data = getDataFromJson(self::PATH_NAV . $slug)) return $this->successfulResponse('[]');

        // Пересбор
        if ($slug === 'sidebar') { 
            if (defined('SLUG_ADMIN') && SLUG_ADMIN) {
                $data = str_replace('{direction}', SLUG_ADMIN, $data);
            }
            $items = jsonDecode($data, true);
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
        return $this->successfulResponse($data);
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
