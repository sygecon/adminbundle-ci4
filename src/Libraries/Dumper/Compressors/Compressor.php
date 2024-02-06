<?php

namespace Sygecon\AdminBundle\Libraries\Dumper\Compressors;

interface Compressor
{
    public function useCommand(): string;

    public function useExtension(): string;
}
