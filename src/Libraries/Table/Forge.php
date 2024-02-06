<?php

namespace Sygecon\AdminBundle\Libraries\Table;

use Config\Database;
use CodeIgniter\Database\BaseConnection;
use App\Config\Boot\NestedTree;
use Throwable;

final class Forge 
{
    protected const KEY_ON_UPDATE = '';

    protected const KEY_ON_DELETE = 'CASCADE';

    protected bool $isCreate = true;

    protected bool $onlyAdd = true;

    protected string $table = '';

    protected array $fields = [];

    protected array $keys = [];

    protected string $qAlterTable = 'ALTER TABLE';

    protected $db;

    // Static Functions

    public static function isTable(string $table = ''): bool
    {
        if ($table) { 
            $db = Database::connect(NestedTree::DB_GROUP);
            $result = $db->tableExists($table, false);
            $db->close();
            return $result;
        }
        return false;
    }

    public static function reservedFields(): array 
    {
        $result = NestedTree::PAGE_PROPERTY_COLUMNS;
        $db = Database::connect(NestedTree::DB_GROUP);

        if (! $fields = $db->getFieldNames(NestedTree::TAB_NESTED)) { 
            $db->close();
            return $result; 
        }
        foreach ($fields as $field) { $result[$field] = 1; }

        if (! $fields = $db->getFieldNames(NestedTree::TAB_DATA)) { 
            $db->close();
            return $result; 
        }
        $db->close();
        foreach ($fields as $field) { $result[$field] = 1; }
        
        return $result;
    }

    /** @param string $table */
    public function __construct(string $table = '') {
        $this->onlyAdd = true;
        $this->db = Database::connect(NestedTree::DB_GROUP);
        if (isGroupAdmin() && $table) { $this->getFields($table); }
        
    }

    /** * Destructor */
    public function __destruct() 
    {
        if ($this->db instanceof BaseConnection) { $this->db->close(); }
    }

    public function fieldsTable(): array {
        if ($this->table) {
            return $this->db->getFieldData($this->table);
        }
        return [];
    }

    public function delete() {
        if ($this->table) {
            $forge = Database::forge(NestedTree::DB_GROUP);
            $forge->dropTable($this->table, true, true);
        }
        return;
    }

    public function rename(string $newName = '') {
        if ($this->table && $newName) {
            $forge = Database::forge(NestedTree::DB_GROUP);
            $forge->renameTable($this->table, $newName);
        }
        return;
    }

    public function deleteFields(array $fields = []) {
        if ($this->onlyAdd === false && $this->table && $fields) {
            $forge = Database::forge(NestedTree::DB_GROUP);
            $forge->dropColumn($this->table, $fields);
        }
        return;
    }

    public function addFields(array $newFields = [], array $newPrimary = [], array $newUnique = [], array $newIndex = [], array $newForeign = []) {
        if ($this->table) {
            $newFields = changeValueCase($newFields);
            if ($this->isCreate === false) {
                $addTableKeys = $this->getModifyKeys($newPrimary, $newUnique, $newIndex, $newForeign);
                $forge = Database::forge(NestedTree::DB_GROUP);
                try {
                    if ($newFields) { $forge->addColumn($this->table, $newFields); }
                    $this->queryKeysBuild($addTableKeys);
                    unset($forge);
                    return true;
                } catch (Throwable $th) {
                    unset($forge);
                    return false;
                }  
            } else if ($newFields) {
                return $this->createTable($newFields, $newPrimary, $newUnique, $newIndex, $newForeign);
            }
        }
        return false;
    }

    public function save(array &$newFields = [], array $newPrimary = [], array $newUnique = [], array $newIndex = [], array $newForeign = []): bool {
        if ($this->table) {
            $newFields = changeValueCase($newFields);

            if ($this->isCreate) {
                return $this->createTable($newFields, $newPrimary, $newUnique, $newIndex, $newForeign);
            }

            // Изменение
            $this->onlyAdd = false;
            $this->addValuesFromDefaultArray($newFields, ['null' => true, 'default' => null], ['null' => ['default', null, false]]);
            
            $forge = Database::forge(NestedTree::DB_GROUP);
            try {
                // helper('path');
                // baseWriteFile('fields.txt', jsonEncode($this->fields, true));
                $modFields = $this->compareFields($newFields);
                if (count($this->fields)) {
                    $forge->dropColumn($this->table, $this->fields);
                    array_splice($this->fields, 0);
                }
                if (isset($modFields['modify']) && count($modFields['modify'])) {
                    foreach ($modFields['modify'] as $name => $field) {
                        $forge->modifyColumn($this->table, [$name => $field]);
                        unset($modFields['modify'][$name]);
                    }
                    unset($modFields['modify']);
                }
                if (isset($modFields['new']) && count($modFields['new'])) {
                    $forge->addColumn($this->table, $modFields['new']);
                    array_splice($modFields['new'], 0);
                    unset($modFields['new']);
                }

                $addTableKeys = $this->getModifyKeys($newPrimary, $newUnique, $newIndex, $newForeign);

                if (!$addTableKeys) { return true; }
                $this->queryKeysBuild($addTableKeys);
                return true;
            } catch (Throwable $th) {
                helper('path');
                baseWriteFile('logs' . DIRECTORY_SEPARATOR . 'Errors' . DIRECTORY_SEPARATOR . 'Update-Model-Table.txt', $th->getMessage());
                return false;
            }
        }
        return false;
    }

    // Проверка Ключей
    private function getModifyKeys(array &$newPrimary, array &$newUnique, array &$newIndex, array &$newForeign): array
    {
        $addTableKeys = ['foreign' => [], 'keys' => [], 'modify' => []];  
        // Primary Keys
        if (isset($newPrimary) && is_array($newPrimary) && $newPrimary) {
            $this->deleteKeys($this->subtractArrayFromArray($this->keys['primary'], $newPrimary), $forge);
            $newPrimary = $this->subtractArrayFromArray($newPrimary, $this->keys['primary']);
            if ($newPrimary) { $this->modAddKey($addTableKeys, $newPrimary, 'primary'); }
            array_splice($this->keys['primary'], 0);
            unset($this->keys['primary']);
        }
        // Unique Keys
        if (isset($newUnique) && is_array($newUnique) && $newUnique) {
            $this->deleteKeys($this->subtractArrayFromArray($this->keys['unique'], $newUnique), $forge);
            $newUnique = $this->subtractArrayFromArray($newUnique, $this->keys['unique']);
            if ($newUnique) { $this->modAddKey($addTableKeys, $newUnique, 'unique'); }
            array_splice($this->keys['unique'], 0);
            unset($this->keys['unique']);
        }
        // Keys
        if (isset($newIndex) && is_array($newIndex) && $newIndex) {
            $this->deleteKeys($this->subtractArrayFromArray($this->keys['index'], $newIndex), $forge);
            $newIndex = $this->subtractArrayFromArray($newIndex, $this->keys['index']);
            if ($newIndex) { $this->modAddKey($addTableKeys, $newIndex, ''); }
            array_splice($this->keys['index'], 0);
            unset($this->keys['index']);
        }
        // Foreign Keys
        $resMod = [];
        if (isset($this->keys['foreign']) && count($this->keys['foreign'])) {
            if (is_array($newForeign) && $newForeign) {
                foreach ($this->keys['foreign'] as $name => $field) {
                    foreach ($newForeign as $i => $newField) {
                        if (strtolower($field['field']) === strtolower($newField['field'])) {
                            if (strtolower($field['table']) === strtolower($newField['table']) && strtolower($field['table_field']) === strtolower($newField['table_field'])) {
                            } else {
                                $resMod[$name] = $newField;
                            }
                            unset($this->keys['foreign'][$name], $newForeign[$i]);
                        }
                    }
                }
            }
            if (isset($this->keys['foreign'])) {
                foreach ($this->keys['foreign'] as $name => $field) {
                    $forge->dropForeignKey($this->table, $name);
                    unset($this->keys['foreign'][$name]);
                }
                unset($this->keys['foreign']);
            }
            if (count($resMod)) {
                foreach ($resMod as $name => $field) {
                    $this->modAddKey($addTableKeys, $field, '');
                    $forge->dropForeignKey($this->table, $name);
                    unset($resMod[$name]);
                }
            }
        }
        unset($resMod);

        if ($newForeign && is_array($newForeign)) {
            $this->modAddKey($addTableKeys, $newForeign, '');
        }

        return array_filter($addTableKeys);
    }

    private function createTable(&$fields, &$keyPrimary, &$keyUnique, &$keyIndex, &$keyForeign): bool
    {
        if (! is_array($fields)) { return false; }
        if (! $fields) { return false; }

        $forge = Database::forge(NestedTree::DB_GROUP);
        try {
            $forge->addField($fields);

            if (isset($keyPrimary) && $keyPrimary) { $forge->addKey($keyPrimary, true); }
            if (isset($keyUnique) && $keyUnique) { $forge->addUniqueKey($keyUnique); }
            if (isset($keyIndex) && $keyIndex) { $forge->addKey($keyIndex); }
            if (isset($keyForeign) && $keyForeign && is_array($keyForeign)) {
                //['field' => 'page_id', 'table' => 'pages', 'table_field' => 'id']
                foreach ($keyForeign as $items) {
                    if ($items && is_array($items)) {
                        $onUpdate = (isset($items['update']) ? : self::KEY_ON_UPDATE);
                        $onDelete = (isset($items['delete']) ? : self::KEY_ON_DELETE);
                        $forge->addForeignKey($items['field'], $items['table'], $items['table_field'], $onUpdate, $onDelete);
                    }
                }
            }
            $forge->createTable($this->table, true);
            return true;
        } catch (Throwable $th) {
            helper('path');
            baseWriteFile('logs' . DIRECTORY_SEPARATOR . 'Errors' . DIRECTORY_SEPARATOR . 'Create-Model-Table.txt', $th->getMessage());
            return false;
        }
    }

    private function queryKeysBuild(array &$box): void 
    {
        if (! $box) { return; }

        if (isset($box['keys']) && $box['keys']) {
            $this->dbQuery($this->qAlterTable, $box['keys']);
            unset($box['keys']);
        }

        if (isset($box['modify']) && is_array($box['modify'])) {
            foreach ($box['modify'] as $i => $str) {
                $this->dbQuery($this->qAlterTable, $str, $i);
                unset($box['modify'][$i]);
            }
            unset($box['modify']);
        }

        if (isset($box['modify']) && $box['foreign']) {
            $this->dbQuery($this->qAlterTable, $box['foreign']);
            unset($box['foreign'], $box);
        }
    }

    private function addKeyAsType(array &$box, mixed $name, string $type): void 
    {
        if (! $this->table) { return; }
        if (! $name) { return; }
        
        if (is_array($name) && isset($name['field']) && isset($name['table']) && isset($name['table_field'])) {
            $str = 'ADD CONSTRAINT ' . $this->table . '_' . $name['field'] . '_foreign ' .
                'FOREIGN KEY (' . $name['field'] . ') REFERENCES ' . $name['table'] . '(' . $name['table_field'] . ') ' .
                'ON DELETE ' . self::KEY_ON_DELETE . ' ON UPDATE ' . self::KEY_ON_UPDATE;
        } else 
        if ($type === 'unique') {
            $str = 'ADD UNIQUE KEY ' . $name . ' (' . $name . ')';
        } else 
        if ($type === 'primary') {
            $str = 'ADD PRIMARY KEY (' . $name . ')';
        } else {
            $str = 'ADD KEY ' . $name . ' (' . $name . ')';
        }

        if (! in_array($str, $box)) { $box[] = $str; }
    }

    private function modAddKey(array &$boxKeys, mixed $keys, string $type): void 
    {
        if (! $keys) { return; }

        if (is_string($keys)) {
            $this->addKeyAsType($boxKeys['keys'], $keys, strtolower($type));
            return;
        }

        if (isset($keys['field']) && isset($keys['table']) && isset($keys['table_field'])) {
            $this->addKeyAsType($boxKeys['foreign'], $keys, '');
            return;
        }

        foreach ($keys as $name) {
            if (is_array($name)) {
                $this->addKeyAsType($boxKeys['foreign'], $name, '');
            } else {
                $this->addKeyAsType($boxKeys['keys'], $name, strtolower($type));
            }
        }
    }

    private function deleteKeys($keys, &$forge): void 
    {
        if ($this->onlyAdd === false && $this->table && $forge && $keys && is_array($keys)) {
            foreach ($keys as $key) {
                if ($key && is_string($key)) { $forge->dropKey($this->table, $key); }
            }
        }
    }

    private function getFields(string $table = ''): void 
    {
        $this->fields = [];
        $this->keys = ['primary' => [], 'unique' => [], 'index' => [], 'foreign' => []];
        $this->isCreate = true;

        if (! $this->table = $table) { return; }
        if (! $this->db->tableExists($this->table)) { return; }

        $this->isCreate = false;
        $fields = $this->db->getFieldData($this->table);
        $keys = $this->db->getIndexData($this->table);
        $keysForeign = $this->db->getForeignKeyData($this->table);

        foreach ($keys as $i => &$value) {
            if (isset($value->type) && $value->fields && is_array($value->fields)) {
                $type = strtolower($value->type);
                if (array_key_exists($type, $this->keys) === true) {
                    $this->keys[$type][] = $value->fields[0];
                }
                unset($value->type, $value->name, $value->fields[0], $value->fields);
            }
            unset($value);
        }

        foreach ($keysForeign as $i => $key) {
            $this->keys['foreign'][$key->constraint_name] = [
                'field' => $key->column_name,
                'table' => $key->foreign_table_name,
                'table_field' => $key->foreign_column_name
            ]; // $key->table_name,
            unset($keysForeign[$i]);
        }

        foreach ($fields as $i => $field) {
            $this->fields[$field->name] = $this->arrayKeyOnArrayValue($field, ['max_length' => 'constraint', 'nullable' => 'null', 'primary_key' => false, 'name' => false]);
            unset($fields[$i]);
        }
    }

    private function compareFields(array $newFields = []): array 
    {
        $rows = ['new' => [], 'modify' => []];
        $number = ['modify' => []];
        if (!count($this->fields)) {
            return $rows['new'] = $newFields;
        }
        if (count($newFields)) {
            foreach ($newFields as $name => $field) {
                if (!isset($this->fields[$name])) {
                    $rows['new'][$name] = $field;
                } else {
                    $unsigned = false;
                    $mod = (array) array_intersect_key($field, ['name' => 1, 'constraint' => 1, 'type' => 1, 'default' => 1, 'null' => 1, 'unsigned' => 1]);
                    $this->subtractKeyArrayFromArray($mod, (array) $this->fields[$name]);
                    if (isset($mod) && is_array($mod) && count($mod)) {
                        $q = '';
                        if (isset($mod['unsigned'])) {
                            if ($mod['unsigned']) $unsigned = true;
                            unset($mod['unsigned']);
                        }
                        if (isset($mod['null'])) {
                            $nul = ((!$field['null'] && isset($field['default'])) ? false : true);
                            if ($mod['null'] != $nul) {
                                if (!$nul) $q .= ' NOT NULL';
                                else $q .= ' NULL';
                            }
                            unset($mod['null']);
                        }
                        if (isset($mod['default'])) {
                            $def = $field['default'];
                            unset($mod['default']);
                            if ($q === '' && isset($field['null'])) {
                                if (!$field['null'] && $def !== null) $q .= 'NOT NULL';
                                else $q .= ' NULL';
                            }
                            if (is_string($def)) {
                                $def = "'" . $def . "'";
                            }
                            $q .= ' DEFAULT ' . $def;
                        }
                        if ($q !== '') {
                            if ($unsigned) $q = ' UNSIGNED ' . $q;
                            if (isset($mod['constraint']) && isset($field['constraint'])) {
                                unset($mod['constraint']);
                                $q = '(' . (int) $field['constraint'] . ') ' . $q;
                            }
                            $number['modify'][] = 'MODIFY ' . $name . ' ' . strtoupper($field['type']) . str_replace('  ', ' ', $q);
                            $q = '';
                        }
                        // if (isset($mod['auto_increment'])) { $q .= ' AUTO_INCREMENT'; }
                        if (count($mod)) {
                            if (!isset($field['name'])) { $field['name'] = $name; }
                            $rows['modify'][$name] = $field;
                            array_splice($mod, 0);
                        }
                    }
                    unset($this->fields[$name]);
                }
                unset($newFields[$name]);
            }
            if ($number['modify'] && is_array($number['modify'])) {
                $this->queryKeysBuild($number);
            }
            unset($number['modify'], $number);
        }
        if (count($this->fields)) {
            $this->fields = array_keys($this->fields);
        }
        return $rows;
    }

    private function dbQuery(string $head, &$box): void
    {
        if (! $this->db instanceof BaseConnection) { return; }
        if (! $this->table) { return; }
        if (! $head) { return; }
        if (! $box) { return; }

        $sql = '';
        if (is_array($box)) {
            foreach ($box as $i => $str) {
                $sql .= $str . ", \n";
                unset($box[$i]);
            }
            $sql = substr($sql, 0, -3);
        } else { $sql = $box; }

        if (! $sql) { return; }

        $sql = $head . ' ' . $this->table . " \n " . $sql . ';';
        $this->db->transStart();
        $this->db->query($sql);
        $this->db->transComplete();
    }

    //Добавить значения из массива по умолчанию
	private function addValuesFromDefaultArray(mixed &$source, array $default, array $rule): void 
    {
		foreach ($source as &$field) {
			foreach ($default as $key => $value) {
				if (! array_key_exists($key, $field)) { $field[$key] = $value; }
			}
			foreach ($rule as $key => $value) {
				if (isset($field[$key]) && is_array($value) && count($value) === 3) {
					if (isset($field[$value[0]]) && $field[$value[0]] !== $value[1]) {
						$field[$key] = $value[2];
					}
				}
			}
		}
	}

    // Заменить ключи массива значениями из другого массива
	private function arrayKeyOnArrayValue(mixed $source, array $filter): array 
    {
		if (! $filter) { return $source; }

        foreach ($source as $key => $value) {
            if (isset($filter[$key])) {
                $nk = $filter[$key];
                if (is_object($source)) {
                    if (is_numeric($nk) || is_string($nk)) $source->{$nk} = $value;
                    unset($source->{$key});
                } else if (is_array($source)) {
                    if (is_numeric($nk) || is_string($nk)) $source[$nk] = $value;
                    unset($source[$key]);
                }
            }
        }
		return $source;
	}

    // Вычесть из одного массива ключи и значения другого
	private function subtractKeyArrayFromArray(array &$arrayHead = [], array $arraySub = []): void 
    {
		foreach ($arrayHead as $key => $value) {
			if (isset($arraySub[$key]) && !is_array($value) && !is_array($arraySub[$key])) {
				if ((string) $value === (string) $arraySub[$key]) { unset($arrayHead[$key]); }
			}
		}
	}

    // Вычесть из одного массива значения другого
	private function subtractArrayFromArray(array $arrayHead, $arraySub): array 
    {
		if (isset($arraySub) && $arraySub) {
			foreach ($arrayHead as $i => $value) {
				if (is_array($arraySub)) {
                    if (in_array($value, $arraySub)) unset($arrayHead[$i]);
				} else if ((string) $value === (string) $arraySub) {
                    unset($arrayHead[$i]);
                }
			}
		}
		return $arrayHead;
	}
}
// $field = [
//     'name'           => $key,
//     'new_name'       => $attributes['NAME'] ?? null,
//     'type'           => $attributes['TYPE'] ?? null,
//     'length'         => '',
//     'unsigned'       => '',
//     'null'           => '',
//     'unique'         => '',
//     'default'        => '',
//     'auto_increment' => '',
//     '_literal'       => false,
// ];