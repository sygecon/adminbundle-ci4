<?php namespace Sygecon\AdminBundle\Controllers;

final class ManagePartitions extends AdminController
{
	public function index($page = 'index'): string
	{
		return $this->build($page, ['head' => ['h1' => lang('Admin.goWelcome')]]);
	}
}
