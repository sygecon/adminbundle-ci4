<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Sygecon\AdminBundle\Commands\Generators;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\CLI\GeneratorTrait;

/**
 * Generates a skeleton controller file.
 */
class AspControllerGenerator extends BaseCommand 
{
    use GeneratorTrait;

    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'Generators';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'make:app-controller';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Generates a new controller file.';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'make:app-controller <name> [options]';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [
        'name' => 'The controller class name.',
    ];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [
        '--bare'      => 'Extends from CodeIgniter\Controller instead of AppController.',
        '--model'     => 'Model name.',
        '--restful'   => 'Extends from a RESTful resource, Options: [controller, presenter]. Default: "controller".',
        '--namespace' => 'Set root namespace. Default: "APP_NAMESPACE".',
        '--suffix'    => 'Append the component title to the class name (e.g. User => UserController).',
        '--force'     => 'Force overwrite existing file.',
    ];

    /**
     * Actually execute a command.
     */
    public function run(array $params) {
        $this->component = 'Controller';
        $this->directory = 'Controllers';
        $this->template  = 'controller.tpl.php';
        $this->classNameLang = 'CLI.generator.className.controller';
        $this->execute($params);
    }

    /**
     * Prepare options and do the necessary replacements.
     */
    protected function prepare(string $class): string {
        $bare = $this->getOption('bare');
        $rest = $this->getOption('restful');
        $model = $this->getOption('model');
        $isModel = false;
        $useStatement = trim(APP_NAMESPACE, '\\') . '\Controllers\Boot\AppController';
        
        if ($model) {
            $model = trim(str_replace('/', '\\', $model), ' \\');
            if ($model) {
                $isModel = true;
                //$model = 'use App\Models\\' . $model . ' as BaseModel;';
            }
        } else {
            $model = '';
        }
        // Gets the appropriate parent class to extend.
        if ($bare || $rest) {
            if ($bare) {
                $useStatement = 'CodeIgniter\Controller';
            } elseif ($rest) {
                $rest = is_string($rest) ? $rest : 'controller';

                if (!in_array($rest, ['controller', 'presenter'], true)) {
                    // @codeCoverageIgnoreStart
                    $rest = CLI::prompt(lang('CLI.generator.parentClass'), ['controller', 'presenter'], 'required');
                    CLI::newLine();
                    // @codeCoverageIgnoreEnd
                }

                if ($rest === 'controller') {
                    $useStatement = 'CodeIgniter\RESTful\ResourceController';
                } elseif ($rest === 'presenter') {
                    $useStatement = 'CodeIgniter\RESTful\ResourcePresenter';
                }
            }
        }
        return $this->parseTemplate(
            $class,
            ['{useStatement}', '{extends}', '{model}'],
            [$useStatement, substr(strrchr($useStatement, '\\'), 1), $model],
            ['type' => $rest, 'isModel' => $isModel]
        );
    }
}
