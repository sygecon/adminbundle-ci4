<?php
namespace Sygecon\AdminBundle\Controllers\Api;

use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Models\Template\LayoutModel as BaseModel;

final class AspLayout extends AdminController {

    public function me_list(): string 
    {
        if (strtolower($this->request->getMethod()) !== 'get') {
            return $this->successfulResponse('[]');
        }
        $model = new BaseModel();
        // $model->JsonEncode = true;
        return $this->successfulResponse($model->getBuilder(), true);
    }

    public function me_get(int $id = 1) 
    {
        if (strtolower($this->request->getMethod()) !== 'get') return null;
        if ($id < 1) $id = 1; 
        $model = new BaseModel();
        // $model->JsonEncode = true;
        return $this->successfulResponse($model->getBuilder($id), true);
    }

    public function me_sheet(int $layoutId = 0): string 
    {
        if (strtolower($this->request->getMethod()) === 'get') {
            if ($layoutId) {
                $name = 'Layout_Sheet_List_' . $layoutId;
                if (! $res = cache($name)) {
                    //$model = new LBModel();
                    $model = new BaseModel();
                    $data = [
                        0 => ['label' => lang('HeadLines.catalog.allSheet'), 'class' => 'move-right', 'data' => []], 
                        1 => ['label' => lang('HeadLines.catalog.selSheet'), 'class' => 'move-left', 'save' => 'meDataBlock', 'data' => []]
                    ];
                    $data[0]['data'] = $model->getSheet();
                    $data[1]['data'] = $model->getSheet((int) $layoutId);
                    $res = jsonEncode($data, false);
                    cache()->save($name, $res, $model::CACHE_TTL);
                }
                return $res;
            }
        }
        return '[]';
    }
}