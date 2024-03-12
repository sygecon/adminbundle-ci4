<?php

if (! function_exists('toSnake')) {
    function toSnake(string $text = ''): string
    {
        if (! $result = str_replace(' ', '', $text)) return '';
        if (ctype_alpha($result) === true && ctype_lower($result) === true) return $result;
        return strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1_', $result));
    }
}

if (! function_exists('toCamelCase')) {
    function toCamelCase(string $text = ''): string 
    {
        if (! $text = strtolower(str_replace(' ', '', $text))) return '';
        $result = '';
        foreach (explode('_', $text) as $word) { $result .= ucfirst(trim($word)); }
        return $result;
    }
}

/// Приведение пути к стандарту Класса  -------------------------------------------
if (! function_exists('toClassUrl')) {
    function toClassUrl(string $classname = '', string $suffix = ''): string 
    {
        // $classname = preg_replace_callback(
        //     '~(?<=^|[\pP\h])\pL~u',
        //     function ($m) { return strtoupper($m[0]); },
        //     trim(str_replace(['_', '-', '|'], [' ', ' ', '/'], toUrl($classname)), '/')
        // );
        $classname = str_replace(['-', '|', ' '], ['_', '/', '_'], toUrl($classname));
        $classname = trim(str_replace(['__', '//'], ['_', '/'], $classname), '/');
        $classname = toCamelCase($classname);
        //$classname = str_replace(' ', '', $classname);
        if ($suffix) {
            $suffix = ucfirst($suffix);
            if ($suffix && substr($classname, -1 * strlen($suffix)) !== $suffix) 
                return $classname . $suffix;
        }
        return $classname;
    }
}

if (! function_exists('getRootUrl')) {
    function getRootUrl(string $url = ''): string
    {
        $link = rtrim(cutQueryFromUrl($url), '/ ');
        if (! $link) return $link;
        $pos = 0;
        if ($start = strpos($link, '://')) $pos = $start + 3;
        else 
        if (substr($link, 0, 2) === '//') $pos = 2;
        if (! $n = strpos($link, '/', $pos)) return $link;
        return substr($link, 0, $n);
    }
}

// Предыдущая Url
if (! function_exists('previousUrl')) {
	function previousUrl(string $url = '', string $delimiter = '/'): string 
	{
		$link = rtrim(cutQueryFromUrl($url), $delimiter . ' ');
		if (! $link) { return $link; }
		if (! $pos = strrpos($link, $delimiter, -1)) { return $link; }
		return substr($link, 0, $pos);
	}
}

/// Чтение данных из файла   -------------------------------------------
if (! function_exists('readingDataFromFile')) {
    function readingDataFromFile(string $fileName): string 
    {
        if (is_file($fileName) === true && is_readable($fileName) === true) { 
            if ($data = file_get_contents($fileName)) return $data;
        }
        return '';
    }
}

// Запись данных в файл (перезапись) -------------------------------------------
if (! function_exists('writingDataToFile')) {
    function writingDataToFile(string $fileName = '', string $data = '', bool $bkDelete = true): bool 
    {
        if (! $fileName) return false;
        $bkName  = '';
        if (true === file_exists($fileName)) {
            $bkName = $fileName . '.bak.php';
            if (true === is_file($bkName)) @unlink($bkName);
            rename($fileName, $bkName);
        }
        
        if (! $fp = fopen($fileName, 'c')) return false; // 'wb'
        $buffer   = trim(urldecode($data)) . PHP_EOL;
        $length   = mb_strlen($buffer);
        $fwrite   = 0;
        $written  = 0;
        try {
            flock($fp, LOCK_EX);
            for ($written = 0; $written < $length; $written += $fwrite) {
                $fwrite   = fwrite($fp, mb_substr($buffer, $written));
                if (false === $fwrite) break;
            }
            flock($fp, LOCK_UN);
            fclose($fp);
        } catch (\Throwable $th) { $written = 0; }
        
        if ($written === $length) { 
            if (true === $bkDelete && true === isset($bkName)) @unlink($bkName);
            return true; 
        }
        if ($bkName && true === is_file($bkName)) {
            if (is_file($fileName)) @unlink($fileName);
            rename($bkName, $fileName);
            @unlink($bkName); 
        }
        return false;
    }
}

// Перезапись файла
if (! function_exists('baseWriteFile')) {
    function baseWriteFile(string $name = '', string $data = '', bool $bkDelete = true): bool 
    {
        $fileName = WRITEPATH . 'base' . DIRECTORY_SEPARATOR . castingPath($name, true);
        return writingDataToFile($fileName, $data, $bkDelete);
    }
}

// Чтение файла
if (! function_exists('baseReadFile')) {
    function baseReadFile(string $name): string
    {
        $fileName = WRITEPATH . 'base' . DIRECTORY_SEPARATOR . castingPath($name, true);
        return readingDataFromFile($fileName);
    }
}

// Get Data from Json file  ==============================
if (! function_exists('getDataFromJson')) {
    function getDataFromJson(string $fileName): mixed 
    {
        if (! $content = baseReadFile($fileName . '.json')) return null;
        return preg_replace_callback('#(\{([0-9a-zA-Z\_\.\-]+)\|([0-9a-zA-Z\_\-\.]+)\})+#uim', 
            function ($match) {
                if (isset($match[3]) === true && $match[3] && $match[2]) {
                    $attr   = &$match[2];
                    $param  = &$match[3];
                    if (function_exists($attr) === true) {
                        if ($param !== '.') return $attr($param);
                        return $attr();
                    }
                    if (isset($attr[$param]) === true) return $attr[$param];
                    if (isset($attr->{$param}) === true) return $attr->{$param};
                }
                return (isset($match[0]) === true ? $match[0] : null);
            }
        , $content);
    }
}
