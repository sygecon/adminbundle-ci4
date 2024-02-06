<?php 
/**
 * @author  Aspada.ru
 * @license http://www.opensource.org/licenses/bsd-license.php BSD 3-Clause License
 */
namespace Sygecon\AdminBundle\Libraries\Seo;

final class Redirect
{
    private const REDIRECT_FILE = APPPATH . 'Config' . DIRECTORY_SEPARATOR . 'Boot' . DIRECTORY_SEPARATOR . 'redirect.php';
    
    private const LINK_LIFETIME = '-1 year';

    private bool $isChange = false;

    private array $output = [];
    private $listLinks = null;

    public $currentTime = null;

    public function __construct(?array $listLinks = null) {
        if (is_array($listLinks) && $listLinks) { 
            $this->listLinks = $listLinks; 
            $this->currentTime = strtotime(self::LINK_LIFETIME, time());
        }

        if (is_file(self::REDIRECT_FILE)) {
            if ($text = file_get_contents(self::REDIRECT_FILE)) { 
                $matches = [];
                $result = preg_match_all('/\$routes->addRedirect([(](.*?)[,](.*?)[);])/ui', $text, $matches);
                if ($result && isset($matches[3])) { 
                    $this->arrayCombine($matches[2], $matches[3]);
                }
            }
        }
    }

    public function add(string $fromUrl = '', string $fromTo = ''): void 
    {
        if (! $fromUrl) { return; }
        if (! $fromTo) { return; }
        if ($fromUrl === $fromTo) { return; }
        $from = $this->normalizeUrl($fromUrl);
        if (isset($this->output[$from])) { return; }
        $to = $this->normalizeUrl($fromTo);
        if (isset($this->output[$to])) { unset($this->output[$to]); }
        foreach($this->output as $key => &$value) {
            if ($value === $from) {
                unset($this->output[$key]);
                break;
            }
        }
        $this->output[$from] = $to;
        $this->isChange = true;
        return;
    }

    public function delete(string $url = ''): bool 
    {
        $url = $this->normalizeUrl($url);
        if (! $url) { return false; }
        if (isset($this->output[$url])) {
            unset($this->output[$url]);
            $this->isChange = true;
        }
        foreach($this->output as $from => &$to) {
            if ($to === $url) {
                unset($this->output[$from]);
                $this->isChange = true;
                return true;
            }
        }
        return false;
    }

    public function build(bool $saveOnlyIfChange = true): void
    {
        $save = $this->isChange;
        if ($saveOnlyIfChange === false) { $save = true; }
        $this->isChange = false;

        if (is_array($this->listLinks)) {
            foreach($this->listLinks as $key => &$items) {
                $from = $items['link_from'];
                if ($items['updated_at'] < $this->currentTime) {
                    if (isset($this->output[$from])) { 
                        $save = true;
                        unset($this->output[$from]); 
                    }
                } else
                if (! isset($this->output[$from]) && ! isset($this->output[$items['link_to']]) && ! in_array($from, $this->output)) {
                    $save = true;
                    $this->output[$from] = $items['link_to'];
                    $items = [];
                    unset($this->listLinks[$key]);
                }
                $items = [];
                unset($this->listLinks[$key]);
            }
            $this->listLinks = null;
        }

        if ($save === false) { 
            $this->clear();
            return; 
        }

        $text = '<?php ' . "\n";
        foreach($this->output as $key => &$val) {
            $text .= $this->toFormat($key, $val);
            unset($this->output[$key]);
        }
        file_put_contents(self::REDIRECT_FILE, $text, LOCK_EX);
    }

    // PRIVATE FUNCTIONS ========================================================

    private function clear(): void
    {
        $this->isChange = false;
        if (! $this->output) { return; }
        foreach($this->output as $key => &$val) { unset($this->output[$key]); }
        $this->output = [];
    }

    private function toFormat(string $fromUrl, string $toUrl): string
    {
        return sprintf("\n\$routes->addRedirect('%s', '%s');", $fromUrl, $toUrl) . "\n";
    }

    private function normalizeUrl(string $url): string
    {
        if ($url === '' || $url === '/' || $url === '/index' || $url === '/index.php') { return '/'; }
        return trim($url, '/');
    }

    private function arrayCombine(array &$keys, array &$values): void
    {
        if (! $keys) { return; }
        $clr = chr(39) . '" ';
        $this->output = [];
        foreach($keys as $i => &$key) {
            if (isset($values[$i])) { 
                $value = trim($values[$i], $clr);
                if (! in_array($value, $this->output)) {
                    $this->output[trim($key, $clr)] = $value;
                }
            }
            unset($keys[$i], $values[$i]);
        }
    }
}

