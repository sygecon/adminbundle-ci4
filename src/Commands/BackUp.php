<?php

namespace Sygecon\AdminBundle\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\I18n\Time; 
use Sygecon\AdminBundle\Config\BackUp as Config;
use Throwable;

// php spark make:backup
class BackUp extends BaseCommand
{
    protected $group        = 'Generators';
    protected $name         = 'make:backup';
    protected $usage        = 'make:backup';
    protected $description  = 'Database and files backup.';

    protected $configDb;

    protected string $pathName;

    public function run(array $params)
    {
        CLI::write('Preparing processes ...');
        $this->pathName = date_format(date_create(new Time('now')), Config::FORMAT_FOLDER_NAME);
        helper('files');
        $path = Config::FOLDER . DIRECTORY_SEPARATOR . $this->pathName;
            
        if(createPath($path) === false) { 
            CLI::write('Error! Can`t create folder.');
            return; 
        }
        $path .= DIRECTORY_SEPARATOR;
        $isZip = class_exists('ZipArchive');
        $this->configDb = config('Database');
        try {
            CLI::write('Database backup');
            foreach(Config::DB_BACKUP as $nameDb) {
                $this->buildDb($nameDb, $path);
            }

            CLI::write('Files backup');
            foreach(Config::FOLDERS_BACKUP as $folder) {
                if ($isZip) { 
                    pathToZip($folder, $path);
                } else {
                    copyPath($folder, $path);
                }  
            }

            CLI::write('Checking the maximum number of backups in a folder.');
            $this->checkingMaxFolders();

            CLI::write('Process completed.');
        } catch (Throwable $th) {
            $this->showError($th);
        }
    }

    private function buildDb(string $nameDb, string $path): void 
    {
        if (! isset($this->configDb->{$nameDb})) { return; }
        $base = $this->configDb->{$nameDb};
        $driver = strtolower($base['DBDriver']); 
        if (! isset(Config::DB_TYPE[$driver])) { return; }
        $class = Config::PREFIX_DUMPER_DB . Config::DB_TYPE[$driver];

        if (class_exists($class) === false) { return; }
        
        $dumpFile = strtolower($base['database']) . '_' . ($driver) . '.sql';
        $dump = new $class($base);
        $dump->dumpToFile($path . $dumpFile);
    }

    private function checkingMaxFolders(): void
    {
        $result = [];
        $len = (Config::MAXIMUM_BACKUPS ? (int) Config::MAXIMUM_BACKUPS : (int) 1);
        
        $folders = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(Config::FOLDER, \RecursiveDirectoryIterator::SKIP_DOTS), 
            \RecursiveIteratorIterator::SELF_FIRST);
        foreach($folders as $fileinfo){
            if (! $fileinfo->isDir()) { continue; }
            $name = $fileinfo->getFilename();
            if ($name === $this->pathName) { continue; }
            if (! $key = strpos($name, '_')) { continue; }
            if (! $name = substr($name, $key)) { continue; }
            $name = str_replace('_', '-', trim($name, '_ '));
            if ($key = strtotime($name)) { $result[$key] = $fileinfo->getRealPath(); }
        }
        if (count($result) <= $len) { return; }

        helper('files');
        ksort($result, SORT_NUMERIC); 
        array_splice($result, count($result) - $len);

        foreach($result as $key => $path) {
            deletePath($path);
            unset($result[$key]);
        }
        unset($result);
    }
}