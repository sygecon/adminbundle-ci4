<?php

// Создание папки если её нет  -------------------------------------------
if (! function_exists('createPath')) {
    function createPath(string $path): bool 
    {
        $dir = castingPath($path);
        if (is_dir($dir) === true) return true;
        try {
            mkdir($dir, 0755, true);
            return is_dir($dir);
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
            $origin = $fileInfo->getPathname();
            if (is_link($origin)) {
                if (!(unlink($origin) || '\\' !== \DIRECTORY_SEPARATOR || rmdir($origin)) && file_exists($origin)) {
                    return false;
                }
            } else
            if ($fileInfo->isDir()) {
                $folders[] = $origin;
            } else {
                unlink($origin);
            }
        }

        if ($folders) {
            usort($folders, function($a, $b) { 
                $na = substr_count($a, DIRECTORY_SEPARATOR);
                $nb = substr_count($b, DIRECTORY_SEPARATOR);
                if ($na === $nb) return $a < $b;
                return $na < $nb;
            });
            foreach($folders as $pathName) { rmdir($pathName); }
        }
        
        if ($startDelete === true && is_dir($dir) === true) return rmdir($dir);
        return true;
    }
}

// Удаление файла  -------------------------------------------
if (! function_exists('deleteFile')) {
    function deleteFile(string $fileName): bool 
    {
        $file = castingPath($fileName);
        if (file_exists($file) === true) { return unlink($file); }
        return false;
    }
}

// Переименование папки или файла  -------------------------------------------
if (! function_exists('renameFile')) {
    function renameFile(string $path, string $oldName, string $newName): string 
    {
        if (! $dst = castingPath($newName)) return '';
        $src = castingPath($path) . DIRECTORY_SEPARATOR . castingPath($oldName, true);

        if (is_file($src) === true) {
            $dst .= '.' . pathinfo($oldName, PATHINFO_EXTENSION);
        } else if (is_dir($src) === false) { return ''; } 

        $dst = dirname($src) . DIRECTORY_SEPARATOR . $dst;
        if ($src === $dst) return '';
        if (file_exists($dst) === true) return '';

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
        if (is_file($src) === false) return false;
        if (is_dir($dir) === false) { 
            if (mkdir($dir, 0755, true) === false) return false;
        }
        $file = $dir . DIRECTORY_SEPARATOR . basename($src);
        return ($move ? rename($src, $file) : copy($src, $file));
    }
}

// Копировать папку -----------------------------------------------------------
if (! function_exists('copyPath')) {
    function copyPath(string $srcPath = '', string $dstPath = '', bool $move = false): bool
    {
        $src = castingPath($srcPath);
        $dst = castingPath($dstPath);
        $originDirLen = strlen($src);
        if (is_dir($src) === false) return false;
        if (createPath($dst) === false) return false;
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $origin = $file->getPathname();
            $target = $dst.substr($origin, $originDirLen);
            // $dir = $dst . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($file->isDir()) {
                if (is_dir($target) === false) mkdir($target, 0755, true);
            } else {
                if ($move === true) rename($origin, $target); else copy($origin, $target);
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
            if ($zip) $zip->close();
            return false;
        }
    }
}
