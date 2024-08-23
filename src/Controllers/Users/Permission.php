<?php namespace Sygecon\AdminBundle\Controllers\Users;

use Config\AuthGroups as BaseModel;
use Sygecon\AdminBundle\Controllers\AdminController;

final class Permission extends AdminController 
{
	public function index(): string
	{
        if (strtolower($this->request->getMethod())  !== 'get') return $this->pageNotFound();
        if (! $this->request->isAJAX()) return $this->pageNotFound();

        $data = [];
        $id = 1;
        $config = new BaseModel();
        foreach($config->permissions as $name => $title) {
            $data[] = ['id' => $id, 'name' => $name, 'description' => $title];
            ++$id;
        }
        return $this->successfulResponse($data, true);
    }
}