<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sygecon\AdminBundle\Libraries\Process\Messenger;

use Sygecon\AdminBundle\Libraries\Process\Exception\ProcessFailedException;
use Sygecon\AdminBundle\Libraries\Process\Exception\RunProcessFailedException;
use Sygecon\AdminBundle\Libraries\Process\Process;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RunProcessMessageHandler
{
    public function __invoke(RunProcessMessage $message): RunProcessContext
    {
        $process = new Process($message->command, $message->cwd, $message->env, $message->input, $message->timeout);

        try {
            return new RunProcessContext($message, $process->mustRun());
        } catch (ProcessFailedException $e) {
            throw new RunProcessFailedException($e, new RunProcessContext($message, $e->getProcess()));
        }
    }
}
