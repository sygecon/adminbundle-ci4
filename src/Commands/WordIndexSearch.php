<?php

namespace Sygecon\AdminBundle\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Sygecon\AdminBundle\Libraries\Parsers\SearchBuilder;
use Throwable;

// php spark make:sitemap
class WordIndexSearch extends BaseCommand
{
    protected $group        = 'Generators';
    protected $name         = 'make:search-index';
    protected $usage        = 'make:search-index';
    protected $description  = 'Indexing site pages by words and their syntactical analysis.';

    public function run(array $params)
    {
        ignore_user_abort(true);
        set_time_limit(0);
        CLI::write('Indexing site pages by words and their syntactical analysis');
        try {
            $indexWord = new SearchBuilder();
            $indexWord->builder();
            CLI::write('Process completed!');
        } catch (Throwable $th) { $this->showError($th); }
    }
}