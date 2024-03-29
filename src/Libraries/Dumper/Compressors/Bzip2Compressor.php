<?php

namespace Sygecon\AdminBundle\Libraries\Dumper\Compressors;

class Bzip2Compressor implements Compressor
{
    public function useCommand(): string
    {
        return 'bzip2';
    }

    public function useExtension(): string
    {
        return 'bz2';
    }
}
