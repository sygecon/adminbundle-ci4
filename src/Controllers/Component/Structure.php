<?php
namespace Sygecon\AdminBundle\Controllers\Component;

use App\Models\Boot\AppModel;
use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Libraries\Parsers\ControlBuilder;
use Sygecon\AdminBundle\Models\Component\SheetModel;
use Sygecon\AdminBundle\Models\Template\LayoutModel;
use Sygecon\AdminBundle\Models\Catalog\PagesModel;
use Throwable;

final class Structure extends AdminController 
{
    private const MAP_FILE_PATH = 'control' . DIRECTORY_SEPARATOR . 'structure.json';

    private string $confRoute   = '';
    private array $models       = [];
    private array $layouts      = [];
    private array $pages        = [];
    private array $controllers  = [];

    private $control;
    
    /** @return string */
    public function index(): string 
    {
        if (strtolower($this->request->getMethod()) !== 'get') return $this->pageNotFound();
        helper('path');
        if (! $data = baseReadFile(self::MAP_FILE_PATH)) $data = "{\n\n}";

        return $this->build('structure', [
            'structure' => $data,
            'head' => [
                'icon' => 'diagram-3', 
                'title' => lang('Admin.menu.sidebar.projectStructureName')
            ]
        ], 'Component'); 
    }

    /** @return string */
    public function update(): string 
    {
        helper(['path']);
        try {
            if (strtolower($this->request->getMethod()) !== 'put') return $this->successfulResponse(false);
            
            $data = file_get_contents('php://input');
            if (! $data) return $this->pageNotFound();
            if (! $pos = mb_strpos($data, '=', 0, 'UTF-8')) return $this->successfulResponse(false);
            
            $name = trim(mb_substr($data, 0, $pos, 'UTF-8'));
            if ($name != 'data_map') return $this->successfulResponse(false);
            $data = trim(mb_substr($data, ++$pos, null, 'UTF-8'));
            if (! $data) $data = "{\n\n}";

            $result = baseWriteFile(self::MAP_FILE_PATH, $data);
            return $this->successfulResponse($result); 
        } catch (Throwable $th) {
            return $this->successfulResponse(false);
        }
    }

    /** @return string */
    public function applyData(): string 
    {
        helper('path');
        
        $text   = baseReadFile(self::MAP_FILE_PATH);
        if (! $text) return $this->successfulResponse(true);

        $this->control   = new ControlBuilder('');
        $this->emptyOptions();
        try {
            if ($data = jsonDecode($text)) $this->buildRoute($data, '');
            if (! $this->pages) return $this->successfulResponse(false);
            $result = false;
            // $this->buildModels();
            // $this->buildLayouts();
            $this->buildControllers();
            $this->buildPages();

            $result = (bool) $this->confRoute;
            baseWriteFile('log.txt', $this->confRoute);
            return $this->successfulResponse($result); 
        } catch (Throwable $th) {
            baseWriteFile('log-error.txt', $th->getMessage());
            return $this->successfulResponse(false);
        }
    }

    private function buildRoute(array $data, string $uri): void 
    {
        foreach ($data as $key => &$value) {
            if (isset($value['Path']) === false) continue;
            $slug = toUrl(trim($value['Path']), true);
              
            $link = $uri;
            if ($link) $link .= '/';
            $link .= $slug;
            
            if ($this->pages && isset($this->pages[$link]) === true) continue;

            $title      = $value['Description'] ?? $slug;
            $control    = $value['Controller'] ?? null;
            $callback   = $value['Callback'] ?? '';
            $relation   = $value['Relationship'] ?? null;
            $isList     = $value['listOfPages'] ?? false;

            $modelName  = $value['Model'] ?? null;
            $pageLayout = $value['PageLayout'] ?? null;
    
            $this->pages[$link] = [
                'name'      => $slug, 
                'title'     => $title,
                'link'      => $link,
                'parentUrl' => $uri
            ];

            // ROUTE Config
            if ($control) {
                $control = trim(str_replace('/', '\\', toClassUrl($control)), '\\ ');

                // ROUTE Config
                $this->confRoute .= $this->control->meLinkToRoute(
                    $link, $control, 
                    (! $isList && $callback ? $callback : $this->control::ROUTE_CALLBACK[0])
                );
                if ($isList) {
                    $nameRange = '(:all)';
                    if (is_bool($isList)) {
                        if ($key === '__ch_ld__') $nameRange = '(:segment)';
                    } else 
                    if (is_string($isList)) {
                        $nameRange = $isList;
                    }
                    $this->confRoute .= $this->control->meLinkToRoute(
                        $link, $control, 
                        ($callback ? $callback : $this->control::ROUTE_CALLBACK[1]),
                        $nameRange
                    );
                }
                $this->confRoute .= "\n";

                // Add controller to list
                if (isset($this->controllers[$control]) === false) {
                    $this->controllers[$control] = ['name' => $control];
                    if ($modelName) $this->controllers[$control]['model'] = $modelName;
                }

                // Add model to list
                if (isset($this->models[$modelName]) === false) $this->models[$modelName] = 1;
                
                // Add layout to list
                if ($modelName && ! $pageLayout) $pageLayout = $modelName;
                if (isset($this->layouts[$pageLayout]) === false) $this->layouts[$pageLayout] = 1;
            }

            if (isset($value['children']) === true && is_array($value['children']) === true) {
                $children = ['__ch_ld__' => $value['children']];
                unset($value['children']);
                $this->buildRoute($children, $link);
            } 
        }
    }

    private function buildModels(): void {
        if (! $this->models) return;

        $modelSheet = new SheetModel();
        foreach($this->models as $name => $value) {
            // Создаем Модель (таблицу)
            if ($name && mb_strlen($name) > 2) {
                $id = $modelSheet->create(['name' => $name, 'title' => mb_ucfirst($name)]);
            }
            unset($this->models[$name]);
        }
        $this->models = [];
    }

    private function buildLayouts(): void
    {
        if (! $this->layouts) return;

        $modelLayout = new LayoutModel();
        foreach($this->layouts as $name => $value) {
            if ($name && mb_strlen($name) > 2) {
                // Создаем Макет
                $id = $modelLayout->create(['name' => $name, 'title' => mb_ucfirst($name)]);
            }
            unset($this->layouts[$name]);
        }
        $modelLayout->clearCache();
        $this->layouts = [];
    }

    private function buildControllers(): void
    {
        if (! $this->controllers) return;

        foreach($this->controllers as $name => $value) {
            $res = $this->control->find($name);
            baseWriteFile($name . '-log.txt', jsonEncode($res, true));
            // Создаем Контроллер
            // if (! $res['file']) $this->control->insert($value);
            $this->controllers[$name] = [];
            unset($this->controllers[$name]);
        }
        $this->controllers = [];
    }

    private function buildPages(): void
    {
        $model = new PagesModel();
        $nodeModel = new AppModel(APP_DEFAULT_LOCALE, $model->db);
        // Сортируем данные по полю 
        $parent = array_column($this->pages, 'parentUrl');
        array_multisort($parent, SORT_ASC, $this->pages);

        baseWriteFile('log-pages.txt', jsonEncode($this->pages, true));
        foreach($this->pages as $name => &$value) {
            if ($id = (int) $nodeModel->nodeIdByUrl($value['link'])) continue;

            if ($value['parentUrl']) {
                $value['parent'] = (int) $nodeModel->nodeIdByUrl($value['parentUrl']);
            }
            unset($value['parentUrl'], $value['link']);
            // Создаем Страницу
            // $id = $model->create($value);

            unset($this->pages[$name]);
        }
    }

    private function emptyOptions(): void
    {
        $this->confRoute    = '';
        $this->pages        = [];
        $this->controllers  = [];
        $this->models       = [];
        $this->layouts      = [];
    }
}
