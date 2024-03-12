<?php

if (! function_exists('getClassModelName')) {
    function getClassModelName(string $name = '', bool $autoload = true): string
    {
        $class = '\\App\\Models\\Boot\\BuildModel';
        if ($modelName = ucfirst(trim($name))) { 
            $class = '\\Sygecon\\AdminBundle\\Models\\Layout\\' . $modelName . 'Model';
        }
        if (class_exists($class, $autoload) === true) { return $class; }
        return '';
    }
}

if (! function_exists('dataIntersect')) {
    function dataIntersect(array &$data, array $allow): array
    {
        if (! isset($data) || ! $data) { return []; }
        if (! isset($allow) || ! $allow) { return $data; }
        $result = [];
        foreach($data as $key => &$value) {
            if (in_array($key, $allow)) {
                $result[$key] = $value;
                unset($data[$key]);
            }
        }
        return $result;
    }
}

if (! function_exists('dataKeyIntersect')) {
    function dataKeyIntersect(array &$data, array $allow): array
    {
        if (! isset($data) || ! $data) { return []; }
        if (! isset($allow) || ! $allow) { return $data; }
        $result = [];
        foreach($data as $key => &$value) {
            if (array_key_exists($key, $allow)) {
                $result[$key] = $value;
                unset($data[$key]);
            }
        }
        return $result;
    }
}