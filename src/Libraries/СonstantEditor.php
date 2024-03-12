<?php

namespace Sygecon\AdminBundle\Libraries;

final class СonstantEditor
{
    private const FILE_PATH = APPPATH . 'Config' . DIRECTORY_SEPARATOR . 'Boot'  . DIRECTORY_SEPARATOR;

    private bool $isLoader  = false;
    private bool $isChange  = false;
    private array $content  = [];
    private $fileName       = '';

    /** * Create a new writer instance */
    public function __construct(string $fileName = 'options') 
    {
        helper('match');
        $this->fileName = $fileName;
        $this->isLoader = false;
        if ($this->fileName) { $this->load(); }
    }

    /** * get All content as array */
    public function get(string $name = '') 
    {
        if ($this->isLoader === false) { return null; }
        if (! $name) { $this->content; }
        $name = $this->setName($name);
        if (array_key_exists($name, $this->content)) {
            $value = trim($this->content[$name]);

            if (isInteger($value) === true) { return (int) $value; }
            if (isWholeNumber($value) === true) { return (float) $value; }
            return $value;
        }
        return null;
    }

    public function getBoolean(string $name): bool
    {
        if ($this->isLoader === false) { return false; }
        if (! $name) { return false; }
        $name = $this->setName($name);
        if (array_key_exists($name, $this->content)) {
            if (trim($this->content[$name]) === 'true') { return true; }
        }
        return false;
    }

    public function getArray(string $name): array
    {
        if ($this->isLoader === false) { return []; }
        if (! $name) { return false; }
        $name = $this->setName($name);
        if (array_key_exists($name, $this->content)) {
            $value = trim($this->content[$name]);
            return jsonDecode(str_replace(['=>', '[', ']', chr(39)], [':', '{', '}', '"'], $value));
        }
        return [];
    }

    /**
     * set const
     */
    public function set(string $name, string $value): void
    {
        if ($this->isLoader !== true) { return; }
        if ($name) { 
            $this->isChange = true;
            $this->content[$this->setName($name)] = $this->setValue($value); 
        }
    }

    public function setArray(string $name, array $value): void
    {
        if ($this->isLoader !== true) { return; }
        if ($name) {
            $this->isChange = true;
            $this->content[$this->setName($name)] = $this->setValueArray($value);
        }
    }

    public function setBoolean(string $name, bool $value): void
    {
        if ($this->isLoader !== true) { return; }
        if ($name) {
            $this->isChange = true;
            $this->content[$this->setName($name)] = $this->setValueBool($value);
        }
    }

    /**
     * Save buffer to file
     */
    public function save(): void 
    {
        if ($this->isLoader === false) { return; }
        if ($this->isChange === false) { return; }
        $res = "<?php \n";
        if (count($this->content)) {
            foreach ($this->content as $name => $value) {
                $res .= "defined('" . $name . "') || define('" . $name . "', " . $value . ");\n";;
            }
        }
        file_put_contents(self::FILE_PATH . castingPath($this->fileName, true) . '.php', trim($res) . PHP_EOL, LOCK_EX);
    }

    private function setName(string $name): string 
    {
        return mb_strtoupper(
            str_replace(['[', ']', '(', ')', ':', chr(39), '"'], '', cleaningText($name))
        );
    }

    private function setValue(string $value): string 
    {
        $value = str_replace(['[', ']', '(', ')', ':'], '', str_replace(chr(39), '"', trim($value)));
        if (isWholeNumber($value) === true) { return $value; }
        if ($value === 'true') { return 'true'; }
        if ($value === 'false') { return 'false'; }
        return chr(39) . $value . chr(39);
    }

    private function setValueBool(?bool $value): string 
    {
        return ($value ? 'true' : 'false');
    }
    
    private function setValueArray(array $value): string 
    {
        if (is_array($value) === false) { return '[]'; }

        $res = '';
        foreach ($value as $i => $val) {
            if (isInteger($i) === true) {
                $res .= $i . ' => ';
            } else {
                $res .= chr(39) . $i . chr(39) . ' => ';
            }

            if (is_bool($val) === true) {
                $res .= $this->setValueBool($val) . ', ';
            } else {
                $res .= $this->setValue($val) . ', ';
            }
        }
        if ($res) { $res = substr($res, 0, -2); }
        return '[' . $res . ']';
    }

    /**
     * Load file for working
     */
    private function load(): void 
    {
        $this->isChange = false;
        $file = self::FILE_PATH . $this->fileName . '.php';
        if (! is_file($file)) { return; }
        if (! $text = file_get_contents($file)) { return; }

        $matches = [];
        $result = preg_match_all('/define([(](.*?)[,](.*?)[);])/ui', $text, $matches);
        if (! $result || ! isset($matches[2])) { return; }
        
        foreach($matches[2] as $i => &$key) {
            if (isset($matches[3][$i])) { 
                $this->content[trim($key, chr(39) . '" ')] = trim($matches[3][$i]);
            }
        }
        $this->isLoader = true; 
    }
}
