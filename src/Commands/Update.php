<?php
declare(strict_types=1);
namespace Sygecon\AdminBundle\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
// use CodeIgniter\I18n\Time;
use App\Libraries\Loader\Githab;
use Sygecon\AdminBundle\Libraries\LibraryLoader;
use Throwable;

// # php spark make:import about build
// after
// # php spark make:import about
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
        $isUpdate = true;
        if ($params[0] && $params[0] === 'install') { $isUpdate = false; } 

        try {
            CLI::write('Preparing processes ...');
            $gitHab = new Githab();
            if ($result = $gitHab->build($isUpdate)) {
                $loader = new LibraryLoader();
                $loader->build($result);
            }
        } catch (Throwable $th) {
            $this->showError($th);
        }
    }
}