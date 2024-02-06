<?php
namespace Sygecon\AdminBundle\Controllers\Api;

use CodeIgniter\Controller;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

final class AspSeo extends Controller
{
    use ResponseTrait;

    public function me_build(): ResponseInterface
    {
        if (auth()->loggedIn() && $this->request->isAJAX()) {
            command('make:seobuild');
            return $this->respond('1', 200);
        }
        return $this->respond('0', 200);
    }
}