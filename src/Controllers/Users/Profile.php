<?php
declare(strict_types=1);
namespace Sygecon\AdminBundle\Controllers\Users;

use Sygecon\AdminBundle\Controllers\AdminController;

final class Profile extends AdminController
{
	public function index(string $query = 'profile'): string 
	{
		return $this->build($query, ['head' => ['h1' => lang('Admin.goWelcome')]], 'User');
	}

}
