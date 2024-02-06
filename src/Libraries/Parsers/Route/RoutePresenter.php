<?php

declare(strict_types=1);

namespace Sygecon\AdminBundle\Libraries\Parsers\Route;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class RoutePresenter extends AbstractRouteRest
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
        'remove',
        'delete',
    ];

    protected string $codeTemplate = "\$routes->presenter('%s', %s);";
}
