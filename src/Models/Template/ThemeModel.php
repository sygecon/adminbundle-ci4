<?php namespace Sygecon\AdminBundle\Models\Template;

use Config\Services;
use App\Models\Boot\BaseModel as Model;
use Sygecon\AdminBundle\Config\PageTypes;
use App\Config\Boot\Values;
use Sygecon\AdminBundle\Libraries\ConstantBuilder;
use MatthiasMullie\Minify;
use Throwable;

final class ThemeModel extends Model
{
    public const HTML_ELEMENT = [
        'js' => [
            'type'      => 'scripts',
            'tagStart'  => '<script ',
            'attr'      => 'src=',
            'suffix'    => '>',
            'tagEnd'    => '</script>'
        ],
        'css' => [
            'type'      => 'styles',
            'tagStart'  => '<link ',
            'attr'      => 'href=',
            'suffix'    => ' rel="stylesheet" type="text/css" /',
            'tagEnd'    => '>'
        ]
    ];

    protected $resource = '{"scripts":[],"styles":[]}';

    protected $table = 'themes';
    protected $columnsFindAll = 'id, active, name, title';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function setActive(int $id = 0): bool 
    {
        if ($id) { 
            if ($data = $this->find((int) $id, 'id, active, name')) {
                if ($data->active) { return true; }
                $path = FCPATH . castingPath(PATH_THEME, true);
                if (!is_dir($path)) { return false; }
                $path .= DIRECTORY_SEPARATOR;
                $pl = strtolower(Services::request()->getUserAgent()->getPlatform());
                $pl = substr($pl, 0, strpos($pl, ' '));
                if ($pl == 'windows') { $path .= DIRECTORY_SEPARATOR; }
                if (is_dir($path . $data->name)) {
                    $go = true;
                    if ($row = $this->getActive()) {
                        if ((int) $row->id === (int) $id) { return true; }
                        foreach (self::HTML_ELEMENT as $type => &$val) {
                            $this->removingLinkToMinifyFromTemplate($type, $row->name);
                        }
                        // if (is_dir($path . ACTIVE_THEME)) {
                        //     try {
                        //         if (! rename($path . ACTIVE_THEME, $path . $row->name)) { return false; }
                        //     } catch (Throwable $th) { 
                        //         return false;
                        //     }
                        // }
                        if (! $this->builder()->where('id', (int) $row->id)->set('active', (int) 0)->update()) {
                            $go = false;
                        }
                    }
                    if ($go && $this->update((int) $id, ['active' => 1])) {
                        //rename($path . $data->name, $path . ACTIVE_THEME);
                        $this->setActiveConstant($data->name);
                        return true;
                    } 
                }
            }
        }
        return false;
    }

    public function setActiveConstant(string $name): void 
    {
        if($name) { 
            $constEditor = new ConstantBuilder();
            $constEditor->set('ACTIVE_THEME', $name);
            $constEditor->save();
        }
    }

    public function getActive(): ?object 
    {
        if($row = $this->builder()->select('id, name, title')->where('active', 1)->get()->getRow()) { 
            return $row; 
        }
        return null;
    }

    public function getCount(): int 
    {
        return (int) $this->countAll();
    }

    public function getResource(int $id = 0, string $type = ''): array 
    {                    
        if ($id) {
            $row = $this->find((int) $id, 'resource');
            if (isset($row) && $row) {
                $res = [];
                try {
                    $row = jsonDecode($row->resource);
                    foreach (self::HTML_ELEMENT as &$val) {
                        $vt = $val['type']; 
                        $res[$vt] = [];
                        foreach ($row[$vt] as $i => $item) {
                            $n = $i + 1;
                            $res[$vt][] = ['id' => (int) $n, 'src' => $item];
                            unset($row[$vt][$i]);
                        }
                        unset($row[$vt]);
                    }
                    unset($row);
                } catch (Throwable $th) {
                    $res = jsonDecode($this->resource);
                }
                if (! $type) { return $res; }
                if (array_key_exists($type, $res)) { return $res[$type]; }
            }
        }
        return [];
    }

    public function cutFileName(string $fullFileName = '', string $ext = '', string $name = ACTIVE_THEME): string
    {
        if ($fullFileName && isset(PageTypes::FILE_PATH[$ext])) { 
            $path = strtolower(castingPath(PATH_THEME, true) . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . trim(PageTypes::FILE_PATH[$ext], '/\\'));
            $n = strlen($path);
            if (strtolower(substr($fullFileName, 0, $n)) == $path) {
                return ltrim(substr($fullFileName, $n), DIRECTORY_SEPARATOR);
            }
        }
        return $fullFileName;
    }

    public function setResource(int $id = 0, array $data = []) 
    {                    
        //helper('path');
        if ($id && $data && isset($data['action'])) {
            $fileName = '';
            $ext    = '';
            $n      = (int) 0;
            $row    = $this->find((int) $id, 'active, name, resource');
            if (isset($row) && $row ) {
                try {
                    if (isset($data['src'])) {
                        $fileName = castingPath($data['src'], true);
                        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        if (isset(self::HTML_ELEMENT[$ext])) {
                            //$path = ($row->active ? ACTIVE_THEME : $row->name);
                            $fileName = $this->cutFileName($fileName, $ext, $row->name);
                            $ext = self::HTML_ELEMENT[$ext]['type'];
                            $n = (int) 0;
                        } else {
                            $ext = '';
                            $fileName = '';
                        }
                    }
                    if (isset($data['id'])) { 
                        $n = (int) $data['id']; 
                        --$n;
                        if (isset($data['ext'])) {
                            $ext = strtolower(trim($data['ext']));
                            $ext = (isset(self::HTML_ELEMENT[$ext]) ? self::HTML_ELEMENT[$ext]['type'] : '');
                        }
                    }
                    $res = jsonDecode($row->resource);
                    unset($row->resource);
                } catch (Throwable $th) {
                    $res = jsonDecode($this->resource);
                    //baseWriteFile('error.txt', $th->getMessage());
                }
                unset($row->name, $row->active, $row);
                if ($ext && isset($res[$ext])) {
                    if ($data['action'] === 'delete' && isset($res[$ext][$n])) {
                        unset($res[$ext][$n]);
                        $result = ++$n;
                    } else if ($fileName) {
                        foreach ($res[$ext] as $item) {
                            if (strtolower($fileName) === strtolower($item)) { return false; }
                        }
                        if ($data['action'] === 'update') {
                            if (isset($res[$ext][$n]) && strtolower($fileName) !== strtolower($res[$ext][$n])) {
                                $res[$ext][$n] = $fileName;
                                $result = jsonEncode(['id' => ++$n, 'src' => $fileName], false);
                            }
                        } else {
                            $res[$ext][] = $fileName;
                            $result = jsonEncode(['id' => (int) count($res[$ext]), 'src' => $fileName], false);
                        }
                    }
                    if (isset($result) && 
                        $this->update((int) $id, ['resource' => jsonEncode($res, false)])) {
                            return $result;
                    }
                }
            }
        }
        return false;
    }

    // Создание Minify файла определенного типа
    public function minifyFile(string $name = '', string $type = '', array &$listFiles = [], bool $isActive = true): void
    {
        if ($name && $type) {
            $dir = FCPATH . castingPath(trim(PATH_THEME, '\\/')) . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . PageTypes::FILE_PATH[$type];
            if (is_dir($dir) === false) { return; }
            $dir .= DIRECTORY_SEPARATOR;
            $miniFile = $dir . $this->setMinifyName($type);
            if (file_exists($miniFile) === true) { @unlink($miniFile); }
            $list = [];
            foreach ($listFiles as $i => $fileName) {
                $file = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $dir . $fileName);
                if (is_file($file)) { $list[] = $file; }
                unset($listFiles[$i]);
            }
            if ($list) {
                try {
                    $this->removingRedundantFiles($dir);
                    
                    $minifier = ($type === 'css' ? new Minify\CSS($list) : new Minify\JS($list));
                    $minifier->minify($miniFile);

                    if ($isActive) { 
                        $this->setLinkToMinifyInTemplate($type, $miniFile); 
                    }
                } catch (Throwable $th) {
                    helper('path');
                    baseWriteFile(DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'Errors' . DIRECTORY_SEPARATOR . 'minify-' . $type . '.log', $th->getMessage());
                }
            } else if ($isActive) {
                $this->removingLinkToMinifyFromTemplate($type, $name);
            }
        }
    }

    private function setMinifyName(string $ext): string
    {
        return PageTypes::THEME_MINIFY_FILE_NAME . '.' . substr(sha1(date('m/d/Y h:i:s a', time())), -8) . '.' . $ext;
    }

    // Добавление ссылки на Minify файл в HTML шаблон
    private function setLinkToMinifyInTemplate(string $type, string $fileName): void
    {
        if ($type && $fileName && isset(self::HTML_ELEMENT[$type])) {
            $separate   = '"';
            $attr       = self::HTML_ELEMENT[$type]['attr'];
            $fileName   = '/' . str_replace('//', '/', str_replace('\\', '/', trim(substr($fileName, strlen(FCPATH)), '/\\')));
            
            if ($type === 'css') {
                $file = templateFile(HTML_FILE_HEADER);
                $tag = '</head';
                $predTag = '<?= $app_style';
            } else {
                $file = templateFile(HTML_FILE_FOOTER);
                $tag = '</body';
                $predTag = '<?= $app_script';
            }
        }
        if (isset($file) === false) { return; }
        helper('path');
        if (! $html = readingDataFromFile($file)) { return; }
        $this->cleaningHtml($html, $type);

        $arr = explode('.', $fileName);
        $link = implode('.', array_splice($arr, 0, -2));
        if (! $pos = mb_stripos($html, $attr . $separate . $link)) {
            $separate = "'";
            $pos = mb_stripos($html, $attr . $separate . $link);
        }
        if ($pos) { // Заменяем одну ссылку на другую 
            $attr .= $separate; 
            $len = $pos + strlen($attr);
            $len = mb_strpos($html, $separate, ++$len);
            if ($pos && $len && $len > $pos) {
                $html = mb_substr($html, 0, $pos) . $attr . $fileName . mb_substr($html, $len);
            } else { return; }
        } else { // Добавляем ссылку
            $link = self::HTML_ELEMENT[$type]['tagStart'] . $attr . '"' . $fileName . '"' . self::HTML_ELEMENT[$type]['suffix'] . self::HTML_ELEMENT[$type]['tagEnd'];
            if (! $n = mb_stripos($html, $predTag)) { 
                $pos = mb_stripos($html, $tag);
            } else {
                $pos = (int) $n; 
            }
            if ($pos) {
                --$pos;
                $html = mb_substr($html, 0, $pos) . "\n" . $link . "\n" . mb_substr($html, $pos);
            } else {
                if ($type === 'css') {
                    $html .= "\n\t" . $link;
                } else {
                    $html = $link. "\n" . $html;
                }
            }
        }
        writingDataToFile($file, $html, false);
    } 

    // Удаление излишних файлов
    public function removingRedundantFiles(string &$dir): void
    {
        $list = $this->findMinifyFilesByDate($dir);
        $i = false;
        foreach($list as $key => $value) {
            if ($i === true) { @unlink($dir . $value); } else { $i = true; }
            unset($list[$key]);
        }
        unset($list);
    }

    // Поиск файлов Minify
    private function findMinifyFilesByDate(string &$dir): array
    {
        if ($handle = opendir(rtrim($dir, DIRECTORY_SEPARATOR))) {
            $find = strtolower(PageTypes::THEME_MINIFY_FILE_NAME . '.');
            $len = strlen($find);
            $list = [];
            while (($file = readdir($handle)) !== false) {
                if ($file !== '.' && $file !== '..') {
                    if (strtolower(substr($file, 0, $len)) === $find) { 
                        if ($ctime = filectime($dir . $file)) { 
                            $list[$ctime] = $file; 
                        }
                    }
                }
            }
            closedir($handle);
            if ($list) { krsort($list, SORT_NUMERIC); }
            return $list;
        }
        return [];    
    }

    // Удаление ссылки на Minify файл из HTML шаблона
    private function removingLinkToMinifyFromTemplate(string $type, string $name): void
    {
        helper('path');
        if ($type && isset(self::HTML_ELEMENT[$type])) {
            $elem = self::HTML_ELEMENT[$type];
            if ($type === 'css') {
                $file = templateFile(HTML_FILE_HEADER);
            } else {
                $file = templateFile(HTML_FILE_FOOTER);
            }
        }
        if (isset($file) && $html = readingDataFromFile($file)) {
            $link = '/' . toUrl(trim(PATH_THEME, '\\/') . '/' . $name . '/' . PageTypes::FILE_PATH[$type]) . '/' . PageTypes::THEME_MINIFY_FILE_NAME . '.';
            $separate   = '"';
            $this->cleaningHtml($html, $type);

            if (! $pos = mb_stripos($html, $elem['attr'] . $separate . $link)) {
                $separate = "'";
                $pos = mb_stripos($html, $elem['attr'] . $separate . $link);
            }
            if ($pos) {
                $p = mb_strrpos(mb_substr($html, 0, $pos), $elem['tagStart']) - 1;
                //$start = mb_strrpos($html, $tagStart, -(mb_strlen($html) - $pos));
                $pos = mb_strpos($html, $elem['tagEnd'], $pos);
                if ($pos && $p && $pos > $p) {
                    $pos += strlen($elem['tagEnd']);
                    $html = mb_substr($html, 0, $p) . "\n" . mb_substr($html, ++$pos);

                    writingDataToFile($file, $html, false);
                }
            }
        }
    }

    // Очистка текста внутри тегов 
    private function cleaningHtml(string &$html, string $type): void
    {
        $pattern = ($type === 'css' ? '/(\<link (.*?)\>)/is' : '/(\<script (.*?)\>)/is');
        $html = preg_replace_callback($pattern, 
            function ($matches) {
                $result = str_replace([' = ', ' =', '= '], '=', str_replace('  ', ' ', $matches[0]));
                $result = preg_replace_callback('~"[^"]*"~', 
                    function ($m) { return preg_replace('~\s~', '', $m[0]); }
                , $result);
                return preg_replace_callback("~'[^']*'~", 
                    function ($m) { return preg_replace('~\s~', '', $m[0]); }
                , $result); 
            }
        , str_replace(["\n\n", "\n \n", "\r\n\r\n"], "\n", $html));
    }

}