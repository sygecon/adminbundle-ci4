<?php

namespace Sygecon\AdminBundle\Libraries\Dumper\Exceptions;

use Exception;

class CannotSetParameter extends Exception
{
    public static function conflictingParameters(string $name, string $conflictName): static
    {
        return new static("Cannot set `{$name}` because it conflicts with parameter `{$conflictName}`.");
    }
}
