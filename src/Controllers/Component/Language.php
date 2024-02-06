<?php namespace Sygecon\AdminBundle\Controllers\Component;

use CodeIgniter\HTTP\ResponseInterface;
use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Models\Component\LanguageModel as BaseModel;


final class Language extends AdminController {

	public function index(): ResponseInterface
	{
        if ($this->request->getMethod() === 'get') {
            return $this->respond(
                $this->build('language', 
                    ['head' => ['icon' => 'translate', 'title' => lang('Admin.language.title')]], 
                'Component')
            , 200);
        }
        return $this->fail(lang('Admin.IdNotFound'));
    }

    /**
     * Create a new resource object, from "posted" parameters.
     * @return array an array
     */
    public function create(): ResponseInterface
    {
        $data = $this->postDataValid($this->request->getPost(), 5, 64);
        if ($data && isset($data['name'])) {
            if (array_key_exists('default', $data)) { unset($data['default']); }
            $model = new BaseModel();
            if ($id = $model->create($data)) {
                return $this->respondCreated($id, lang('Admin.language.msg.msg_insert'));
            }
        }
        return $this->fail('Error!');
    }

    /**
     * Return the editable properties of a resource object.
     * @param int $id
     * @return array an array
     */
    public function edit(int $id = 0): ResponseInterface
    {
        if ($id) {
            $model = new BaseModel();
            if ($found = $model->find($id)) {
                return $this->respond(['data' => $found], 200, lang('Admin.language.msg.msg_get', [$id]));
            }
        }
        return $this->fail('Error!');
    }

    /**
     * Add or update a model resource, from "posted" properties.
     * @param int $id
     * @return array an array
     */
    public function update(int $id = 0): ResponseInterface
    {
        if (!$id) return $this->fail(lang('Admin.IdNotFound'), 400);
        if (! $data = $this->postDataValid($this->request->getRawInput(), 5, 64)) { 
            return $this->fail(lang('Admin.IdNotFound'));
        }
        $model = new BaseModel();

        if (isset($data['pos']) && isset($data['action']) && $data['action'] === 'sortable') {
            if (! $model->moveToPos((int) $id, (int) $data['pos'])) {
                return $this->fail(lang('Admin.IdNotFound'));
            }
            return $this->respondUpdated(true, lang('Admin.language.msg.msg_update', [$id]));
        }

        if (! $model->update((int) $id, $data)) {
            return $this->failNotFound(lang('Admin.language.msg.msg_get_fail', [$id]));
        }
        $model->setUpdate();

        return $this->respondUpdated(true, lang('Admin.language.msg.msg_update', [$id]));
    }

    /**
     * Delete the designated resource object from the model.
     * @param int $id
     */
    public function delete(int $id = 0): ResponseInterface
    {
        $model = new BaseModel();
        if ($model->remove((int) $id) === true) {
            return $this->respondDeleted($id, lang('Admin.language.msg.msg_delete', [$id]));
        }
        return $this->fail(lang('Admin.IdNotFound'));
    }

    // PRIVATE
}