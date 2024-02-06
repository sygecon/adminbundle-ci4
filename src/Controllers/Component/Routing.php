<?php
namespace Sygecon\AdminBundle\Controllers\Component;

use CodeIgniter\HTTP\ResponseInterface;
use Sygecon\AdminBundle\Controllers\AdminController;

final class Routing extends AdminController 
{
    private const APP_ROUTE = APPPATH . 'Config' . DIRECTORY_SEPARATOR . 'Boot' . DIRECTORY_SEPARATOR . 'routes.php';
    
    /** @return ResponseInterface */
    public function index(): ResponseInterface 
    {
        if ($this->request->getMethod() === 'get') {
            return $this->respond($this->build('routing', [
                'dataRoute' => (is_file(self::APP_ROUTE) ? esc(file_get_contents(self::APP_ROUTE)) : ''),
                'head' => ['icon' => 'send-check','title' => lang('Admin.menu.sidebar.routeName')]
            ], 'Component'), 200); 
        }
        return $this->fail(lang('Admin.IdNotFound'));
    }

    /** @return ResponseInterface */
    public function update(): ResponseInterface 
    {
        $data = $this->postDataValid($this->request->getRawInput());
        if (!isset($data)) { return $this->fail(lang('Admin.IdNotFound')); }
        if (isset($data['data_route'])) {
            helper('path');
            return $this->respondUpdated(writingDataToFile(self::APP_ROUTE, $data['data_route']));
        } 
        return $this->fail(lang('Admin.IdNotFound'));
    }
}
