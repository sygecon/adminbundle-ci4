<?php

// Создание папки если её нет  -------------------------------------------
if (! function_exists('createPath')) {
    function createPath(string $path): bool 
    {
        $dir = castingPath($path);
        if (is_dir($dir) === true) return true;
        try {
            return mkdir($dir, 0755, true);
        } catch (\Throwable $th) {
            return false;
        }
    }
}

// Создание файла  -------------------------------------------
if (! function_exists('createFile')) {
    function createFile(string $fileName, string $data): bool 
    {
        $fname  = checkFileName(basename($fileName));
        $dir    = castingPath(dirname($fileName));
        if (! $fname || $fname[0] === '.' || $fname === $dir) return false;
        if (is_dir($dir) === false) { 
            if (mkdir($dir, 0755, true) === false) return false;
        }
        $r = file_put_contents($dir . DIRECTORY_SEPARATOR . $fname, trim($data) . PHP_EOL, LOCK_EX);
        return ($r === false ? false : true);
    }
}

// Удаление папки с файлами  ------------------------------------------- 
if (! function_exists('deletePath')) {
    function deletePath(string $path = '', bool $startDelete = true): bool 
    {
        $dir = castingPath($path);
        if (is_file($dir) === true) { 
            if ($startDelete === true) { return unlink($dir); }
            return false;
        }
        if (is_dir($dir) === false) { return false; }
        $folders = [];

        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, 
                \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST
            ) as $fileInfo) 
        {
            $pathname = $fileInfo->getPathname();
            if (is_link($pathname)) {
                if (!(unlink($pathname) || '\\' !== \DIRECTORY_SEPARATOR || rmdir($pathname)) && file_exists($pathname)) {
                    return false;
                }
            } else
            if ($fileInfo->isDir()) {
                $folders[] = $pathname;
            } else {
                if (true === file_exists($pathname)) unlink($pathname);
            }
        }
        foreach($folders as $pathname) { rmdir($pathname); }
        if ($startDelete === true && is_dir($dir) === true) return rmdir($dir);
        return true;
    }
}

// Удаление файла  -------------------------------------------
if (! function_exists('deleteFile')) {
    function deleteFile(string $filename): bool 
    {
        $file = castingPath($filename);
        if (true === file_exists($file)) return unlink($file);
        return false;
    }
}

// Переименование папки или файла  -------------------------------------------
if (! function_exists('renameFile')) {
    function renameFile(string $path, string $oldName, string $newName): string 
    {
        if (! $dst = castingPath($newName)) return '';
        $src = castingPath($path) . DIRECTORY_SEPARATOR . castingPath($oldName, true);

        if (true === is_file($src)) {
            $dst .= '.' . pathinfo($oldName, PATHINFO_EXTENSION);
        } else 
        if (false === is_dir($src)) return '';

        $dst = dirname($src) . DIRECTORY_SEPARATOR . $dst;
        if ($src === $dst) return '';
        if (true === file_exists($dst)) return '';

        if (rename($src, $dst) === false) return '';
        return $newName;
    }
}

// Копировать или переместить файл в указанную папку -------------------------------------------
if (! function_exists('copyFile')) {
    function copyFile(string $srcFile = '', string $dstPath = '', bool $move = false): bool 
    {
        $dir = castingPath($dstPath);
        $src = castingPath($srcFile);
        if (false === file_exists($src)) return false;
        if (false === is_dir($dir)) { 
            if (! mkdir($dir, 0755, true)) return false;
        }
        $filename = $dir . DIRECTORY_SEPARATOR . basename($src);
        return (true === $move ? rename($src, $filename) : copy($src, $filename));
    }
}

// Копировать папку -----------------------------------------------------------
if (! function_exists('copyPath')) {
    function copyPath(string $srcPath = '', string $dstPath = '', bool $move = false): bool
    {
        $src = castingPath($srcPath);
        $dst = castingPath($dstPath);
        if (is_dir($src) === false) return false;
        if (createPath($dst) === false) return false;

        $dirLength  = strlen($src);
        $iterator   = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $fileInfo) {
            $origin = $fileInfo->getPathname();
            $target = $dst . substr($origin, $dirLength);
            // $target = $dst . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($fileInfo->isDir()) {
                if (false === is_dir($target)) mkdir($target, 0755, true);
            } else {
                if (true === $move) {
                    rename($origin, $target);
                } else {
                    copy($origin, $target);
                }
                if (is_file($target) === true) {
                    chmod($target, fileperms($target) | (fileperms($origin) & 0111));
                    touch($target, filemtime($origin));
                }
            }
        }
        return true;
    }
}

// Архивировать папку
if (! function_exists('pathToZip')) {
    function pathToZip(string $folderSrc, string $folderDst): bool
    {
        $folderSrc = castingPath($folderSrc);
        $folderDst = castingPath($folderDst);
        if (! is_dir($folderSrc)) return false;
        if (! $folderDst) return false;

        $dirName = pathInfo($folderSrc, PATHINFO_BASENAME);
        $exclusiveLength = strlen(pathInfo($folderSrc, PATHINFO_DIRNAME));
        $exclusiveLength++;
        if (! is_dir($folderDst)) { 
            if (! mkdir($folderDst, 0755, true)) return false;
        }

        if (! $zip = new \ZipArchive()) return false;
        $zip->open($folderDst . DIRECTORY_SEPARATOR . $dirName . '.zip', \ZipArchive::CREATE);
        $zip->addEmptyDir($dirName);
        
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($folderSrc, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $fileInfo) {
                $filePath = $folderSrc . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
                $localPath = substr($filePath, $exclusiveLength);
                if ($fileInfo->isDir()) {
                    $zip->addEmptyDir($localPath);
                } else {
                    $zip->addFile($filePath, $localPath);
                }
            }
            $zip->close();
            return true;
        } catch (\Throwable $th) {
            if ($zip) $zip->close();
            return false;
        }
    }
}
