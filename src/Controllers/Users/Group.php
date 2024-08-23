<?php namespace Sygecon\AdminBundle\Controllers\Users;

use Config\AuthGroups as BaseModel;
use Sygecon\AdminBundle\Controllers\AdminController;

final class Group extends AdminController 
{
	public function index(): string
	{
        if (strtolower($this->request->getMethod()) !== 'get') $this->pageNotFound();
        if (! $this->request->isAJAX()) return $this->pageNotFound();
        
        $config = new BaseModel();
        $data = [];
        $id = 1;
        foreach($config->groups as $name => $value) {
            $data[] = ['id' => $id, 'name' => $name, 'title' => $value['title'], 'description' => $value['description']];
            ++$id;
        }
        return $this->successfulResponse($data, true);
    }

    /**
     * Return the editable properties of a object.
     * @param int $id
     * @return string
     */
    public function edit(int $id = 0): string
    {
        if (! $id) return $this->pageNotFound();

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

        return $this->successfulResponse([
            'data'         => $group,
            'permission'   => $keys
        ], true);
    }
}