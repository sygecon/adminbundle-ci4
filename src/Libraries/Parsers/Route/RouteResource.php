<?php

declare(strict_types=1);

namespace Sygecon\AdminBundle\Libraries\Parsers\Route;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class RouteResource extends AbstractRouteRest
{
    /**
     * @var string[]
     */
    protected array $validMethods = [
        'index',
        'show',
        'new',
        'create',
        'edit',
        'update',
        'delete',
    ];

    protected string $codeTemplate = "\$routes->resource('%s', %s);";
}
