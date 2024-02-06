<?php

declare(strict_types=1);

namespace Sygecon\AdminBundle\Libraries\Parsers\Route;

use CodeIgniter\Autoloader\FileLocator;
use CodeIgniter\Config\Services;

use function is_file;

final class ControllerFinder
{
    /**
     * @var string[]
     */
    private array $namespaces;

    private FileLocator $locator;

    private string $filtrNamespace = 'Controllers';

    private bool $filtrOnlyPath = true;
    /**
     * @param string[] $namespaces namespaces to search
     */
    public function __construct(array $namespaces)
    {
        $this->namespaces = $namespaces;
        $this->locator    = Services::locator();
    }

    /**
     * @return class-string[]
     */
    public function find($filtrClass = ''): array
    {
        $classes = [];
        $needle = Chr(92) . 'Boot' . Chr(92);
        foreach ($this->namespaces as $namespace) {
            $files = $this->locator->listNamespaceFiles($namespace, $this->filtrNamespace);
            foreach ($files as $file) {
                if (is_file($file)) {
                    $name = strtolower(basename($file, ".php"));
                    $classnameOrEmpty = $this->locator->getClassname($file);
                    if ($classnameOrEmpty !== '') {
                        /** @var class-string $classname */
                        $classname = $classnameOrEmpty;
                        if (strripos($classnameOrEmpty, $needle) !== false) {
                            continue;
                        }
                        if ($this->filtrOnlyPath && strtolower($classnameOrEmpty) !== strtolower($namespace . '\\' . $this->filtrNamespace). '\\' . $name) {
                            continue;
                        }
                        if ($filtrClass && $name === $filtrClass) {
                            $classes[] = $classname;
                            break;
                        } 
                        $classes[] = $classname;
                    }
                }
            }
        }
        return $classes;
    }
}
