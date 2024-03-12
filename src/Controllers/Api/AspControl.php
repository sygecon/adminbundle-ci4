<?php

namespace Sygecon\AdminBundle\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;
use Config\Services;
use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Libraries\Control\Finder;
use Sygecon\AdminBundle\Models\Component\RelationshipModel;
use Sygecon\AdminBundle\Config\FormDataTypes;
use Config\Boot\NestedTree;

use function function_exists;
use function is_file;
use function is_array;
use function array_splice;
use function isGroupAdmin;
use function jsonEncode;

final class AspControl extends AdminController {

    use ResponseTrait;

    public function me_tree(): ResponseInterface 
    {
        return $this->respond($this->getBuilder('controllers'), 200);
    }

    public function me_list(): ResponseInterface 
    {
        return $this->respond($this->getBuilder('controllers', false), 200);
    }

    public function me_model(): ResponseInterface 
    {
        return $this->respond($this->getBuilder('models'), 200);
    }

    public function me_layout(): ResponseInterface 
    {
        return $this->respond($this->getBuilder('layout'), 200);
    }

    public function me_library(): ResponseInterface 
    {
        return $this->respond($this->getBuilder('library'), 200);
    }

    public function me_data_types(): ResponseInterface 
    {
        $inputs = FormDataTypes::INPUTS;
        $variables = FormDataTypes::VARIABLES;
        $relTemplate = $variables['relationship'];
        unset($variables['relationship']);
        
        // Relationship
        $relModel = new RelationshipModel();
        if ($relations = $relModel->find(0, 'name, title')) {
            if (isset($inputs['Files-Page'])) { $inputs['Files-Page']['tab'] = []; }
            if (isset($inputs['Files-Page-List'])) { $inputs['Files-Page-List']['tab'] = []; }
            foreach ($relations as $i => $item) {
                $variables[$item->name] = str_replace('%tab_name%', $item->name, $relTemplate);
                if (isset($inputs['Files-Page'])) { $inputs['Files-Page']['tab'][] = $item->name; }
                if (isset($inputs['Files-Page-List'])) { $inputs['Files-Page-List']['tab'][] = $item->name; }
                unset($relations[$i]);
            }
            unset($relations);
        }

        foreach($inputs as $key => &$value) {
            if ($value && isset($value['tab'])) { $inputs[$key] = $value['tab']; }
        }

        return $this->respond(jsonEncode([
            'title'     => FormDataTypes::BTN_TITLE, 
            'group'     => FormDataTypes::GROUP_TITLE, 
            'variables' => $variables, 
            'inputs'    => $inputs,
            'attributes'=> FormDataTypes::ATTRIBUTES
        ], false), 200);
    }

    public function me_lang(): ResponseInterface 
    {
        $page = 'HeadLines';
        $list = [];
        // $language = Services::language();
        // $Locale = $language->getLocale();
        $array  = Services::locator()->search('Language/' . $this->locale . '/' . $page, 'php', false);
        if (is_array($array) && isset($array[0]) && is_file($array[0])) {
            $file = $array[0];
            array_splice($array, 0);
            $array[$page] = require $file;
            $id = 1;
            $list[] = ['id' => (int) 0, 'parent' => (int) 0, 'value' => $page, 'name' => $page, 'title' => $page];
            $this->arrayToList($array, $list, $id, $id);
        }
        return $this->respond(jsonEncode($list, false), 200);
    }

    private function getBuilder(string $var = '', bool $asTree = true): string 
    {
        if (function_exists('isGroupAdmin') && isGroupAdmin()) {
            $model = new Finder();
            if (! $data = $model->get($var, $asTree)) { return '[]'; }
            return jsonEncode($data, false);
        }
        return '[]';
    }

    private function arrayToList(array &$array, array &$list, int $parent, int &$id): void 
    {
        foreach($array as $name => &$item) {
            ++$id;
            if (is_array($item)) {
                $list[] = ['id' => (int) $id, 'parent' => (int) $parent, 'name' => $name, 'title' => $name];
                $this->arrayToList($item, $list, $id, $id);
            } else {
                $list[] = ['id' => (int) $id, 'parent' => (int) $parent, 'value' => $name, 'name' => $name, 'title' => $item];
            }
            unset($array[$name]);
        }
    }

    //Функция формирования дерева children
    private function buildTree(array $elements = [], int $parentId = 0): array 
    {
        $branch = [];
        foreach ($elements as $element) {
            if ($element['parent'] == $parentId) {
                $children = $this->buildTree($elements, $element['id']);
                if ($children) { 
                    $element[NestedTree::JSON_KEY_CHILDREN] = $children; 
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }

}
