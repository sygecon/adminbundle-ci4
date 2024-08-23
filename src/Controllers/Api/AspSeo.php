<?php
namespace Sygecon\AdminBundle\Controllers\Api;

use CodeIgniter\Controller;

final class AspSeo extends Controller
{
    public function me_build(): string
    {
        if (auth()->loggedIn() && $this->request->isAJAX()) {
            command('make:seobuild');
            return jsonEncode(['status' => 200, 'message' => '1'], false);
        }
        return '0';
    }
}