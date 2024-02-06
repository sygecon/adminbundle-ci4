<?php

namespace Sygecon\AdminBundle\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Sygecon\AdminBundle\Libraries\Seo\SeoModel;
use Throwable;

// php spark make:seobuild
class SeoBuilder extends BaseCommand
{
    protected $group        = 'Generators';
    protected $name         = 'make:seobuild';
    protected $usage        = 'make:seobuild';
    protected $description  = 'Generates a new Sitemap.xml file and Robots.txt file.';

    public function run(array $params)
    {
        ignore_user_abort(true);
        set_time_limit(0);
        CLI::write('The process of generating new files: sitemap.xml and robots.txt');
        try {
            $seoModel = new SeoModel();
            $seoModel->build();
            CLI::write('Process completed!');
        } catch (Throwable $th) {
            $this->showError($th);
        }
    }
}