<?php

namespace Sygecon\AdminBundle\Controllers\Template;

use Sygecon\AdminBundle\Controllers\AdminController;

final class GlobalVariables extends AdminController 
{
    protected const DEFAULT_CONTENT = '<?php' . "\n\nreturn [\n\n];\n";

    protected $helpers = ['path'];

    protected $lang = APP_DEFAULT_LOCALE;

    /**
     * @return string
     */
    public function index(string $lang = APP_DEFAULT_LOCALE): string 
    {
        if (strtolower($this->request->getMethod()) !== 'get') return $this->pageNotFound();

        $this->setLanguage($lang);
        if (! $data = readingDataFromFile($this->getFile())) {
            $data = self::DEFAULT_CONTENT; 
        }
        return $this->build('variables', [
            'data' => esc($data),
            'lang_name' => $this->lang,
            'head' => ['icon' => 'regex', 'title' => lang('Admin.menu.sidebar.variablesDesc') . ' - self::globalData("name", [param])']
        ], 'Template'); 
    }

    /**
     * Add or update a model resource, from "posted" properties.
     * @param string $lang
     * @return string
     */
    public function update(string $lang = APP_DEFAULT_LOCALE): string 
    {
        $data = $this->postDataValid($this->request->getRawInput());
        if (! isset($data['content'])) return $this->pageNotFound();
        
        $this->setLanguage($lang);
        $data['content'] = trim($data['content']);
        if (! $data['content']) { 
            $data['content'] = self::DEFAULT_CONTENT; 
        }
        if (writingDataToFile($this->getFile(), $data['content']) === true) {
            return $this->successfulResponse('1');
        }
        return $this->pageNotFound();
    }

    private function setLanguage(string $lang): void
    {
        $lang = strtolower($lang);
        if ($lang === '') return;
        if ($lang === $this->lang) return;
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
