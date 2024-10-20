<?php
declare(strict_types=1);
namespace Sygecon\AdminBundle\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
// use CodeIgniter\I18n\Time;
use Config\Boot\Update as Config;
use App\Libraries\Loader\Githab;
use Sygecon\AdminBundle\Libraries\LibraryLoader;
use Throwable;

// # php spark make:update
class Update extends BaseCommand
{
    protected $group        = 'Generators';
    protected $name         = 'make:update';
    protected $usage        = 'make:update';
    protected $description  = 'Updating libraries from a remote site...';

    public function run(array $params)
    {
        ignore_user_abort(true);
        set_time_limit(0);

        $name = null;
        $isGit = (isset($params[0]) ? false : true); 
        
        if ($isGit === false) {
            $name = $params[0];
        }

        try {
            CLI::write('Preparing processes ...');
            if ($isGit === true) {
                $gitHab = new Githab();
                $libraries = $gitHab->make();
                CLI::write('The libraries were downloaded from the GitHub cloud platform.');

                if ($libraries) {
                    $loader = new LibraryLoader();
                    $loader->build($libraries);
                }
                return;
            }
            
            $loader = new LibraryLoader($name);
            $loader->build();
        } catch (Throwable $th) {
            $this->showError($th);
        }
    }
}