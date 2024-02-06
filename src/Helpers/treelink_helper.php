<?php

// Сохранение данных страницы в массив
if (! function_exists('get_parent_from_cat'))
{
    function get_parent_from_cat (int $pid = 0, array $dataset, array & $tree) {
           if (isset ($pid) && $pid)
               if ($dataset)
                   foreach ($dataset as $i => $node)
                            if (isset($node['parent']) && $node['parent'] == $pid) {
                                $tree[$i] = $node['link'];
		                unset ($dataset[$i]);
                                get_parent_from_cat ($i, $dataset, $tree);      
                            }
           return true;
    }
}

/** * Шаблон Ссылок JS Tree **/
if (! function_exists('set_array_to_json'))
{
    function set_array_to_json (array $data = []){
             $tree = [];
             if (isset ($data) && $data && is_array ($data))
                 foreach ($data as $id => $item)
                          $tree[] = [ 'id' => (int)$id, 'parent' => (int)$item['parent'], 'link' => $item['link'], 'name' => $item['title'], 'title' => $item['tooltip'], 'have' => (int)$item['have_node'] ];
             return json_encode ($tree);
    }
}
/** * Шаблон Ссылок JS Tree Childs **/
if (! function_exists('set_to_index_array'))
{
    function set_to_index_array (array $array = []) {
             $new_array = [];
             if (isset ($array) && $array && is_array ($array)) {
                 //array_walk_recursive ($array, function ($item, $key) use (&$new_array) {
                 array_walk_recursive ($array, function ($item) use (&$new_array) {
                        $new_array[] = [ 'id' => (int)$item['id'], 'parent' => (int)$item['parent'], 'link' => $item['link'], 'name' => $item['title'], 'title' => $item['tooltip'], 'have' => (int)$item['have_node'] ];
                 });
             }
             return $new_array;
    }
}