<?php
namespace Sygecon\AdminBundle\Controllers\Component;

use CodeIgniter\HTTP\ResponseInterface;
use Sygecon\AdminBundle\Controllers\AdminController;

final class Redirect extends AdminController 
{
    private const REDIRECT_FILE = APPPATH . 'Config' . DIRECTORY_SEPARATOR . 'Boot' . DIRECTORY_SEPARATOR . 'redirect.php';
    
    /** @return ResponseInterface */
    public function index(): ResponseInterface 
    {
        if ($this->request->getMethod() === 'get') {
            return $this->respond($this->build('redirect', [
                'dataRedirect' => (is_file(self::REDIRECT_FILE) ? esc(file_get_contents(self::REDIRECT_FILE)) : ''),
                'head' => ['icon' => 'arrow-up-right-square','title' => lang('Admin.menu.sidebar.redirectDesc')]
            ], 'Component'), 200); 
        }
        return $this->fail(lang('Admin.IdNotFound'));
    }

    /** @return ResponseInterface */
    public function update(): ResponseInterface 
    {
        $data = $this->postDataValid($this->request->getRawInput());
        if (!isset($data)) { return $this->fail(lang('Admin.IdNotFound')); }
        if (isset($data['data_redirect'])) {
            helper('path');
            return $this->respondUpdated(writingDataToFile(self::REDIRECT_FILE, $data['data_redirect']));
        } 
        return $this->fail(lang('Admin.IdNotFound'));
    }
}
