<?php namespace Sygecon\AdminBundle\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

final class ManagePartitions extends AdminController
{
	public function index($page = 'index'): ResponseInterface
	{
		return $this->respond($this->build($page, ['head' => ['h1' => lang('Admin.goWelcome')]]), 200);
	}
}
