<?php
namespace Sygecon\AdminBundle\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Models\Template\LayoutModel as BaseModel;

final class AspLayout extends AdminController {

    public function me_list(): ResponseInterface 
    {
        if ($this->request->getMethod() === 'get') {
            if (! $data = cache('Layout_All')) {
                $model = new BaseModel();
                $model->JsonEncode = true;
                $data = $model->getBuilder();
            }
            return $this->respond($data, 200);
        }
        return $this->fail(lang('Admin.IdNotFound'));
    }

    public function me_get(int $id = 1): ResponseInterface 
    {
        if ($this->request->getMethod() === 'get') {
            if ($id < 1) { $id = 1; }
            if (! $data = cache('Layout_Id_' . $id)) {
                $model = new BaseModel();
                $model->JsonEncode = true;
                $data = $model->getBuilder($id);
            }
            return $this->respond($data, 200);
        }
        return $this->fail(lang('Admin.IdNotFound'));
    }

    public function me_sheet(int $layoutId = 0): ResponseInterface 
    {
        if ($this->request->getMethod() === 'get') {
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
                    cache()->save($name, $res, 20160);
                }
                return $this->respond($res, 200);
            }
        }
        return $this->fail(lang('Admin.IdNotFound'));
    }
}