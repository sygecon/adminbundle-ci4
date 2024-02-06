<?php

namespace Sygecon\AdminBundle\Libraries\Resource;

use Throwable;

class Action extends Receive {

    protected int $block = 0;
    protected array $dataPost = [];

    public function build(int $block = 0, string $type = '', array $data = []): ?string 
    {
        if ($this->table_res && isset($this->db) && $this->db && $block && $type !== '' && $data) {
            $this->block = $block;
            $this->dataPost = $data;
            $action = '';
            if (isset($this->dataPost['action'])) {
                $action = cleaningText($this->dataPost['action']);
                unset($this->dataPost['action']);
                if ($action === 'insert') {
                    $this->dataPost['type'] = $type;
                }
            }
            if ($action) {
                $id = 0;
                if (isset($this->dataPost['id'])) {
                    $id = (int) $this->dataPost['id'];
                    unset($this->dataPost['id']);
                }
                switch ($action) {
                    case 'insert':
                        return $this->insert();
                        break;
                    case 'update':
                        return $this->update($id);
                        break;
                    case 'delete':
                        return ($this->delete($id) ? '1' : null);
                        break;
                }
            }
        }
        return null;
    }

    protected function delete(int $id = 0): bool 
    {
        if ($id) {
            $this->db->transStart();
            $res = $this->builder->delete('id = ' . $id);
            $this->db->transComplete();
            if ($res) {
                $this->delUnion((int) $id);
                return true;
            }
        }
        return false;
    }

    protected function insert(): ?string 
    {
        if (isset($this->dataPost) && $this->dataPost && isset($this->dataPost['src']) && $this->dataPost['src']) {
            $this->dataPost['hash'] = $this->hash($this->dataPost['src']);
            if (isset($this->dataPost['id'])) {
                unset($this->dataPost['id']);
            }
            // helper('path');
            //baseWriteFile('fields4.txt', jsonEncode($this->dataPost, false));
            $row = $this->getResIdFromHash($this->dataPost['hash']);
            if ($row) {
                $id = $row['id'];
                $this->dataPost = $row;
                array_splice($row, 0);
            } else {
                $this->db->transStart();
                $this->builder->set($this->dataPost)->insert();
                $id = $this->db->insertID();
                $this->db->transComplete();
                $this->dataPost['id'] = (int) $id;
            }
            unset($row);

            $res = false;
            if (isset($id) && $id) {
                $res = $this->addUnion((int) $id);
            }
            if ($res) {
                if (isset($this->dataPost['hash'])) {
                    unset($this->dataPost['hash']);
                }
                return jsonEncode($this->dataPost, false);
            }
        }
        return null;
    }

    protected function update(int $id = 0): ?string 
    {
        if (isset($this->dataPost) && $this->dataPost) {
            if (isset($this->dataPost['src']) && $this->dataPost['src']) {
                $this->dataPost['hash'] = $this->hash($this->dataPost['src']);
                $row = $this->getResIdFromHash($this->dataPost['hash']);
                if ($row) {
                    if ((int) $id !== (int) $row['id']) {
                        $res = $this->addUnion((int) $row['id']);
                        if ($res) {
                            $this->delUnion((int) $id);
                        }
                        if ($res) {
                            return jsonEncode($row, false);
                        }
                    }
                    return null;
                }
            }
            $this->db->transStart();
            $this->builder->set($this->dataPost)->where('id', (int) $id)->update();
            $this->db->transComplete();
            return $this->get((int) $id);
        }
        return null;
    }

    protected function addUnion(int $resId = 0): bool 
    {
        if ($this->block && $resId) {
            $data = ['block_id' => (int) $this->block, 'resource_id' => (int) $resId];
            $builder = $this->db->table($this->table_union);
            $res = $builder->where($data)->get()->getRowArray();
            if (!isset($res) || !$res) {
                $this->db->transStart();
                $builder->set($data)->insert();
                $this->db->transComplete();
                return true;
            }
        }
        return false;
    }

    protected function delUnion(int $resId = 0) {
        if ($resId) {
            $builder = $this->db->table($this->table_union);
            try {
                $this->db->transStart();
                $builder->where('block_id', (int) $this->block)
                    ->where('resource_id', (int) $resId)
                    ->delete();
                $this->db->transComplete();
            } catch (Throwable $th) { return; }
        }
    }
}
