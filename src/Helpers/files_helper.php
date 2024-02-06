<?php

// Создание папки если её нет  -------------------------------------------
if (!function_exists('createPath')) {
    function createPath(string $dir = ''): bool 
    {
        $dir = castingPath($dir);
        if (is_dir($dir)) { return true; }
        return mkdir($dir, 0755, true);
    }
}

// Создание файла  -------------------------------------------
if (!function_exists('createFile')) {
    function createFile(string $fileName = '', string $data = ' '): bool 
    {
        $fileName = castingPath($fileName);
        if ($fileName && mb_substr($fileName, 0, 1) !== '.') {
            if (file_put_contents($fileName, $data, LOCK_EX) !== false) { return true; }
        }
        return false;
    }
}

// Удаление папки с файлами  ------------------------------------------- 
if (!function_exists('deletePath')) {
    function deletePath(string $dir = '', bool $startDelete = true): bool 
    {
        $dir = castingPath($dir);
        if (is_file($dir)) { 
            if ($startDelete === true) { return unlink($dir); }
            return false;
        }
        if (! is_dir($dir)) { return false; }
        $folders = [];

        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, 
                \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST
            ) as $fileInfo) 
        {
            if ($fileInfo->isDir()) {
                $folders[] = $fileInfo->getRealPath();
                continue;
            }
            unlink($fileInfo->getRealPath());
        }

        if ($folders) {
            usort($folders, 
                function ($a, $b) { 
                    $lenA = explode(DIRECTORY_SEPARATOR, $a);
                    $lenB = explode(DIRECTORY_SEPARATOR, $b);
                    if ($lenA === $lenB) { return $a < $b; }
                    return $lenA < $lenB;
                }
            );
            foreach($folders as $pathName) { rmdir($pathName); }
        }
        
        if ($startDelete === true) { return rmdir($dir); }
        return true;
    }
}

// Удаление файла  -------------------------------------------
if (!function_exists('deleteFile')) {
    function deleteFile(string $file = ''): bool 
    {
        $file = castingPath($file);
        if (is_file($file)) { return unlink($file); }
        return false;
    }
}

// Переименование папки или файла  -------------------------------------------
if (!function_exists('renameFile')) {
    function renameFile(string $path = '', string $oldName = '', string $newName = '') 
    {
        helper('path');
        $oldName = castingPath($path) . DIRECTORY_SEPARATOR . castingPath($oldName, true);
        if (! file_exists($oldName)) { return false; }
        if (! $newName = checkFileName($newName)) { return false; }
        $resName = $newName;
        $fileInfo = pathinfo($oldName);
        $path = $fileInfo['dirname'] . DIRECTORY_SEPARATOR;
        $fname = $fileInfo['basename'];
        if (is_file($path . $fname)) {
            $newName .= '.' . $fileInfo['extension'];
        } else if (! is_dir($path . $fname)) {
            return false; 
        } 
        if (file_exists($path . $newName)) { return false; }
        if ($newName === $fname) { return false; }
        
        if (rename($path . $fname, $path . $newName)) { return $resName; }
        return false;
    }
}

if (!function_exists('copyFile')) {
    function copyFile(string $srcFile = '', string $dstPath = '', bool $move = false): bool 
    {
        $dstPath = castingPath($dstPath);
        if (! is_dir($dstPath)) return false;
        if (!is_file($srcFile)) return false;
        $file = $dstPath . DIRECTORY_SEPARATOR . basename($srcFile);
        if (is_file($file)) unlink($file);
        return ($move ? rename($srcFile, $file) : copy($srcFile, $file));
    }
}

if (!function_exists('copyPath')) {
    function copyPath(string $src = '', string $dst = '', bool $move = false): bool
    {
        $src = castingPath($src);
        $dst = castingPath($dst);
        if (is_dir($src) === false) { return false; }
        deletePath($dst);
        if (createPath($dst) === false) { return false; }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($src, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $file) {
                $subPath = $iterator->getSubPathName();
                if ($file->isDir()) {
                    mkdir($dst . DIRECTORY_SEPARATOR . $subPath);
                } else {
                    $move 
                    ? rename($file, $dst . DIRECTORY_SEPARATOR . $subPath) 
                    : copy($file, $dst . DIRECTORY_SEPARATOR . $subPath);
                }
            }
            return true;
        } catch (\Throwable $th) { return false; }
    }
}

// Архивировать папку
if (!function_exists('pathToZip')) {
    function pathToZip(string $folderSrc = '', string $folderDst = ''): bool
    {
        $folderSrc = castingPath($folderSrc);
        $folderDst = castingPath($folderDst);
        if (! is_dir($folderSrc)) { return false; }
        if (! $folderDst) { return false; }

        $dirName = pathInfo($folderSrc, PATHINFO_BASENAME);
        $exclusiveLength = strlen(pathInfo($folderSrc, PATHINFO_DIRNAME));
        $exclusiveLength++;
        if (! is_dir($folderDst)) { 
            if (! mkdir($folderDst, 0755, true)) { return false; }
        }
        
        $zip = new \ZipArchive();
        $zip->open($folderDst . DIRECTORY_SEPARATOR . $dirName . '.zip', \ZipArchive::CREATE);
        $zip->addEmptyDir($dirName);
        if (! $zip) { return false; }
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($folderSrc, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $file) {
                $filePath = $folderSrc . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
                $localPath = substr($filePath, $exclusiveLength);
                if ($file->isDir()) {
                    $zip->addEmptyDir($localPath);
                } else {
                    $zip->addFile($filePath, $localPath);
                }
            }
            $zip->close();
            return true;
        } catch (\Throwable $th) {
            if ($zip) { $zip->close(); }
            return false;
        }
    }
}

/*** Функция транслитерации текста */
if (!function_exists('translatelt')) {
    function translatelt(string $text = ''): string 
    {
        $L['ru'] = [
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
        ];
        $L['en'] = [
            'YO', 'ZH',  'CZ', 'CH', 'SHH', 'SH', 'Y',
            'E', 'YU',  'YA', 'yo', 'zh', 'cz', 'ch',
            'sh', 'shh', 'y', 'e', 'yu', 'ya', 'A',
            'B', 'V',  'G',  'D',  'E',  'Z',  'I',
            'Y',  'K',   'L',  'M',  'N',  'O',  'P',
            'R',  'S',   'T',  'U',  'F',  'X',  '',
            '',  'a',   'b',  'v',  'g',  'd',  'e',
            'z',  'i',   'y',  'k',  'l',  'm',  'n',
            'o',  'p',   'r',  's',  't',  'u',  'f',
            'x',  '',  ''
        ];
        return str_replace($L['ru'], $L['en'], $text);
    }
}
