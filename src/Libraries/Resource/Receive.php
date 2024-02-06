<?php

namespace Sygecon\AdminBundle\Libraries\Resource;

use Config\Database as DB;
use Throwable;

class Receive 
{
    protected $table_res = '';
    protected $table_union = '';
    protected $fields = ['id', 'src', 'type', 'alt', 'class'];

    protected $db;
    protected $builder;

    /** Constructor */
    public function __construct(string $table_res = '', string $table_union = '') {
        if ($table_res && $table_union) {
            $this->table_res = cleaningText($table_res);
            $this->table_union = cleaningText($table_union);
            try {
                $this->db = DB::connect();
                $this->builder = $this->db->table($this->table_res);
            } catch (Throwable $th) { $this->table_res = ''; }
        }
    }

    /** Destructor */
    public function __destruct() {
        if (isset($this->db) && $this->db) {
            $this->db->close();
        }
    }

    /** Get */
    public function get(int $id = 0): string 
    {
        if ($id) {
            $data = $this->builder->where('id', $id)->get()->getRowArray();
            return jsonEncode($data, false);
        }
        return '[]';
    }

    public function getAll(int $block = 0, string $type = ''): string 
    {
        if ($this->table_res && isset($this->db) && $this->db && $block && $type !== '') {
            $select = '';
            foreach ($this->fields as $name) {
                $select .= $this->table_res . '.' . $name . ', ';
            }
            $select = substr($select, 0, -2);
            $data = $this->builder->select($select)
                ->join($this->table_union, $this->table_union . '.resource_id = ' . $this->table_res . '.id', 'left')
                ->where($this->table_union . '.block_id', (int) $block)
                ->where($this->table_res . '.type', $type)->get()->getResultArray();
            return jsonEncode($data, false);
        }
        return '[]';
    }

    public function getResIdFromHash(string $hash = ''): ?array 
    {
        $hash = strip_tags(trim($hash));
        if ($hash)
            try {
                $row = $this->builder->select(implode(', ', $this->fields))->where('hash', $hash)->get()->getRowArray();
                if ($row) { return $row; }
            } catch (Throwable $th) { return null; }
        return null;
    }

    public function hash(string $text = ''): string 
    {
        return sha1(strip_tags(trim($text)));
    }
}
