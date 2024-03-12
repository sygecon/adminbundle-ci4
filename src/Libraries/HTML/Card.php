<?php namespace Sygecon\AdminBundle\Libraries\HTML;

use Sygecon\AdminBundle\Libraries\HTML\Form;
use Sygecon\AdminBundle\Libraries\HTML\Component;
use Sygecon\AdminBundle\Config\Paths;
use Throwable;

final class Card 
{
    public const TYPE_WYSIWYG_EDITOR = ['Textarea-TinyMCE', 'Textarea-TinyMCE-Full'];

    private const BOX = 'asp-box';

    private const FORM_CLASS = 'asp-all-form-class';

    private const BTN = [
        'reset' => '<button type="button" name="clear" class="btn btn-secondary" onclick="Asp.resetFormData()"> ',
        'save'  => ['<button class="btn btn-primary float-right"', '<img alt="', '" asp-lazy="check2-all">']
    ];

    private const FOOTER = ['</div><div class="card-footer" ' . self::BOX . '="btn">', '</div></div>'];
    
    private const FORM = [
        'name' => 'meForm_',
        'method' => 'put',
        'class' => 'needs-validation',
        'novalidate' => true
    ];

    private $modelName = '';

    private array $data = []; // JSON Content
    private array $inputValues = [];

    private int $nodeId = 0;
    private $langName = APP_DEFAULT_LOCALE;

    /**
     * Class Constructor.
     * Formcode is either a JSON file or a JSON formatted string
     * @param string|array $data - json content as extern file or intern variable (code)
     */
    public function __construct(string $modelName = '', string $path = '')
    {
        if ($modelName) { 
            $this->modelName = $modelName;
            $this->setDataFromFile($path);
        }
    }

    /**
     * @param string $title
     * @param string $ident
    */
    public function setTitle(string $title = '', string $ident = ''): Card 
    {
        foreach ($this->data as &$cardFirst) { $data = &$cardFirst; break; }
        if (isset($data) === false) return $this;

        if ($title) $data['title'] = $title; 
        if ($ident)
            $data['form']['data-prefix'] = ($this->modelName ? $this->modelName . '/' : '') . $ident;
        return $this;
    }

    /**
    * @param array $data
    */
    public function setData(array $data = []): Card 
    {
        $this->data = $data;
        $this->buildData();
        return $this;
    }

    /**
    * @param array $data
    */
    public function setValues(array $data = []): Card 
    {
        $this->inputValues = $data;
        return $this;
    }

    /**
    * @param string $langName
    */
    public function setLangName(string $langName = APP_DEFAULT_LOCALE): Card 
    {
        $this->langName = $langName;
        return $this;
    }

    /**
    * @param string $langName
    */
    public function setNodeId(int $nodeId = 0): Card 
    {
        $this->nodeId = $nodeId;
        return $this;
    }

    public function show(string $key = ''): string 
    {
        if (! $this->data) return '';
        $html = '';

        if (! $key) return $this->renderData($this->getForm(), $html);

        if (isset($this->data[$key]) && is_array($this->data[$key])) {
            $html = $this->renderBlock($this->getForm(), $this->data[$key]);
            unset($this->data[$key]);
        }
        return $html;
    }

    public function showWithHtmlEditors(bool $noEmptyThenCollapsed = true): string 
    {
        if (! $this->data) return '';
        foreach ($this->data as &$cardFirst) { $data = &$cardFirst; break; }

        if (! isset($data['form']['fields'])) return '';
        $htmlData = [];
        
        foreach ($data['form']['fields'] as $key => &$value) { 
            if (isset($value['formType']) && in_array($value['formType'], self::TYPE_WYSIWYG_EDITOR)) {
                $htmlData[$key] = $value;
                unset($data['form']['fields'][$key]);
            }
        }
        if (! $htmlData) return $this->show();

        $html       = '';
        $name       = $data['form']['name'];
        $id         = 1;
        $tempData   = ['card' => []];
        
        foreach ($data as $key => &$value) { 
            if ($key !== 'form') $tempData['card'][$key] = $value;
        }
        $tempData['card']['form'] = [];

        foreach ($data['form'] as $key => &$value) { 
            if ($key !== 'fields') $tempData['card']['form'][$key] = $value;
        }

        $tempData['card']['icon'] = 'file-earmark-richtext';
        $modForm = $this->getForm();

        foreach ($htmlData as $key => &$value) {
            $tempData['card']['form']['name']   = $name . '_' . $id;
            if (! isset($value['label']) || ! $value['label']) {
                $tempData['card']['title'] = lang('Admin.editorHTML');
            } else {
                $tempData['card']['title'] = $value['label'];
            }
            $value['label'] = '';
            $tempData['card']['form']['fields'] = [$key => $value];
            $html .= $this->renderBlock($modForm, $tempData['card']);
            unset($htmlData[$key]);
            ++$id;
        }
        if (! $data['form']['fields']) return $html;

        if ($noEmptyThenCollapsed === true) $data['collapsed'] = true;

        return $this->renderData($modForm, $html);
    }

    // Private Functions

    private function &getForm(): Form 
    {
        $modForm = new Form();
        $modForm->langName = $this->langName;
        $modForm->nodeId = (int) $this->nodeId; 
        return $modForm;
    }

    private function renderData(Form &$form, string $html): string 
    {
        foreach ($this->data as $key => $data) {
            if (is_array($data) === false) continue;
            $html .= $this->renderBlock($form, $data);
            unset($this->data[$key]);
        }
        return $html;
    }

    private function setDataFromFile(string $path = ''): void 
    {
        $this->data = [];
        if (! $this->modelName) return;
        helper('path');
        $path = castingPath($path, true);
        if (! $path) $path = Paths::MODEL;
        $textData = getDataFromJson($path . DIRECTORY_SEPARATOR . castingPath($this->modelName, true));
        if (! $textData) return; 

        try {
            $this->data = jsonDecode($textData);
        } catch (Throwable $th) {
            try { $this->data = unserialize($textData, ['allowed_classes' => false]); } 
            catch (Throwable $th) { $this->data = []; }
        }
        $this->buildData();
    }

    private function buildData(): void 
    {
        foreach ($this->data as &$cardFirst) { $data = &$cardFirst; break; }
        if (isset($data) === false) return;
        
        if (isset($data['form']) === false || ! $data['form']) $data['form'] = self::FORM;
        if (isset($this->data['fields']) === false) return;

        $data['form']['fields'] = $this->data['fields'];
        $this->data['fields']   = [];
        unset($this->data['fields']);

        if ($this->modelName) $data['form']['name'] .= $this->modelName;
    }

    private function renderBlock(Form &$modForm, array $data): string 
    {
        $html = '';
        if (isset($data) && is_array($data)) {
            $html = $this->setHeader($data);

            if (isset($data['form']) && is_array($data['form'])) {
                $modForm->set($data['form'], $this->inputValues);
                $html .= $modForm->show();
                unset($data['form']);
            }
            $html .= $this->setFooter($data);
        }
        return $html;
    }

    private function setHeader(array &$data): string 
    {
        $head = 'card';
        if (isset($data['class'])) {
            $head .= ' ' . $data['class'];
            unset($data['class']);
        }
        if (isset($data['collapsed']) && $data['collapsed']) {
            $head .= ' collapsed';
            unset($data['collapsed']);
        }
        $head = '<div class="' . $head . '"';

        if (isset($data['id'])) {
            $head .= ' id="' . $data['id'] . '"';
            unset($data['id']);
        }
        if (isset($data[self::BOX])) {
            $head .= ' ' . self::BOX . '="' . $data[self::BOX] . '"';
            if (isset($data[self::FORM_CLASS])) {
                $head .= ' ' . self::FORM_CLASS . '="' . $data[self::FORM_CLASS] . '"';
            }
        }
        $head .= '><div class="card-header"><h3 class="card-title">';

        if (isset($data['icon'])) {
            $head .= ' <i asp-lazy="' . $data['icon'] . '"></i>';
            unset($data['icon']);
        }
        if (isset($data['title'])) {
            $head .= ' ' . $data['title'];
            unset($data['title']);
        }
        $head .= '</h3><div class="card-tools d-flex" ' . self::BOX . '="btn">';

        if (isset($data['select-language'])) {
            $head .= Component::renderSelectLang($this->langName);
            unset($data['select-language']);
        }
        return  $head . '<button class="btn btn-tool" asp-click="btn-collapse" static="1"></button></div></div><div class="card-body">';
    }

    private function setFooter(array &$data): string 
    {
        $btnReset = '';
        $btnSave = '';

        if (isset($data['btn-reset'])) {
            $btnReset = self::BTN['reset'] . lang('HeadLines.clearForm') . '</button>';
            unset($data['btn-reset']);
        }

        if (isset($data['btn-save'])) {
            $btnSaveTitle = lang('HeadLines.save');
            $btnSaveData = [
                'data-btntext'  => lang('HeadLines.save'),
                'data-title'    => lang('HeadLines.btnConfirmSave'),
                'data-action'   => 'save-form-data',
                'asp-click'     => 'modal-confirm'
            ];
            if (is_string($data['btn-save'])) { 
                $btnSaveData['data-action'] = $data['btn-save'];
            }
            if (isset($data['btn-save-modal'])) { 
                $btnSaveData['asp-click'] = $data['btn-save-modal'];
                unset($data['btn-save-modal']);
            }
            if (isset($data['btn-save-title'])) { 
                $btnSaveTitle = $data['btn-save-title'];
                unset($data['btn-save-title']);
            }
            $btnSave = self::BTN['save'][0];
            foreach ($btnSaveData as $n => $v) {
                $btnSave .= ' ' . $n . '="' . $v . '"';  
            }
            $btnSave .= '>' . self::BTN['save'][1] . $btnSaveTitle . self::BTN['save'][2] . ' ' . $btnSaveTitle . '</button>';
            unset($data['btn-save']);
        }
        return self::FOOTER[0] . $btnReset . $btnSave . self::FOOTER[1];
    }
}
