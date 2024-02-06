<?php

namespace Sygecon\AdminBundle\Controllers\Template;

use CodeIgniter\HTTP\ResponseInterface;
use Sygecon\AdminBundle\Controllers\AdminController;

final class GlobalVariables extends AdminController 
{
    protected const DEFAULT_CONTENT = '<?php' . "\n\nreturn [\n\n];\n";

    protected $helpers = ['path'];

    protected $lang = APP_DEFAULT_LOCALE;

    public function index(string $lang = APP_DEFAULT_LOCALE): ResponseInterface 
    {
        if ($this->request->getMethod() !== 'get') {
            return $this->fail(lang('Admin.IdNotFound'));
        }
        $this->setLanguage($lang);
        if (! $data = readingDataFromFile($this->getFile())) {
            $data = self::DEFAULT_CONTENT; 
        }

        return $this->respond($this->build('variables', [
            'data' => esc($data),
            'lang_name' => $this->lang,
            'head' => ['icon' => 'regex', 'title' => lang('Admin.menu.sidebar.variablesDesc') . ' - self::globalData("name", [param])']
        ], 'Template'), 200); 
    }

    /**
     * Add or update a model resource, from "posted" properties.
     * @param int $id
     * @return array an array
     */
    /** * @return $this->respond */
    public function update(string $lang = APP_DEFAULT_LOCALE): ResponseInterface 
    {
        $data = $this->postDataValid($this->request->getRawInput());
        if (! isset($data['content'])) { return $this->fail(lang('Admin.IdNotFound')); }
        $this->setLanguage($lang);
        $data['content'] = trim($data['content']);
        if (! $data['content']) { $data['content'] = self::DEFAULT_CONTENT; }
        if (writingDataToFile($this->getFile(), $data['content'])) {
            return $this->respondUpdated(1, lang('Admin.navbar.msg.msg_update'));
        }
        return $this->fail(lang('Admin.IdNotFound'));
    }

    private function setLanguage(string $lang): void
    {
        $lang = strtolower($lang);
        if ($lang === $this->lang) { return; }
        if (isset(SUPPORTED_LOCALES[$lang]) === false) {
            $this->lang = APP_DEFAULT_LOCALE;
            return;
        }
        $this->lang = $lang;
    }

    private function getFile(): string
    {
        return APPPATH . 'Language' . DIRECTORY_SEPARATOR . $this->lang  . DIRECTORY_SEPARATOR . 'GlobalParam.php';
    }
}
