<?php

if (! function_exists('isInteger')) {
    function isInteger($value): bool
    {
        if (! is_numeric($value)) return false;
        return (bool) preg_match('/\A[\-+]?\d+\z/', $value ?? '');
    }
}

if (! function_exists('isWholeNumber')) {
    function isWholeNumber($value): bool
    {
        if (! is_numeric($value)) return false;
        return (bool) preg_match('/\A[-+]?\d{0,}\.?\d+\z/', $value ?? '');
    }
}

if (! function_exists('inArray')) {
    function inArray(string $text = '', array $arr = [], string $key = 'link'): bool
    {
        foreach($arr as &$item) {
            $res = (is_array($item) === true
                ? strcmp($text, $item[$key])
                : strcmp($text, $item)
            );
            if ($res === 0) return true;
        }
        return false;
    }
}

if (! function_exists('contains')) {
    function contains(string $text = '', array $arr = [], string $key = 'link'): bool
    {
        foreach($arr as &$item) {
            $res = (is_array($item) === true
                ? strpos($text, $item[$key])
                : strpos($text, $item)
            );
            if ($res !== false && $res == 0) { return true; }
        }
        return false;
    }
}

if (! function_exists('subString')) {
	function subString(string $text, string $find, string $findEnd, int &$offset): string 
    {
        $start = strpos($text, $find, $offset);
        if ($start === false) {
            ++$offset; 
            return ''; 
        }
        $start += strlen($find);
        if (! $end = strpos($text, $findEnd, $start)) { 
            $offset = $start + 1;
            return ''; 
        }
        $offset = $end + 1;
        return trim(substr($text, $start, ($end - $start)));
    }
}

if (! function_exists('findParamInClass')) {
    function findParamInClass(string $text, string $param): int 
    {
        if (! $len = mb_strlen($param)) { return 0; }
        if ($pos = mb_strpos($text, ' $' . $param)) { return $pos + $len + 2; }
        if ($pos = mb_strpos($text, ' const ' . $param)) { return $pos + $len + 7; }
        return 0;
    }
}

if (! function_exists('writeParamToClass')) {
    function writeParamToClass(string &$text, string $param, string $value): bool 
    {
        if (! $pos = findParamInClass($text, $param)) { return false; }
        if (! $end = mb_strpos($text, ';', $pos)) { return false; }
        
        $text = mb_substr($text, 0, $pos) . ' = ' . $value . mb_substr($text, $end);
        return true;
    }
}

// функция по удалению определенных тегов
if (! function_exists('stripTags')) {
    //$text - строка с тегами, $tags - удаляемые теги (в строку через запятую)
    function stripTags(string $text, string $tags): string 
    { 
        foreach(explode(',', $tags) as $tag) {
            $regexp = '#</?' . trim($tag) . '( .*?>|>)#Usi';
            $text = preg_replace($regexp, '', $text);
        }
        return $text; 
    }
}

// Функция транслитерации текста 
if (! function_exists('translateRus')) {
    function translateRus(string $text): string 
    {
        $charMap = [
            0 => [
                'Ё', 'Ж', 'Ц', 'Ч', 'Щ', 'Ш', 'Ы',
                'Э', 'Ю', 'Я', 'ё', 'ж', 'ц', 'ч',
                'ш', 'щ', 'ы', 'э', 'ю', 'я', 'А',
                'Б', 'В', 'Г', 'Д', 'Е', 'З', 'И',
                'Й', 'К', 'Л', 'М', 'Н', 'О', 'П',
                'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ъ',
                'Ь', 'а', 'б', 'в', 'г', 'д', 'е',
                'з', 'и', 'й', 'к', 'л', 'м', 'н',
                'о', 'п', 'р', 'с', 'т', 'у', 'ф',
                'х', 'ъ', 'ь'
            ],
            1 => [
                'YO', 'ZH',  'CZ', 'CH', 'SHH', 'SH', 'Y',
                'E',  'YU',  'YA', 'yo', 'zh',  'cz', 'ch',
                'sh', 'shh', 'y',  'e',  'yu',  'ya', 'A',
                'B',  'V',   'G',  'D',  'E',   'Z',  'I',
                'Y',  'K',   'L',  'M',  'N',   'O',  'P',
                'R',  'S',   'T',  'U',  'F',   'X',  '',
                '',   'a',   'b',  'v',  'g',   'd',  'e',
                'z',  'i',   'y',  'k',  'l',   'm',  'n',
                'o',  'p',   'r',  's',  't',   'u',  'f',
                'x',  '',  ''
            ]
        ];
        return str_replace($charMap[0], $charMap[1], $text);
    }
}
