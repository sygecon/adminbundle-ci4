<?php 
namespace Sygecon\AdminBundle\Libraries\HTML;

use Config\Boot\NestedTree;
use Sygecon\AdminBundle\Config\PageTypes;
use Sygecon\AdminBundle\Libraries\Tree\PageList;
use Throwable;

final class Component 
{
    private const INPUT_TEXT    = '<input type="text" name="%s" value="%s" title="%s" class="%sform-control col-md-4 ml-1 mr-1" />';
    private const INPUT_HIDDEN  = '<input type="hidden" name="%s" value="%s" />';
    private const TAG_LINK      = '<a class="name" href="%s" target="_blank" title="%s">%s</a>';
    private const DIV_ITEM      = '<div class="input-group mt-1">';

    private const BUTTON_ADD    = '<button type="button" class="btn btn-outline-secondary float-right" data-type="%s"%s' . 
        ' onclick="%s"><img src="/images/icons-main/icons/plus-square.svg"> %s</button>';
   
    private const HEAD_LIST     = '<div class="list-wrap"><div class="data-lists-wrapper">' .
        '<div class="box"><div class="data-length" data-rows-page="15"></div><div class="data-filter">' .
        '<label>%s:<input type="search" placeholder="%s" /></label></div></div>';

    private const END_LIST      = '</div></div>';
    private const CLASS_LIST    = 'todo-list data-list';
    private const ITEM_ATTR     = 'data-type="item"';

    private const DIV_STATUS    = '<div class="data-info" role="status"></div><div class="data-paginate"></div>';
    private const BTN_CLICK_FM  = 'AspFM.open()';
    private const BTN_CLICK_TREE= 'AspTree.open(this)';
    private const SPAN_STYLE    = 'style="margin-left:auto;vertical-align:middle;"';
    private const ICON_TRASH    = '/images/icons-main/icons/trash.svg';

    private const PREFIX_NAME   = PageTypes::FORM_LIST_FIELDS;

    private const BLANK_DATA_PAGE = [
        'id' => 0, 'name' => '', 'slug' => '', 'title' => '', 'description' => ''
    ];

    // Раскрывающийся список выбора языка
    public static function renderSelectLang(string $langName = APP_DEFAULT_LOCALE): string 
    {
        $languages = arrayUsedLanguages();
        if (count($languages) < 2) { return ''; }

        $text = lang('Admin.pageLanguage');
        $html = '<label class="h5 mt-1 d-sm-none d-md-block">&nbsp;' . $text . '&nbsp;</label><span class="h5 mr-3"><select title="' . 
            $text . '" name="lang_name" class="form-select" style="display:inline;min-width:120px;">';
        
        foreach($languages as $lang => &$value) {
            $html .= '<option value="' . $lang . '" title="' . $lang . '"' . 
                ($langName === $lang ? ' selected="selected"' : '') . '> ' . $value['title'] . '</option>';
        }
        return $html . '</select></span>';
    }

    public static function renderInputBtn(string $title = '', string $btnClick = '', string $bkgImage = '', string $dataType = '', string $dataFilter = ''): string 
    {
        $bkgImage = ($bkgImage
            ? 'background-image:url(' . chr(39) . $bkgImage . chr(39) . ');padding:1px;'
            : 'background-image:url(' . chr(39) . self::ICON_TRASH . chr(39) . ');margin:0;padding:8px;'
        );
        return 
            '<input type="button" class="btn toolbtn" style="background-color:rgba(0,0,0,0);vertical-align:middle;' . $bkgImage . '"' .
            ($title ? ' title="' . $title . '"' : '') . 
            ($dataType ? ' data-type="' . $dataType . '"' : '') .  
            ($dataFilter ? ' ' . trim($dataFilter) : '') .
            ($btnClick ? ' onclick="' . $btnClick .'"' : '') . ' />';
    }

    /// Выполнение функций из строки  -------------------------------------------
	public static function executeAsFunction(string $str): mixed
    {
        $fnct       = '';
        $class      = '';
        $params     = '';
        $matches    = [];
        
        if (! preg_match('#(.+)\{(.+)\/\}(.?)#uim', trim($str), $matches)) return null;
        if ($matches) {
            $fnct   = trim($matches[1]);
            $class  = trim($matches[2]);
            $params = trim($matches[3]);
            if (strpos($class, '|') !== false) $class = rtrim(str_replace('|', '\\', $class), '\\');
        }

        if ($class && class_exists($class) === true && method_exists($class, $fnct) === true) {
            try {
                $model = new $class();
                if (! $params) { return $model->$fnct(); }
                return $model->$fnct($params);
            } catch (Throwable $th) { return null; }
        }
        if ($fnct[0] === '|') $fnct = rtrim(str_replace('|', '\\', $fnct), '\\');

        if (function_exists($fnct)) { $matches = []; } else 
        if ($class) {
            if (isset($class[$fnct])) return $class[$fnct];
            if (isset($class->{$fnct})) return $class->{$fnct};
        } else 
        if (! strpos($fnct, '::')) return null;
        
        try {    
            if ($class) {
                if (! $params) return call_user_func($fnct, $class);
                return call_user_func($fnct, $class, $params);
            }
            return $fnct();
        } catch (Throwable $th) { return null; }
	}

    public static function faIcon(string $icon = ''): string 
    {
        $fn = '/images/fa-icons/';
        if (! $icon) { return $fn . 'solid/circle.svg'; }

        switch (substr($icon, 0, 4)) {
            case 'fas-': $fn .= 'solid/';
            break;
            case 'fab-': $fn .= 'brands/';
            break;
            case 'far-': $fn .= 'regular/';
            break;
            case 'fam-': $fn .= 'main/';
            break;
            case 'faf-': $fn .= 'flags/';
            break;
        }
        if ($fn) { return $fn . substr($icon, 4) . '.svg'; }
        return $fn . 'solid/circle.svg';
    }

    // Component
    public static function render(array &$el, int $nodeId, string $langName) : string
    {
        $tg     = 'src';
        $sort   = ' draggable="true"';
        $name   = $el['name'];
        $type   = array_shift($el['addOption']);
        $isList = (isset($el['addOption']) === false ? false : array_shift($el['addOption']));
        $btnClick   = self::BTN_CLICK_FM;
        $dataFilter = ($el['dataFilter'] ? ' data-filter="' . $el['dataFilter'] . '"' : '');
        $html       = '';
        // helper('path');
        $data = self::getValues($el);

        // This Component is Page List
        if ($type === 'page') {
            $tg       = 'id'; 
            $btnClick = self::BTN_CLICK_TREE;
            $sort     = '';
            if (isset($el['only-folder'])) { $dataFilter .= ' data-only-folder="on"'; }
            
            // This Data is  Relationship
            $db = \Config\Database::connect(NestedTree::DB_GROUP);
            if (isset($el['relation']) === true) {
                $isLeft = ($el['relation'] && $el['relation'] !== 'left' ? false : true);
                $data = PageList::relationship($name, $isLeft, (int) $nodeId, $langName, $db);
                $dataFilter .= ' data-home-id="' . PageList::relationNode($name, $isLeft, $db) . '"';
            } else {
                $data = PageList::fromArray(array_column($data, 'id'), $langName, $db);
            }
            $db->close();
            
            if (! $data) { $data = self::BLANK_DATA_PAGE; }
        }
        
        // baseWriteFile($name . 'melog.txt', jsonEncode($data, false));
        $html .= sprintf(self::INPUT_HIDDEN, self::PREFIX_NAME . '[' . $name . ']', $type);
        
        if ($isList) { 
            //if (! $data) { $data[0] = Component::showIsEmptyValues($type); }
            $btnDel = '<span class="tools" ' . self::SPAN_STYLE . '>' .
                self::renderInputBtn(lang('HeadLines.catalog.removeFromList'), 'Asp.removeItem()') . 
            '</span>';
            
            $html .= sprintf(self::HEAD_LIST, lang('Admin.table.filter.label'), lang('Admin.table.filter.placeholder')) .
                '<ul class="' . self::CLASS_LIST . ($sort ? ' sortable' : '') . '" data-filter="name">';    
        
            foreach($data as &$row) {
                if (! is_array($row) || ! isset($row[$tg])) { continue; }
                if ($item = self::renderItem($row, $name, $type, $dataFilter, $btnDel, '[]')) {
                    $html .= '<li ' . self::ITEM_ATTR . $sort . '>' . $item . "</li>\n";
                }
            }

            $html .= '</ul>' . sprintf(self::BUTTON_ADD, $type, $dataFilter, $btnClick, lang('HeadLines.catalog.addFile'));
            
            $row = self::showIsEmptyValues($type);
            $row[$tg] = '{{' . $tg . '}}';
            if ($type !== 'page') {
                $row['alt'] = '{{alt}}';
                $row['title'] = '{{title}}';
            }

            return $html . '<template><li ' . self::ITEM_ATTR . '>' . 
                self::renderItem($row, $name, $type, $dataFilter, $btnDel, '[]') . 
                '</li></template>' . self::DIV_STATUS . self::END_LIST; 
        }

        if (! $data) { 
            $data = self::showIsEmptyValues($type);
        } else if (array_key_exists(0, $data)) {
            $data = $data[0];
        }
        
        $btnDel = '<span ' . self::SPAN_STYLE . '>' . 
            self::renderInputBtn(lang('HeadLines.catalog.removeFromList'), 'Asp.clearBlock()') . 
        '</span>';

        return $html . self::renderItem($data, $name, $type, $dataFilter, $btnDel);
    }

    // Private Functions

    private static function renderItem(
        array $row = [], string $name = '', string $type = 'image', 
        string $dataFilter = '', string $btnDel = '', string $tr = ''): string 
    {
        if (! $name) { return ''; }

        $tg = 'src';
        $btnClick = Component::BTN_CLICK_FM;
        if ($type === 'page') { 
            $btnClick = Component::BTN_CLICK_TREE; 
            $tg = 'id';
        }
        if(isset($row[$tg]) === false) { return ''; }

        if ($type === 'image') {
            $src = (! $row[$tg] ? PageTypes::FILE_ICON[$type] : $row[$tg]);
        } else {
            $src = PageTypes::FILE_ICON[$type];
        }
        
        $html = self::DIV_ITEM . sprintf(self::INPUT_HIDDEN, $name . '[' . $tg . ']' . $tr, $row[$tg]) .
            self::renderInputBtn(lang('Admin.selectItem'), $btnClick, $src, $type, $dataFilter);

        if (isset($row['link']) && ! isset($row['slug'])) { $row['slug'] = $row['link']; }

        // This Component is Page List
        if ($type === 'page') {
            if(isset($row['slug']) === false) {
                return $html . sprintf(self::TAG_LINK, '{{slug}}', '{{description}}', '{{title}}') . $btnDel . '</div>';
            }

            return $html . sprintf(self::TAG_LINK, $row['slug'], 
                    (isset($row['description']) ? ' title="' . $row['description'] . '"' : '') 
                , $row['title']) . $btnDel . '</div>';
        }

        if (array_key_exists('poster', $row)) {
            if (!$row['poster']) { $row['poster'] = PageTypes::FILE_ICON[$type]; }
            $html .= sprintf(self::INPUT_HIDDEN, $name . '[poster]' . $tr, $row['poster']) .
                self::renderInputBtn(lang('HeadLines.formModel.poster'), self::BTN_CLICK_FM, $row['poster'], 'image');
        }

        if (array_key_exists('alt', $row)) {
            $html .= sprintf(self::INPUT_TEXT, $name . '[alt]' . $tr, $row['alt'], lang('HeadLines.formModel.tagAlt'), 'name ');
        }

        if (array_key_exists('text', $row)) {
            $html .= sprintf(self::INPUT_TEXT, $name . '[text]' . $tr, $row['text'], lang('HeadLines.formModel.linkText'), 'name ');
        }

        if (array_key_exists('title', $row)) {
            $html .= sprintf(self::INPUT_TEXT, $name . '[title]' . $tr, $row['title'], lang('HeadLines.formModel.tooltip'), '');
        }

        return $html . $btnDel . (self::DIV_ITEM ? '</div>' : '');
    }


    private static function showIsEmptyValues(string $dataType = ''): array
    {
        if (! $dataType) { return []; }
        if (isset(PageTypes::FILE_DEFAULT_VARIABLES[$dataType]) === false) { return []; }
        return PageTypes::FILE_DEFAULT_VARIABLES[$dataType]; 
    }

    private static function getValues(?array $data = []): array
    {
        if (is_array($data) === false || isset($data['value']) === false) { return []; }
        if (is_array($data['value'])) { return $data['value']; }
        try { 
            return jsonDecode($data['value']);
        } 
        catch (Throwable $tr) { return []; }
    }
}