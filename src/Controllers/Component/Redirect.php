<?php
namespace Sygecon\AdminBundle\Controllers\Component;

use Sygecon\AdminBundle\Controllers\AdminController;

final class Redirect extends AdminController 
{
    private const REDIRECT_FILE = APPPATH . 'Config' . DIRECTORY_SEPARATOR . 'Boot' . DIRECTORY_SEPARATOR . 'redirect.php';
    
    /** @return string */
    public function index(): string 
    {
        if (strtolower($this->request->getMethod()) !== 'get') return $this->pageNotFound();
        
        return $this->build('redirect', [
            'dataRedirect' => (is_file(self::REDIRECT_FILE) ? esc(file_get_contents(self::REDIRECT_FILE)) : ''),
            'head' => [
                'icon' => 'arrow-up-right-square',
                'title' => lang('Admin.menu.sidebar.redirectDesc')
            ]
        ], 'Component'); 
    }

    /** @return string */
    public function update(): string 
    {
        $data = $this->postDataValid($this->request->getRawInput());
        if (! isset($data)) return $this->pageNotFound();
        if (isset($data['data_redirect'])) {
            helper('path');
            $result = writingDataToFile(self::REDIRECT_FILE, $data['data_redirect']);
            return $this->successfulResponse($result);
        } 
        return $this->pageNotFound();
    }
}
