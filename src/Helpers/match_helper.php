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

if (!function_exists('inArray')) {
    function inArray(string $str = '', array $arr = [], string $key = 'link'): bool
    {
        foreach($arr as &$item) {
            if (is_array($item)) {
                if (strcmp($str, $item[$key]) === 0) { return true; }
            } else {
                if (strcmp($str, $item) === 0) { return true; }
            }
        }
        return false;
    }
}

if (!function_exists('contains')) {
    function contains(string $str = '', array $arr = [], string $key = 'link'): bool
    {
        foreach($arr as &$item) {
            if (is_array($item)) {
                $res = strpos($str, $item[$key]);
            } else {
                $res = strpos($str, $item);
            }
            if ($res !== false && $res == 0) { return true; }
        }
        return false;
    }
}

if (!function_exists('subString')) {
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

if (!function_exists('readParamFromConfig')) {
    function readParamFromConfig(string $text, string $param): ?string 
    {
        if (! $param) { return null; }
        if (! $pos = mb_strpos($text, ' $' . $param)) { return null; }
        $pos += strlen($param) + 2;
        if (! $start = mb_strpos($text, '=', $pos)) { return null; }
        ++$start;
        if (! $end = mb_strpos($text, ';', $start)) { return null; }

        return trim(mb_substr($text, $start, ($end - $start)), '=; ');
    }
}

if (!function_exists('writeParamToConfig')) {
    function writeParamToConfig(string &$text, string $param, string $value): bool 
    {
        if (! $pos = mb_strpos($text, ' $' . $param)) { return false; }
        $pos += mb_strlen($param) + 2;
        if (! $end = mb_strpos($text, ';', $pos)) { return false; }
        
        $text = mb_substr($text, 0, $pos) . ' = ' . $value . mb_substr($text, $end);
        return true;
    }
}

if (!function_exists('addMethodToConfig')) {
    function addParamToConfig(string &$text, string $param): bool 
    {
        $ntext = trim($text);
        if (mb_substr($ntext, -1) === '}') { 
            $pos = mb_strlen($ntext) - 1; 
        } else
        if (! $pos = mb_strrpos($ntext, '}')) { return false; }
        --$pos;

        $text = mb_substr($ntext, 0, $pos) . "\n\n\t" . $param . ";\n}\n";
        return true;
    }
}

if (!function_exists('addUseClassToConfig')) {
    function addUseClassToConfig(string $srcText, string &$dstText): bool 
    {
        if (! $srcText) { return false; }
        if (! $dstText) { return false; }
        if (! $srcPos = mb_strpos($srcText, '{')) { return false; }
        if (! $dstPos = mb_strpos($dstText, '{')) { return false; }
        $regex = '/use\s(.*);/ui';

        $text = mb_substr($dstText, 0, $dstPos);
        if (! $pos = mb_strrpos($text, ';')) { return false; }
        ++$pos;
        $text = mb_substr($dstText, 0, $pos);
        if (! preg_match_all($regex, $text, $dstUse)) { return false; }
        if (! isset($dstUse[1])) { return false; }
        $dstUse = $dstUse[1];
        $text = trim(
            str_replace(["\r\n\r\n", "\n\n", "\r\r"], ["\r\n", "\n", "\r"], 
                preg_replace('/use\s(.*);/ui', '', $text)
            )
        );
        if (! $text) { return false; }
        $useStr = '';
        $srcUse = [];
        $dstUse = [];

        if (! preg_match_all($regex, mb_substr($srcText, 0, $srcPos), $srcUse)) { return false; }
        if (! isset($srcUse[1])) { return false; }
        $srcUse[0] = [];
        foreach($srcUse[1] as $value) {
            $srcUse[0][trim($value, '\\ ')] = 1;
        }
        $srcUse = $srcUse[0];

        foreach($dstUse as $value) { 
            $value = trim($value, '\\ ');
            $useStr .= "\n" . 'use ' . $value . ';';
            if (isset($srcUse[$value]) === true) { unset($srcUse[$value]); }
        }
        if (! $srcUse) { return false; }

        foreach($srcUse as $key => $value) { 
            $useStr .= "\n" . 'use ' . $key . ';';
            unset($srcUse[$key]);
        }
        if (! $useStr) { return false; }

        $dstText = $text . "\n" . $useStr . "\n" . mb_substr($dstText, $pos);
        return true;
    }
}

// функция по удалению определенных тегов
if (!function_exists('stripTags')) {
    function stripTags($string, $tags) { 
        //$string - строка с тегами, $tags - удаляемые теги (в строку через запятую)
        $tags = explode(',', $tags); // разбиваем строку на массив
        foreach($tags as $tag) { // перебираем теги
            $regexp = '#</?' . trim($tag) . '( .*?>|>)#Usi'; // регулярное выражение
            $string = preg_replace($regexp, '', $string); // выполняем поиск в строке
            
        }
        return $string; 
    }
}
