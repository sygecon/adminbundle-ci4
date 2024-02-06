<?php namespace Sygecon\AdminBundle\Controllers\Users;

use CodeIgniter\HTTP\ResponseInterface;
use Config\AuthGroups as BaseModel;
use Sygecon\AdminBundle\Controllers\AdminController;

final class Permission extends AdminController 
{

	public function index(): ResponseInterface
	{
        if ($this->request->getMethod() !== 'get') {
            return $this->fail(lang('Admin.IdNotFound'));
        }

        if ($this->request->isAJAX()) {
            $data = [];
            $id = 1;
            $config = new BaseModel();
            foreach($config->permissions as $name => $title) {
                $data[] = ['id' => $id, 'name' => $name, 'description' => $title];
                ++$id;
            }
            return $this->respond(jsonEncode($data, false), 200);
        } 
        return $this->fail(lang('Admin.IdNotFound'));
    }
}