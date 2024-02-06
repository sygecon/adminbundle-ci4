<?php

namespace Sygecon\AdminBundle\Commands\Generators;

class AspModelDirectGenerator extends AspModelGenerator
{
    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'make:app-model-direct';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Generates a new Direct model file.';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'make:app-model-direct <name> [options]';

    /**
     * Actually execute a command.
     */
    public function run(array $params)
    {
        $this->component = 'Model';
        $this->directory = 'Models' . DIRECTORY_SEPARATOR . 'Layout';
        $this->template  = 'app.model.direct.tpl.php';
        $this->classNameLang = 'CLI.generator.className.model';
        $this->execute($params);
    }
}
