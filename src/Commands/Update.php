<?php
declare(strict_types=1);
namespace Sygecon\AdminBundle\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
// use CodeIgniter\I18n\Time;
// use Config\Boot\Update as Config;
use App\Libraries\Loader\Githab;
use Sygecon\AdminBundle\Libraries\LibraryLoader;
use Throwable;

// # php spark make:update
// # php spark make:update framework
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

        $prm = null;
        if (isset($params[0]) && is_string($params[0]) && $params[0] !== '') {
            $prm = strtolower($params[0]);
        }

        try {
            CLI::write('Preparing processes ...');
            if ($prm === null) {
                $gitHab = new Githab();
                $libraries = $gitHab->make();
                CLI::write('The libraries were downloaded from the GitHub cloud platform.');

                if ($libraries) {
                    $loader = new LibraryLoader();
                    $loader->build($libraries);
                }
                return;
            }

            if ($prm === 'framework') {
                CLI::write('The CodeIgniter 4 Framework update process has started! Please wait for it to be completed.');
            }
            $loader = new LibraryLoader($prm);
            
            if ($prm !== 'framework') {
                $loader->build();
            }
            CLI::write('The process is completed.');
        } catch (Throwable $th) {
            $this->showError($th);
        }
    }
}