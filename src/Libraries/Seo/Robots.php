<?php 
/**
 * @author  Aspada.ru
 * @license http://www.opensource.org/licenses/bsd-license.php BSD 3-Clause License
 */
namespace Sygecon\AdminBundle\Libraries\Seo;

final class Robots
{
    private const ROBOTS_FILE  = 'robots.txt';

    private const DEVELOPER_MODE  = 'User-agent: *' . PHP_EOL . 'Disallow: /' . PHP_EOL;

    private const TEMPLATE_ROBOTS = 
        "User-Agent: Yandex\n{disallow}Host: {host}\n\n" .
        "User-Agent: Googlebot\n{disallow}Allow: *.css\nAllow: *.js\n\n" .
        "User-Agent: *\n{disallow}{sitemap_link}\n";

    private const FIXED_DISALLOW = [
        '/?', 
        '/' . SLUG_ADMIN . '/'
    ]; 

    private $output = ['disallow' => [], 'sitemap' => []];

    private $dataChange = false;

    public function add(string $url = ''): void
    {
        if (! $url) { return; }
        if (isset($this->output['disallow'][$url]) === false) {
            $this->output['disallow'][$url] = 1;
            $this->dataChange = true;
        }
    }

    public function delete(string $url = ''): void
    {
        if (! $url) { return; }
        if (isset($this->output['disallow'][$url]) === true) {
            unset($this->output['disallow'][$url]);
            $this->dataChange = true;
        }
    } 

    public function rename(string $oldUrl = '', string $newUrl = ''): void 
    {
        if ($oldUrl === $newUrl) { return; }
        $this->delete($newUrl);
        if ($oldUrl && isset($this->output['disallow'][$oldUrl]) === false) {
            $this->output['disallow'][$oldUrl] = 1;
            $this->dataChange = true;
        }
    }

    public function build(): void
    {
        if (defined('DEVELOPER_MODE') && DEVELOPER_MODE) {
            $this->clear();
            file_put_contents(FCPATH . self::ROBOTS_FILE, DEVELOPER_MODE, LOCK_EX);
            return;
        }

        if ($this->dataChange === false) {
            $this->clear();
            return;
        }

        $text   = '';
        $sm     = '';
        foreach(self::FIXED_DISALLOW as $link) {
            if (isset($this->output['disallow'][$link]) === false) { 
                $text .= $this->toFormat($link); 
            }
        }
        foreach($this->output['disallow'] as $link => $n) { 
            $text .= $this->toFormat($link); 
            unset($this->output['disallow'][$link]);
        }
        foreach($this->output['sitemap'] as $link => $n) {
            $sm .= "\nSitemap: " . $link;
            unset($this->output['sitemap'][$link]);
        }
        $this->clear();

        $text = str_replace(
            ['{disallow}', '{host}', '{sitemap_link}'], 
            [$text, base_url(), $sm], 
            self::TEMPLATE_ROBOTS
        );

        file_put_contents(FCPATH . self::ROBOTS_FILE, $text, LOCK_EX);
    }

    public function readFile(): void
    {
        $this->clear();
        
        if (defined('DEVELOPER_MODE') && DEVELOPER_MODE) {
            $this->output = ['disallow' => ['/'], 'sitemap' => []];
            return;
        }

        $this->output = ['disallow' => [], 'sitemap' => []];
        if (! $text = file_get_contents(FCPATH . self::ROBOTS_FILE)) { return; }

        helper('match');
        
        $offset = (int) 0;
        while ($str = subString($text, 'Disallow:', "\n", $offset)) {
            if (isset($this->output['disallow'][$str]) === false) { 
                $this->output['disallow'][$str] = 1; 
            }
        }

        $offset = (int) 0;
        while ($str = subString($text, 'Sitemap:', "\n", $offset)) {
            if (isset($this->output['sitemap'][$str]) === false) { 
                $this->output['sitemap'][$str] = 1; 
            }
        }
    }

    public function setSitemaps(array $items = []): void 
    {
        if (! $items) { return; }
        if (defined('DEVELOPER_MODE') && DEVELOPER_MODE) { return; }

        $this->output['sitemap'] = [];
        foreach ($items as $link) {
            if (isset($this->output['sitemap'][$link]) === false) {
                $this->output['sitemap'][$link] = 1; 
                $this->dataChange = true;
            }
        }
    }
    
    // PRIVATE FUNCTIONS ========================================================

    private function toFormat(string $url): string
    {
        return 'Disallow: ' . $url . "\n";
    }

    private function clear(): void
    {
        $this->dataChange = false;
        if (! $this->output['disallow'] && ! $this->output['sitemap']) { return; }
        foreach($this->output['disallow'] as $i => &$row) {
            unset($this->output['disallow'][$i]);
        }
        foreach($this->output['sitemap'] as $i => &$row) {
            unset($this->output['sitemap'][$i]);
        }
        $this->output = ['disallow' => [], 'sitemap' => []];
    }
}
