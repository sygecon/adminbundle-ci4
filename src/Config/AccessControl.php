<?php 
declare(strict_types=1);
namespace Sygecon\AdminBundle\Config;

final class AccessControl
{
	public const FILTER = [
		'direction' => 'permission:admin.access',
		'users' 	=> 'permission:admin.users',
		'reports' 	=> 'permission:admin.reports',
		'catalog' 	=> 'permission:admin.catalog',
		'backend' 	=> 'permission:admin.backend',
		'frontend' 	=> 'permission:admin.frontend',
		'clients'   => 'permission:clients.access',
		'staff'     => 'permission:staff.access',
		'import'    => 'permission:admin.import',
	];

	public const BLOCK_LIST_URL = [
        '/admin',
        '/direct',
        '/login',
        '/auth',
        '/user',
    ]; 
}