<?php
namespace Sygecon\AdminBundle\Controllers\Component;

use Sygecon\AdminBundle\Controllers\AdminController;

final class Routing extends AdminController 
{
    private const APP_ROUTE = APPPATH . 'Config' . DIRECTORY_SEPARATOR . 'Boot' . DIRECTORY_SEPARATOR . 'routes.php';
    
    /** @return string */
    public function index(): string 
    {
        if (strtolower($this->request->getMethod()) !== 'get') return $this->pageNotFound();
        
        return $this->build('routing', [
            'dataRoute' => (is_file(self::APP_ROUTE) ? esc(file_get_contents(self::APP_ROUTE)) : ''),
            'head' => [
                'icon' => 'send-check', 
                'title' => lang('Admin.menu.sidebar.routeName')
            ]
        ], 'Component'); 
    }

    /** @return string */
    public function update(): string 
    {
        $data = $this->postDataValid($this->request->getRawInput());
        if (isset($data) === false) return $this->pageNotFound();
        if (isset($data['data_route']) === true) {
            helper('path');
            $result = writingDataToFile(self::APP_ROUTE, $data['data_route']);
            return $this->successfulResponse($result);
        } 
        return $this->pageNotFound();
    }
}
