<?php 
namespace Sygecon\AdminBundle\Libraries\HTML;

use Sygecon\AdminBundle\Libraries\HTML\Component;

final class Form 
{
    private const CELL_WIDTH = 6;
    private const CELL_WIDTH_FULL = 12;

    private const DIV_WIDTH_FULL = ['hidden', 'textarea', 'files'];

    private const ELEMENT_ATTRIBUTE = [
        'label', 'open', 'divClass', 'labelClass', 'helpClass', 'help', 'feedback', 'feedbackID', 'feedbackClass'
    ];
    
    private const ELEMENT_PARAMETERS = [
        'autofocus', 'checked', 'disabled', 'multiple', 'novalidate', 'readonly', 'required'
    ];

    private const DIV_ROW = '<div class="form-row">' . "\n";

    private $elProps; // Element Properties
    private $data = null; // Content
    private $inputValues = null;

    public string $langName = APP_DEFAULT_LOCALE;
    public int $nodeId = 0;

    public function set(?array $data = null, ?array $inputValues = null): void 
    {
        $this->data = $data;
        $this->inputValues = $inputValues;
        $this->elProps = [];
    }

    /**
     * Render HTML tags from JSON and print it.
     */
    public function show(string $key = ''): string 
    {
        if (! $key) {
            $data = &$this->data;
        } else {
            if (! isset($this->data[$key])) { return ''; }
            $data = &$this->data[$key];
            unset($this->data[$key]);
        }
        if (! is_array($data)) { return ''; }
        if (! $fields = (isset($data['fields']) ? $data['fields'] : [])) { return ''; }
        unset($data['fields']);

        if ($isForm = (bool) ! empty($data)) {
            $html = '<form';
            $html .= $this->checkArray($data, '');
            $html .= ">\n";
        }

        $html .= $this->render($fields);
        if ($isForm) { 
            $html .= '<div style="display:none"><label>Fill This Field</label><input type="text" name="honeypot" value=""></div>' . "\n</form>\n"; 
        }
        return $html;
    }

    /**
     * Render Form Elements from JSON and print it.
     */
    public function render(array &$fields = []): string 
    {
        $html = '';
        $width = (int) 0;
        foreach($fields as $name => &$propValue) {
            if (isset($propValue['formType'])) { // ! IS MODEL
                $propValue['type'] = $propValue['formType']; 
                unset($propValue['formType']);
                $propValue['type'] = strtolower($propValue['type']); 
                $propValue['addOption'] = explode('-', $propValue['type']);
                $propValue['type'] = array_shift($propValue['addOption']);
                if (isset($propValue['name'])) { unset($propValue['name']); }
                if (isset($propValue['constraint'])) { 
                    $propValue['maxlength'] = $propValue['constraint']; 
                    unset($propValue['constraint']);
                }
                if (isset($propValue['null'])) { unset($propValue['null']); }
                if (isset($propValue['default'])) { unset($propValue['default']); }
                if (isset($propValue['unsigned'])) { unset($propValue['unsigned']); }
                if (!isset($propValue['labelClass'])) { $propValue['labelClass'] = 'form-label w-100'; }
                if (in_array($propValue['type'], self::DIV_WIDTH_FULL)) {
                    $propValue['divClass'] = 'form-group col-md-' . self::CELL_WIDTH_FULL;
                    $propValue['class'] = 'mt-1';
                }
                if (!isset($propValue['class'])) { $propValue['class'] = 'form-control mt-1'; }
                if (!isset($propValue['divClass'])) { $propValue['divClass'] = 'form-group col-md-' . self::CELL_WIDTH; }
                if (isset($propValue['help'])) {
                    $propValue['aria-describedby'] = $name . 'Help';
                    $propValue['helpClass'] = 'form-text';
                }
            }

            if (! isset($propValue['name'])) { $propValue['name'] = $name; }
            if ($this->inputValues !== null && array_key_exists($name, $this->inputValues)) { 
                $propValue['value'] = $this->inputValues[$name];
                unset($this->inputValues[$name]); 
            }

            if (isset($propValue['type'])) {
                if ($propValue['type'] !== 'div') {
                    if ($propValue['type'] !== 'hidden') { 
                        if ($width === 0) { $html .= self::DIV_ROW; }
                        $width += (isset($propValue['divClass']) ? (int) $this->getCellWidth($propValue['divClass']) : (int) self::CELL_WIDTH);
                    }
                    $this->elProps = &$propValue;
                    $html .= $this->parse();

                    if ($width > 11) { 
                        $width = 0; 
                        $html .= '</div>'; 
                    }
                }
            }
            unset($propValue);
        }

        return $html . ($width !== 0 ? '</div>' : '');
    }

    /**
     * Parse field array.
     * Specifies the HTML tag to generate based on the type
     */
    private function parse(): string 
    {
        switch($this->elProps['type']) {
            case 'button':
            case 'submit':
            case 'reset':
                return $this->button();

            case 'textarea':
                return $this->textarea();

            case 'select':
                return $this->select();

            case 'hidden':
                return $this->hidden();

            case 'files':
                return $this->labelWrap() .  
                    Component::render($this->elProps, $this->nodeId, $this->langName) .
                    $this->labelWrap(false);

            default:
                return $this->input();
        }
    }

    /**
     * Generate Input tag.
     */
    private function input(): string 
    {
        $el = &$this->elProps;
        $type = $el['type'] ?? '';
        // One of them has to be set at least: label and/or placeholder
        if (! isset($el['label']) && ! isset($el['placeholder']) && $type !== 'hidden' && $type !== 'image' && $type !== 'file') {
            return '';
        }

        // Create input with label
        return $this->labelWrap() . '<input' . $this->checkArray($el, $type) . '>' . $this->labelWrap(false);
    }


    private function labelWrap(bool $isBefore = true): string 
    {
        if (! $type = ($this->elProps['type'] ?? '')) { return ''; }

        $id             = $this->elProps['id'] ?? '';
        $label          = $this->elProps['label'] ?? '';
        $labelClass     = $this->elProps['labelClass'] ?? '';
        $divClass       = $this->elProps['divClass'] ?? '';

        $wrap = ($isBefore && $divClass ? '<div class="' . $divClass . '">' : '');

        // Checkbox and radio has label text always after the input
        if ($type === 'checkbox' || $type === 'radio') {
            if ($isBefore) {
                if (! $id) {
                    $wrap .= '<label' . ($labelClass ? ' class="' . $labelClass . '"' : '') . '>';
                }
                return $wrap;
            }

            // position after
            if ($id) {
                $wrap .= '<label for="' . $id . '"' . ($labelClass ? ' class="' . $labelClass . '"' : '') . '>';
            }
            return $wrap . $label . '</label>' . ($divClass ? '</div>' : '');
        }
        
        $icon           = $this->elProps['icon'] ?? '';
        $ariaLabel      = $this->elProps['aria-label'] ?? '';

        // Handle everything but checkbox and radio
        if ($isBefore) {
            // No need for label if aria-label is set
            if ($icon) {
                return $wrap . '<div class="input-group-prepend"><span class="input-group-text"' . 
                    ($label ? ' title="' . $label . '"' : '') . '>' .
                    '<i style="background-image: url(' . chr(39) . Component::faIcon($icon) . chr(39) . ');"></i></span></div>';
            } 

            if ($label && ! $ariaLabel) {
                $wrap .= $ariaLabel . '<label' . 
                    ($id ? ' for="' . $id . '"' : '') .
                    ($labelClass ? ' class="' . $labelClass . '"' : '') . '>' . $label . 
                    ($id ? "</label>\n" : '');
            }
            return $wrap;
        }

        $help           = $this->elProps['help'] ?? '';
        $helpID         = $this->elProps['aria-describedby'] ?? '';
        $helpClass      = $this->elProps['helpClass'] ?? '';
        $feedback       = $this->elProps['feedback'] ?? '';
        $feedbackID     = $this->elProps['feedbackID'] ?? '';
        $feedbackClass  = $this->elProps['feedbackClass'] ?? '';

        // position after
        if ($feedback) { // Add feedback from form validation
            $wrap .= $this->textAddition($id, $ariaLabel, $feedback, $feedbackID, $feedbackClass);
        }
        if ($help) { // Add help text
            $wrap .= $this->textAddition($id, $ariaLabel, $help, $helpID, $helpClass);
        }
        if ($label && ! $ariaLabel) { $wrap .= (! $id ? '</label>' : ''); }
        $wrap .= ($type === 'files' ? '<hr style="margin-top:1.5rem">' : '');
        return $wrap . ($divClass ? '</div>' : '');
    }

    /**
     * Generate Select tag.
     */
    private function select(): string 
    {
        $el = &$this->elProps;
        // Check if aria-label is true but no label given
        if (! isset($el['label'])) { return ''; }
        // Create select
        $html = $this->labelWrap();
        $value = (isset($el['value']) ? $el['value'] : '');
        $opt = '';

        if ($option = ($el['options'] ?? '')) {
            if (is_array($option)) { 
                $attr = $option; 
            } else if (is_string($option)) { 
                $attr = Component::executeAsFunction($option); 
            }

            if(isset($attr) && is_array($attr)) {
                foreach ($attr as $id => $val) {
                    $selected = '';
                    $desc = '';
                    $name = $val[0];
                    if (isset($val[2])) {
                        if ($val[2]) { $selected = " selected"; }
                        $desc = ' title="' . $val[1] . '"';
                        $name = $val[1];
                    } else if (isset($val[1])) {
                        if (is_numeric($val[1]) || is_bool($val[1])) {
                            if ($val[1]) {$selected = " selected";}
                        } else 
                        if ($val[1] !== "") {
                            $desc = ' title="' . $val[1] . '"';
                            $name = $val[1];
                        }
                    }
                    if ($value && $value == $id) { $selected = " selected"; }
                    $opt .= '<option value="' . $id . '"' . $desc . $selected . '>' . $name . '</option>' . "\n";
                }
            }
        }

        return $html . '<select' . $this->checkArray($el, '') . ">\n" . $opt . "</select>\n" . $this->labelWrap(false);
    }

    /**
     * Generate Area.
     */
    private function textarea(): string 
    {
        $el     = &$this->elProps;
        $class  = ''; 
        $type   = '';

        if (isset($el['addOption']) && $el['addOption']) {
            $class = array_shift($el['addOption']); 
            if ($el['addOption']) { 
                $type = array_shift($el['addOption']); 
                if (! is_string($type)) { $type = ''; }
            }
        }
        // Check if aria-label is true but no label given
        if (! isset($el['label'])) { return ''; }

        // Remove attributes "type" and "pattern"
        if (! $class) { // tinymce[]
            $el['rows'] ??= $el['rows'] = 5;
        } else {
            if (! $el['class']) { 
                $el['class'] = 'editor-' . $class; 
            } else { 
                $el['class'] .= ' editor-' . $class; 
            }
        }

        return $this->labelWrap() . '<textarea' . 
            // tinymce type
            ($type ? ' data-type="' . strtolower(trim($type)) . '"' : '') . 

            $this->checkArray($el, $type) .'>' . 
            (isset($el['value']) ? esc($el['value']) : '') . 
            '</textarea>' . $this->labelWrap(false);
    }
  
    /**
     * Generate Button tag.
     */
    private function button(): string 
    {
        // Check if aria-label is true but no label given
        if (! isset($this->elProps['label'])) { return ''; }
        // Create the button
        return '<button' . $this->checkArray($this->elProps, '') . '>' . 
            $this->elProps['label'] . "</button>\n";
    }

    /** hidden */
    private function hidden(): string 
    {
        if (isset($this->elProps['name']) === false) { return ''; }
        $value = '';
        if (isset($this->elProps['value'])) { 
            $value = ' value="' . esc($this->elProps['value']) . '"'; 
            unset($this->elProps['value']);
        }
        return '<input type="hidden" name="' . $this->elProps['name'] . '"' . $value . ">\n"; 
    }

    /**
     * Check array data and add parameter to input.
     *
     * @param array $data - element properties
     * @param string $type
     * @return string
     */
    private function checkArray(array &$data, string $type): string 
    {
        $html = '';
        $label = $data['label'] ?? '';

        foreach ($data as $key => $val) {
            $attr = strtolower($key);

            if ($attr === 'value') {
                $html .= ($type === 'tel' && $val
                    ? ' value="' . asPhone($val) . '"'
                    : ' value="' . esc($val) . '"'
                );
                continue;
            } 

            if (in_array($key, self::ELEMENT_ATTRIBUTE)) { continue; }

            if ($attr === 'aria-label' && $val) {
                $html .= ' ' . $attr . '="' . $label . '"';
                continue;
            } 
            if (in_array($attr, self::ELEMENT_PARAMETERS)) {
                $html .= ' ' . $attr;
                continue;
            } 
            if (is_null($val)) {
                $html .= ' ' . $key . '=""';
                continue;
            } 
            if (is_bool($val)) {
                $html .= ' ' . $key . '="' . ($val ? 'true' : 'false') . '"';
                continue;
            }
            if (is_string($val) || is_numeric($val)) {
                $html .= ' ' . $key . '="' . $val . '"';
            }
        }
        return $html;
    }

    /**
     * Additional text for input.
     * Add feedback or help text
     *
     * @param string|null $id - 'id=...' of input
     * @param string|null $ariaLabel - 'aria-lable=...' of input
     * @param string $text - text to add
     * @param string|null $textID - 'id=...' of div/span
     * @param string|null $textClass - 'class=...' of div/span
     * @return string
     */
    private function textAddition(?string $id, ?string $ariaLabel, string $text, ?string $textID, ?string $textClass): string 
    {
        $isDiv = (bool) ($id || $ariaLabel);
        return ($isDiv ? "<div" : "<span") . ($textID ? ' id="' . $textID . '"' : '') .
            ($textClass ? ' class="' .$textClass . '"' : '') . '>' . $text .
            ($isDiv ? "</div>\n" : "</span>\n");
    }

    private function getCellWidth(string $param = ''): int // 'form-group col-md-6';
    {
        if ($param !== '' && preg_match('/col(-?.*)-(\d+)/i', $param, $matches)) {
            return (isset($matches[2]) ? (int) $matches[2] : (int) self::CELL_WIDTH);
        }
        return (int) self::CELL_WIDTH;
    }
}