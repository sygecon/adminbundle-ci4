<?php

if (! function_exists('toSnake')) {
    /**
     * toSnake
     * Takes multiple words separated by camel case and underscores them.
     * @param string $string Input string
     */
    function toSnake(string $string): string
    {
        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', trim($string)));
    }
}

if (! function_exists('toCamelCase')) {
    /**
     * toCamelCase
     * Takes multiple words separated by spaces or underscores and converts them to Pascal case,
     * which is camel case with an uppercase first letter.
     * @param string $string Input string
     */
    function toCamelCase(string $string): string 
    {
        $str = lcfirst(str_replace(' ', '', ucwords(preg_replace('/[\s_]+/', ' ', $string))));
        return ucfirst($str);
    }
}

if (! function_exists('underscore')) {
    /**
     * Underscore
     * Takes multiple words separated by spaces and underscores them
     * @param string $string Input string
     */
    function underscore(string $string): string
    {
        $replacement = trim($string);
        return preg_replace('/[\s]+/', '_', $replacement);
    }
}

/// Приведение пути к стандарту Класса  -------------------------------------------
if (! function_exists('toClassUrl')) {
    function toClassUrl(string $string = '', string $suffix = ''): string 
    {
        $classname = str_replace(['-', '|', ' '], ['_', '/', '_'], toUrl($string));
        $classname = trim(str_replace(['__', '//'], ['_', '/'], $classname), '/');
        $classname = toCamelCase($classname);
        if ($suffix) {
            $sfx = ucfirst($suffix);
            if ($sfx && substr($classname, -1 * strlen($sfx)) !== $sfx) return $classname . $sfx;
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
        $path = castingPath($fileName, false);
        if (true === file_exists($path)) { 
            if ($data = file_get_contents($path)) return $data;
        }
        return '';
    }
}

// Запись данных в файл (перезапись) -------------------------------------------
if (! function_exists('writingDataToFile')) {
    function writingDataToFile(string $fileName = '', string $data = '', bool $bkDelete = true): bool 
    {
        $path = castingPath($fileName, false);
        if (! $path) return false;
        $bkName = '';
        $buffer = trim(urldecode($data)) . PHP_EOL;
        $length = mb_strlen($buffer, 'UTF-8');

        if (true === file_exists($path)) {
            $bkName = $path . '.bak.php';
            if (true === file_exists($bkName)) unlink($bkName);
            rename($path, $bkName);
        }

        try {
            $written = file_put_contents($path, $buffer, LOCK_EX);
            if ($written !== false && $written >= $length) { 
                if (true === $bkDelete && $bkName) unlink($bkName);
                return true; 
            }
        } catch (\Throwable $th) {
            $written = 0; 
        }

        if ($bkName && true === file_exists($bkName)) {
            if (true === file_exists($path)) unlink($path);
            rename($bkName, $path);
            if (true === file_exists($bkName)) unlink($bkName);
        }
        return false;
    }
}

// Перезапись файла
if (! function_exists('baseWriteFile')) {
    function baseWriteFile(string $path = '', string $data = '', bool $bkDelete = true): bool 
    {
        if (! isset($path)) return false;
        if (! $path) return false;
        return writingDataToFile(WRITEPATH . 'base' . DIRECTORY_SEPARATOR . $path, $data, $bkDelete);
    }
}

// Чтение файла
if (! function_exists('baseReadFile')) {
    function baseReadFile(string $path): string
    {
        if (! isset($path)) return '';
        if (! $path) return '';
        return readingDataFromFile(WRITEPATH . 'base' . DIRECTORY_SEPARATOR . $path);
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
