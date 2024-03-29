<?php

declare(strict_types=1);

namespace Sygecon\AdminBundle\Libraries\Parsers\Route;

use CodeIgniter\Config\Services;

use function array_keys;
use function file_put_contents;

use const LOCK_EX;

final class RouteFileGenerator
{
    private ControllerFinder $finder;
    private AttributeReader $reader;

    /**
     * @var string Full path to the output file
     */
    private string $routesFile;

    /**
     * @param string[] $namespaces namespaces to search
     * @param string   $routesFile Full path to the output file
     */
    public function __construct(array $namespaces = [], string $routesFile = '')
    {
        if ($namespaces === []) {
            $namespaces = array_keys(Services::autoloader()->getNamespace());
        }

        if ($routesFile === '') {
            $routesFile = APPPATH . 'Config/RoutesFromAttribute.php';
        }

        $this->routesFile = $routesFile;
        $this->finder     = new ControllerFinder($namespaces);
        $this->reader     = new AttributeReader();
    }

    /**
     * @return list<Route|RouteGroup|RoutePresenter|RouteResource>
     */
    public function getRoutes(): array
    {
        $controllers = $this->finder->find();

        $routes = [];

        foreach ($controllers as $controller) {
            $routes = [...$routes, ...$this->reader->getRoutes($controller)];
        }

        return $routes;
    }

    public function getRoutesCode(): string
    {
        $routes = $this->getRoutes();

        $code = '';

        foreach ($routes as $route) {
            $code .= $route->asCode();
        }

        return $code;
    }

    /**
     * @return string successful message
     */
    public function generate(): string
    {
        $code = <<<'PHP'
            <?php
            // This file is automatically generated by ci4-attribute-routes.
            // Please do not modify this file.

            PHP;
        $code .= $this->getRoutesCode();

        file_put_contents($this->routesFile, $code, LOCK_EX);

        return clean_path($this->routesFile) . ' generated.';
    }
}
