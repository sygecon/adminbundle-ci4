<?php
namespace Sygecon\AdminBundle\Controllers\Component;

use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Models\Component\RelationshipModel as BaseModel;

final class Relationship extends AdminController {

    protected $model;

    public function __construct() {
        $this->model = model(BaseModel::class);
    }

    public function index(int $id = 0): string
    {
        if (strtolower($this->request->getMethod()) !== 'get') return $this->pageNotFound();
        
        if ($this->request->isAJAX()) {
            if ($data = $this->model->find($id)) {
                return $this->successfulResponse($data);
            }
            return $this->successfulResponse([]);
        }

        return $this->build('relationship', [   
            'field' => [
                'left' => $this->model::COL_LEFT, 
                'right' => $this->model::COL_RIGHT
            ],
            'head' => [
                'icon' => 'shuffle', 
                'title' => lang('Admin.menu.sidebar.relationDesc')
            ]
        ], 'Component');
    }

    /**
     * Create a new object.
     * @return string
     */
    public function create(): string 
    {
        $data = $this->postDataValid($this->request->getPost(), 32, 128);
        if (isset($data['name']) && strlen($data['name']) > 2) {
            helper('path');
            $data['name'] = toSnake($data['name']);
            $data[$this->model::COL_LEFT] = (int) $data[$this->model::COL_LEFT];
            $data[$this->model::COL_RIGHT] = (int) $data[$this->model::COL_RIGHT];
            if ($data[$this->model::COL_LEFT] && $data[$this->model::COL_RIGHT]) {
                if ($id = $this->model->create($data)) {
                    return $this->successfulResponse($id);
                }
            }
        }
        return $this->pageNotFound();
    }

    /**
     * Update a object.
     * @param int $id
     * @return string
     */
    public function update(int $id = 0): string
    {
        if (! $id) return $this->pageNotFound();
        if (! $data = $this->request->getRawInput()) return $this->pageNotFound(); 
        
        $oldData = $this->model->find((int) $id);
        if (isset($oldData) && isset($oldData->name)) {
            $data = $this->postDataValid($data, 32, 128);
            if ($data ) {
                helper('path');
                if (isset($data['name']) && $data['name']) {
                    $data['name'] = toSnake($data['name']);
                    if ($oldData->name !== $data['name']) {
                        if ($this->model->rename($oldData->name, $data['name']) === false) {
                            unset($data['name']);
                        }
                    } else {
                        unset($data['name']);
                    }
                    unset($oldData->name);
                }
                if (isset($data[$this->model::COL_LEFT])) {
                    $data[$this->model::COL_LEFT] = (int) $data[$this->model::COL_LEFT];
                    if (! $data[$this->model::COL_LEFT]) { unset($data[$this->model::COL_LEFT]); }
                    if ($data[$this->model::COL_LEFT] == $oldData->{$this->model::COL_LEFT}) {
                        unset($data[$this->model::COL_LEFT]);
                    }
                    unset($oldData->{$this->model::COL_LEFT});
                }
                if (isset($data[$this->model::COL_RIGHT])) {
                    $data[$this->model::COL_RIGHT] = (int) $data[$this->model::COL_RIGHT];
                    if (! $data[$this->model::COL_RIGHT]) { unset($data[$this->model::COL_RIGHT]); }
                    if ($data[$this->model::COL_RIGHT] == $oldData->{$this->model::COL_RIGHT}) {
                        unset($data[$this->model::COL_RIGHT]);
                    }
                    unset($oldData->{$this->model::COL_RIGHT});
                }
                unset($oldData->title, $oldData);
                if ($this->model->update($id, $data) === false) {
                    return $this->pageNotFound();
                }
            }
        } 
        return $this->successfulResponse($id);
    }

    /**
     * Return the editable properties of a object.
     * @param int $id
     * @return string
     */
    public function edit(int $id = 0): string
    {
        if ($data = $this->model->find((int) $id, '*', 'array')) { 
            return $this->successfulResponse($data);
        }
        return $this->pageNotFound(); 
    }

    /**
     * Delete object from the model.
     * @param string
     */
    public function delete(int $id = 0): string 
    {
        if ($id && $this->model->delete($id)) {
            return $this->successfulResponse($id);
        }
        return $this->pageNotFound();
    }
}
