<?php
declare(strict_types=1);
/**
 * @author Panin Aleksei S <https://github.com/sygecon>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 */
namespace Sygecon\AdminBundle\Libraries\Seo;

use Config\App;
use App\Libraries\Loader\WebDoc;

final class Sitemap 
{
    private const IS_CHANGE_FREQ = false; // weekly monthly
    private const IS_PRIORITY    = false; 

    private const VALID_URL_RETURN_CODE = true;

    private const SITEMAP_MAX_LINES = 49000;

    private const FORMAT_DATE = 'Y-m-dTH:i:sP'; //'Y-m-dTH:i:sP' , 'c' 

    private const FILE_SITEMAP = 'sitemap%s.xml';
    private const LOGGER_FILE = WRITEPATH . 'base' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'sitemap_builder.log';

    private bool $isUpdate      = false;

    private array $isChange     = [];
    private array $dataFiles    = [];

    private int $marker         = 0;
    private int $xmlLines       = 0;
    private int $enumeration    = 0;

    private $baseUrl;
    private $currentTime;

    public function __construct() {
        if (self::IS_CHANGE_FREQ === true) {
            $this->currentTime = time();
        }
        $appConfig = new App();
        $this->baseUrl = rtrim($appConfig->baseURL, '/ ');
    }

    public function rename(string $oldLink = '', string $newLink = ''): bool
    {
        if (! $oldLink) { return false; }
        if (! $newLink) { return false; }
        if ($oldLink === $newLink) { return false; }
        $oldLink = $this->siteUrl($oldLink);
        $newLink = $this->siteUrl($newLink);

        if ($this->urlReplace($this->dataFiles[$this->enumeration], $oldLink, $newLink) === true) {
            $this->isChange[$this->enumeration] = 1;
            return true;
        }

        foreach($this->dataFiles as $marker => &$text) {
            if ($marker === $this->enumeration) { continue; }
            if ($this->urlReplace($this->dataFiles[$marker], $oldLink, $newLink) === true) {
                $this->enumeration = $marker;
                $this->isChange[$marker] = 1;
                return true;
            }
        }
        return false;
    }

    public function delete(string $link = ''): void
    {
        if (! $link) { return; }
        $link = $this->siteUrl($link);

        if ($this->deleteUrl($this->dataFiles[$this->enumeration], $link) === true) {
            $this->isChange[$this->enumeration] = 1;
            return;
        }

        foreach($this->dataFiles as $marker => &$text) {
            if ($marker === $this->enumeration) { continue; }
            if ($this->deleteUrl($this->dataFiles[$marker], $link) === true) {
                $this->enumeration = $marker;
                $this->isChange[$marker] = 1;
                return;
            }
        }
    }

    public function add(string $link = '', string $modify = ''): void
    {
        if (! $url = filter_var($this->siteUrl($link), FILTER_SANITIZE_URL)) { return; }

        if (self::VALID_URL_RETURN_CODE && WebDoc::httpCode($url) != 200) {
            return; 
        }
        $freq = '';
        $prt = '';

        $date = strtotime($modify);
        if (! $date) { $date = time(); }
        $modify = date(self::FORMAT_DATE, $date);

        if (self::IS_PRIORITY === true) {
            if ($link === '/' || $link === '/index' || $link === '/index.php') {
                $level = 0;
            } else {
                $level = substr_count($link, '/');
            }
            switch ((int) $level) {
                case 0:
                    $priority = 1.0;
                break;
                case 1:
                    $priority = 0.8;
                break;
                case 2:
                    $priority = 0.7;
                break;
                case 3:
                    $priority = 0.5;
                break;
                default: 
                    $priority = 0.3;  
                break;
            }
            $prt = number_format($priority, 1, '.', ',');
        }
        
        if (self::IS_CHANGE_FREQ === true) {
            $freq = 'weekly';
            $chg = (int) ($this->currentTime - $date);
            if ($chg > 0) {
                if ($chg < 604800) { $freq = 'daily'; }
                else
                if ($chg < 2592000) { $freq = 'weekly'; }
                else 
                if ($chg < 25920000) { $freq = 'monthly'; }
                else { $freq = 'yearly'; }
            }
        }

        if ($this->isUpdate === true) {
            $this->updateItem($url, $modify, $freq, $prt);
            return;
        }
        $this->insertItem($url, $modify, $freq, $prt);
    }

    public function build(): void
    {
        foreach($this->isChange as $marker => $i) {
            $this->saveDataToFile($marker);
            $this->dataFiles[$marker] = '';
            unset($this->dataFiles[$marker], $this->isChange[$marker]);
        }
        $this->isChange = [];
        $this->clearDataFiles();
    }

    public function createFile(): void
    {
        $file = FCPATH . $this->formatFileName($this->marker);
        if (is_file($file)) { unlink($file); }
        $this->dataFiles[$this->marker] = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
                    '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $this->xmlLines = (int) 0;
    }

    public function readAllFiles(): void
    {
        $this->isUpdate = true;
        $this->isChange = [];
        $this->clearDataFiles();
        $this->getLogger();
        for ($i = 0; $i <= $this->marker; $i++) {
            $this->dataFiles[$i] = file_get_contents(FCPATH . $this->formatFileName($i));
            if ($pos = mb_strpos($this->dataFiles[$i], '</urlset>')) {           
                $this->dataFiles[$i] = mb_substr($this->dataFiles[$i], 0, $pos);
            }
        }
        if (! isset($this->dataFiles[$this->marker]) || ! $this->dataFiles[$this->marker]) {
            $this->createFile();
        } 
    }

    public function getFilenames(): array
    {
        $result = [];
        for ($i = 0; $i < count($this->dataFiles); $i++) {
            $fileName = $this->formatFileName($i);
            if (is_file(FCPATH . $fileName)) { 
                $result[] = $this->siteUrl($fileName); 
            }
        }
        return $result;
    }

    // PRIVATE FUNCTIONS ========================================================

    private function siteUrl(string $url): string 
    {
        return $this->baseUrl . '/' . trim($url, '/ ');
    }

    // Внесение изменений в файл Sitemap
    // Изменение даты модификации страницы
    private function updateModify(string &$data, string $url, string $modify): bool 
    {
        if (! $start = mb_strpos($data, $url . '</')) { return false; }
        if ($start = mb_strpos($data, '<lastmod>', $start)) {
            $start = $start + strlen('<lastmod>');
            if ($end = mb_strpos($data, '</lastmod>', $start)) {
                $x = $end - $start;
                if ($x > 10 && $x < 30) {
                    $data = mb_substr($data, 0, $start) . $modify . mb_substr($data, $end);
                }
            }
        }
        return true;
    }

    // Изменение ссылки
    private function urlReplace(string &$text, string $old, string $new): bool 
    {
        if (! $text) { return false; }
        if ($old === $new) { return false; }
        if (! $start = mb_strpos($text, '>' . $old . '<')) { return false; }
        ++$start;
        $end = mb_strlen($old) + $start;
        $text = mb_substr($text, 0, $start) . $new . mb_substr($text, $end);
        return true;
    }

    // Удаление ссылки
    private function deleteUrl(string &$data, string $url): bool 
    {
        if (! $end = mb_strpos($data, '>' . $url . '<')) { return false; }
        if (! $start = mb_strrpos(mb_substr($data, 0, $end), '<url>')) { return false; }
        if (! $end = mb_strpos($data, '</url>', $end)) { return false; }
        $end += mb_strlen('</url>');
        $data = mb_substr($data, 0, $start) . mb_substr($data, $end);
        $data = str_replace(["\n\t\n\t", "\n\t\n", "\t\n\t", "\n\n"], ["\n\t", "\n", "\t", "\n"], $data);
        return true;
    }

    private function updateItem(string $url, string $modify, string $freq, string $priority): void
    {
        if ($this->updateModify($this->dataFiles[$this->enumeration], $url, $modify) === true) {
            $this->isChange[$this->enumeration] = 1;
            return;
        }

        foreach($this->dataFiles as $marker => &$text) {
            if ($marker === $this->enumeration) { continue; }
            if ($this->updateModify($text, $url, $modify) === true) {
                $this->enumeration = $marker;
                $this->isChange[$marker] = 1;
                return;
            }
        }

        $this->insertItem($url, $modify, $freq, $priority);
    }

    private function insertItem(string $location, string $modify, string $freq, string $priority): void
    {
        if (! isset($location) || ! $location) { return; }
        $data = &$this->dataFiles[$this->marker];

        $data .= "\t<url>\n\t\t<loc>" . $location . "</loc>\n\t\t<lastmod>" . $modify . "</lastmod>\n";
        if (isset($freq) && $freq) { 
            $data .= "\t\t<changefreq>" . $freq . "</changefreq>\n";
        }
        if (isset($priority) && $priority) { 
            $data .= "\t\t<priority>" . $priority . "</priority>\n";
        }
        $data .= "\t</url>\n";

        ++$this->xmlLines;
        $this->isChange[$this->marker] = 1;
        if ($this->xmlLines < (int) self::SITEMAP_MAX_LINES) { return; }
        if ($this->saveDataToFile() === false) { return; }
        $this->addDataFiles();
        $this->setLogger();
    }

    /**
     * Writes closing tags to current file
     */
    private function formatFileName(int $num): string
    {
        return sprintf(self::FILE_SITEMAP, ($num === 0 ? '' : '_' . $num));
    }

    private function saveDataToFile(?int $marker = null): bool
    {
        if (! is_numeric($marker)) { $marker = (int) $this->marker; }
        if (isset($this->dataFiles[$marker]) === false) { return false; }
        //if (mb_strpos($this->dataFiles[$marker], '</urlset>') === false) { 
        $this->dataFiles[$marker] .= '</urlset>' . PHP_EOL; 
        //}
        if (file_put_contents(FCPATH . $this->formatFileName($marker), $this->dataFiles[$marker], LOCK_EX) === false) {
            return false;
        }
        return true;
    }

    private function addDataFiles(): void
    {
        ++$this->marker;
        $this->dataFiles[$this->marker] = '';
        $this->createFile();
    }

    private function clearDataFiles(): void
    {
        foreach($this->dataFiles as $marker => &$text) { 
            $text = '';
            unset($this->dataFiles[$marker]);
        }
        $this->dataFiles    = [];
        $this->marker       = (int) 0;
        $this->xmlLines     = (int) 0;
        $this->enumeration  = (int) 0;
    }
    // ============================================================================

    private function getLogger(): void
    {
        $this->marker = (int) 0;
        $this->xmlLines = (int) 0;
        $this->enumeration = (int) 0;
        if (! is_file(self::LOGGER_FILE)) { return; }
        if (! $text = file_get_contents(self::LOGGER_FILE)) { return; } 
        if (! $logger = explode("\n", $text)) { return; } 
        if (is_array($logger) && count($logger) > 1) {
            if (is_numeric($logger[0])) {
                $this->marker = (int) $logger[0];
                $this->enumeration = $this->marker;
            }
            if (is_numeric($logger[1])) {
                $this->xmlLines = (int) $logger[1];
            }
        }
    }

    private function setLogger(): void
    {
        $text = (int) $this->marker . "\n" . (int) $this->xmlLines  . "\n" . meDate() . PHP_EOL;
        file_put_contents(self::LOGGER_FILE, $text, LOCK_EX);
    }
}