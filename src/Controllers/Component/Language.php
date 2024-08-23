<?php namespace Sygecon\AdminBundle\Controllers\Component;

use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Models\Component\LanguageModel as BaseModel;

final class Language extends AdminController 
{

    /** @return string */
	public function index(): string
	{
        if (strtolower($this->request->getMethod()) !== 'get') return $this->pageNotFound();
        
        return $this->build('language', ['head' => [
            'icon' => 'translate', 
            'title' => lang('Admin.language.title')
        ]], 'Component');
    }

    /**
     * Create a new resource object, from "posted" parameters.
     * @return string
     */
    public function create(): string
    {
        $data = $this->postDataValid($this->request->getPost(), 5, 64);
        if ($data && isset($data['name'])) {
            if (array_key_exists('default', $data)) unset($data['default']);
            $model = new BaseModel();
            if ($id = $model->create($data)) return $this->successfulResponse($id);
        }
        return $this->pageNotFound();
    }

    /**
     * Return the editable properties of a resource object.
     * @param int $id
     * @return array
     */
    public function edit(int $id = 0): array
    {
        $result = ['data' => null];
        if ($id) {
            $model = new BaseModel();
            $result['data'] = $model->find($id);
        }
        return $result;
    }

    /**
     * Add or update a model resource, from "posted" properties.
     * @param int $id
     * @return string
     */
    public function update(int $id = 0): string
    {
        if (! $id) return $this->pageNotFound();
        if (! $data = $this->postDataValid($this->request->getRawInput(), 5, 64)) return $this->pageNotFound();
        
        $model = new BaseModel();
        if (isset($data['pos']) && isset($data['action']) && $data['action'] === 'sortable') {
            if (! $model->moveToPos((int) $id, (int) $data['pos'])) return $this->pageNotFound();
            return $this->successfulResponse(true);
        }
        if (! $model->update((int) $id, $data)) return $this->pageNotFound();

        $model->setUpdate();
        return $this->successfulResponse(true);
    }

    /**
     * Delete the designated resource object from the model.
     * @param string
     */
    public function delete(int $id = 0): string
    {
        if ($id) {
            $model = new BaseModel();
            if ($model->remove((int) $id) === true) {
                return $this->successfulResponse($id);
            }
        }
        return $this->pageNotFound();
    }

    // PRIVATE
}