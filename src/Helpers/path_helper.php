<?php

if (!function_exists('toSnake')) {
    function toSnake(string $text = ''): string
    {
        if (! $result = str_replace(' ', '', $text)) { return ''; }
        if (ctype_alpha($result) === true && ctype_lower($result) === true) { return $result; }
        return strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1_', $result));
    }
}

if (!function_exists('toCamelCase')) {
    function toCamelCase(string $text = ''): string 
    {
        if (! $text = strtolower(str_replace(' ', '', $text))) { return ''; }
        $result = '';
        foreach (explode('_', $text) as $word) { $result .= ucfirst(trim($word)); }
        return $result;
    }
}

/// Приведение пути к стандарту Класса  -------------------------------------------
if (!function_exists('toClassUrl')) {
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
            if ($suffix && substr($classname, -1 * strlen($suffix)) !== $suffix) {
                return $classname . $suffix;
            }
        }
        return $classname;
    }
}

if (!function_exists('getRootUrl')) {
    function getRootUrl(string $url = ''): string
    {
        $link = rtrim(cutQueryFromUrl($url), '/ ');
        if (! $link) { return $link; }
        $pos = 0;
        if ($start = strpos($link, '://')) {
            $pos = $start + 3;
        } else if (substr($link, 0, 2) === '//') {
            $pos = 2;
        }
        if (! $n = strpos($link, '/', $pos)) { return $link; }
        return substr($link, 0, $n);
    }
}

// Предыдущая Url
if (!function_exists('previousUrl')) {
	function previousUrl(string $url = '', string $delimiter = '/'): string 
	{
		$link = rtrim(cutQueryFromUrl($url), $delimiter . ' ');
		if (! $link) { return $link; }
		if (! $pos = strrpos($link, $delimiter, -1)) { return $link; }
		return substr($link, 0, $pos);
	}
}

/// Чтение данных из файла   -------------------------------------------
if (!function_exists('readingDataFromFile')) {
    function readingDataFromFile(string $fileName = ''): string 
    {
        if ($fileName && is_file($fileName) === true && is_readable($fileName) === true && $data = file_get_contents($fileName)) { 
            return $data; 
        }
        return '';
    }
}

// Запись данных в файл (перезапись) -------------------------------------------
if (!function_exists('writingDataToFile')) {
    function writingDataToFile(string $fileName = '', string $data = '', bool $bkDelete = true): bool 
    {
        if (!$fileName) { return false; }
        $saveResult = 0;
        $buffer = trim(urldecode($data)) . PHP_EOL;
        $length = strlen($buffer);

        if (is_file($fileName)) {
            if (! is_writable($fileName)) { return false; }
            $bkName = $fileName . '.bak.php';
            if (is_file($bkName)) { unlink($bkName); }
            if (rename($fileName, $bkName) === false) { return false; }
        }
        
        $fp = fopen($fileName, 'c'); // 'wb'
        flock($fp, LOCK_EX);
        for ($result = $written = 0, $length; $written < $length; $written += $result) {
            if (($result = fwrite($fp, substr($buffer, $written))) === false) { break; }
            $saveResult += $result;
        }
        flock($fp, LOCK_UN);
        fclose($fp);

        if ($saveResult === $length) { 
            if ($bkDelete === true && isset($bkName)) { @unlink($bkName); }
            return true; 
        }

        if (isset($bkName) && is_file($bkName)) {
            if (is_file($fileName)) { unlink($fileName); }
            rename($bkName, $fileName);
            unlink($bkName); 
        }
        return false;
    }
}

// Перезапись файла
if (!function_exists('baseWriteFile')) {
    function baseWriteFile(string $name = '', string $data = '', bool $bkDelete = true): bool 
    {
        $fileName = WRITEPATH . 'base' . DIRECTORY_SEPARATOR . castingPath($name, true);
        //return (file_put_contents($fileName, trim($data).PHP_EOL, LOCK_EX) !== false ? true : false);
        return writingDataToFile($fileName, $data, $bkDelete);
    }
}

// Чтение файла
if (!function_exists('baseReadFile')) {
    function baseReadFile(string $name = ''): string
    {
        $fileName = WRITEPATH . 'base' . DIRECTORY_SEPARATOR . castingPath($name, true);
        return readingDataFromFile($fileName);
    }
}
// =======================================
if (!function_exists('meLoader')) {
    function meLoader(string $name = '') {
        $content = baseReadFile($name);
        if ($content) {
            return preg_replace_callback('#(\{([0-9a-zA-Z_.-]+)[|]([0-9a-zA-Z_.-]+)})+#', function ($line) {
                if (isset($line[3]) && $line[3] && isset($line[2]) && $line[2]) {
                    try {
                        if (function_exists($line[2])) {
                            if ($line[3] !== '.') { return $line[2]($line[3]); }
                            return $line[2]();
                        } else {
                            if (isset($line[2][$line[3]])) { return $line[2][$line[3]]; }
                            if (isset($line[2]->{$line[3]})) { return $line[2]->{$line[3]}; }
                        }
                    } catch (\Throwable $th) { return $line[0]; }
                }
                return $line[0];
            }, $content);
        }
        return '';
    }
}
