<?php

namespace Sygecon\AdminBundle\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
// use CodeIgniter\I18n\Time; 
use Sygecon\AdminBundle\Libraries\HTML\Dom\Parser;
use Sygecon\AdminBundle\Libraries\HTML\Import as Builder;
use Throwable;

// # php spark make:import about build
// after
// # php spark make:import about

class Import extends BaseCommand
{
    protected $group        = 'Generators';
    protected $name         = 'make:import';
    protected $usage        = 'make:import';
    protected $description  = 'Import files and HTML data from a remote site..';

    public function run(array $params)
    {
        if (! $name = $params[0]) {
            CLI::write('Error! Project not found.');
            return;
        }

        if (isset($params[1]) && $params[1] === 'build') {
            if ($result = $this->parsing($name)) { CLI::write('Error! ' . $result); }
            return;
        }   

        try {
            CLI::write('Preparing processes ...');

            $import = new Builder();
            $import->build($name);
            
            CLI::write('Process completed.');
        } catch (Throwable $th) {
            $this->showError($th);
        }
    }

    /**
     * 
     */
    private function parsing(string $name): string
    {
        try {
            CLI::write('Loading data.');

            $parser = new Parser($name);
            $parser->build();

            CLI::write('Data from the remote resource was successfully loaded and saved.');
            return ''; 
        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }
}