<?php
namespace Sygecon\AdminBundle\Controllers\Component;

use CodeIgniter\HTTP\ResponseInterface;
use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Models\Component\RelationshipModel as BaseModel;

final class Relationship extends AdminController {

    protected $model;

    public function __construct() {
        $this->model = model(BaseModel::class);
    }

    public function index(int $id = 0): ResponseInterface
    {
        if ($this->request->getMethod() !== 'get') {
            return $this->fail(lang('Admin.IdNotFound'));
        }
        if ($this->request->isAJAX()) {
            if ($data = $this->model->find($id)) {
                return $this->respond(jsonEncode($data, false), 200);
            }
            return $this->respond('[]', 200);
        }
        return $this->respond($this->build('relationship', [   
            'field' => ['left' => $this->model::COL_LEFT, 'right' => $this->model::COL_RIGHT],
            'head' => ['icon' => 'shuffle', 'title' => lang('Admin.menu.sidebar.relationDesc')]
        ], 'Component'), 200);
    }

    /**
     * Create a new resource object, from "posted" parameters.
     * @return array an array
     */
    public function create(): ResponseInterface 
    {
        $data = $this->postDataValid($this->request->getPost(), 32, 128);
        if (isset($data['name']) && strlen($data['name']) > 2) {
            helper('path');
            $data['name'] = toSnake($data['name']);
            $data[$this->model::COL_LEFT] = (int) $data[$this->model::COL_LEFT];
            $data[$this->model::COL_RIGHT] = (int) $data[$this->model::COL_RIGHT];
            if ($data[$this->model::COL_LEFT] && $data[$this->model::COL_RIGHT]) {
                if ($id = $this->model->create($data)) {
                    return $this->respondCreated($id, lang('Admin.navbar.msg.msg_insert')); //
                }
            }
        }
        return $this->fail(lang('Admin.IdNotFound'));
    }

    /**
     * Add or update a model resource, from "posted" properties.
     * @param int $id
     * @return array an array
     */
    public function update(int $id = 0): ResponseInterface 
    {
        if (!$id) return $this->fail(lang('Admin.IdNotFound'), 400);
        if (! $data = $this->request->getRawInput()) { return $this->fail(lang('Admin.IdNotFound'), 400); }
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
                    return $this->fail(lang('Admin.IdNotFound'), 400);
                }
            }
        } 
        return $this->respondUpdated($id, lang('Admin.navbar.msg.msg_update'));
    }
    /**
     * Return the editable properties of a resource object.
     * @param int $id
     * @return array an array
     */
    public function edit(int $id = 0): ResponseInterface
    {
        if ($data = $this->model->find((int) $id, '*', 'array')) { 
            return $this->respond(jsonEncode($data, false), 200);
        }
        return $this->fail(lang('Admin.IdNotFound')); 
    }
    /**
     * Delete the designated resource object from the model.
     * @param int $id
     */
    public function delete(int $id = 0): ResponseInterface 
    {
        if (!$id) return $this->fail(lang('Admin.IdNotFound'), 400);
        if (!$this->model->delete($id)) {
            return $this->failNotFound(lang('Admin.navbar.msg.msg_get_fail'));
        }
        return $this->respondDeleted($id, lang('Admin.navbar.msg.msg_delete'));
    }
}
