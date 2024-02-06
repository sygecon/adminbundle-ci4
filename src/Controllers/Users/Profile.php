<?php
declare(strict_types=1);
namespace Sygecon\AdminBundle\Controllers\Users;

use CodeIgniter\HTTP\ResponseInterface;
use Sygecon\AdminBundle\Controllers\AdminController;

final class Profile extends AdminController
{
	private $user;

	public function __construct() {
        $this->user = auth()->user();
    }

	public function index(string $query = 'profile'): ResponseInterface 
	{
		return $this->respond($this->build($query, ['head' => ['h1' => lang('Admin.goWelcome')]], 'User'), 200);
	}

}
