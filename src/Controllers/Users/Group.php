<?php namespace Sygecon\AdminBundle\Controllers\Users;

use CodeIgniter\HTTP\ResponseInterface;
use Config\AuthGroups as BaseModel;
use Sygecon\AdminBundle\Controllers\AdminController;

final class Group extends AdminController {

	public function index(): ResponseInterface
	{
        if ($this->request->getMethod() !== 'get') {
            return $this->fail(lang('Admin.IdNotFound'));
        }
        if ($this->request->isAJAX()) {
            $config = new BaseModel();
            $data = [];
            $id = 1;
            foreach($config->groups as $name => $value) {
                $data[] = ['id' => $id, 'name' => $name, 'title' => $value['title'], 'description' => $value['description']];
                ++$id;
            }
            return $this->respond(jsonEncode($data, false), 200);
        }
        return $this->fail(lang('Admin.IdNotFound'));
    }

    /**
     * Return the editable properties of a resource object.
     * @param int $id
     * @return array an array
     */
    public function edit(int $id = 0): ResponseInterface
    {
        if (! $id) { return $this->failNotFound(lang('Admin.group.msg.msg_get_fail', [0])); }
        $config = new BaseModel();
        $group = [];
        $keys = [];
        $name = '';
        $i = 1;
        foreach($config->groups as $key => $value) {
            if ($i === (int) $id) { 
                $name = $key; 
                $group = ['id' => $id, 'name' => $key, 'title' => $value['title'], 'description' => $value['description']];
                break;
            }
            ++$i;
        }
        if ($name !== '' && isset($config->matrix[$name])) {
            $data = $config->matrix[$name];
            $i = 1;
            foreach($config->permissions as $key => $title) {
                if (in_array($key, $data) || $name === 'superadmin') {
                    $keys[] = $i;    
                }
                ++$i;
            }
        }

        return $this->respond(jsonEncode([
            'data'         => $group,
            'permission'   => $keys
        ], false), 200);
    }
}