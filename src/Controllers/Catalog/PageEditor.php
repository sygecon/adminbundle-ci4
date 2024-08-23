<?php namespace Sygecon\AdminBundle\Controllers\Catalog;

use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Models\Catalog\PagesModel;
use Sygecon\AdminBundle\Models\Component\SheetModel;
use Sygecon\AdminBundle\Config\Catalog as CatVar;
use Sygecon\AdminBundle\Libraries\Tree\NestedGet as TreeGet;

final class PageEditor extends AdminController {

    protected const ICON = 'file-earmark-code';

    // Вывод данных страницы
    public function index(string $lang = APP_DEFAULT_LOCALE, int $id = 0): string
	{
        ignore_user_abort(true);
        set_time_limit(0);
        $data['lang'] = strtolower($lang);
        if (! isset(SUPPORTED_LOCALES[$data['lang']])) { $data['lang'] = APP_DEFAULT_LOCALE; }

        if (! $data['data'] = $this->getPage($lang, (int) $id)) return $this->pageNotFound(); 
        
        $data['head'] = [
            'icon' => self::ICON, 
            //'title' => $data['data']->{'title'}, 
            'h1' => lang('Admin.menu.sidebar.pageEditor'),
            'breadcrumb' => $data['data']->breadcrumb,
            'link' => CatVar::CURRENT_LINK . '/' . $lang . '/' . (int) $data['data']->{'parent'}
        ]; 
        unset($data['data']->breadcrumb);
        return $this->build('page_editor', $data, 'Catalog');
    }

    // Изменение данных страницы
    public function update(int $idPage = 0, string $sheet = '', string $lang = '', int $idNode = 0): string
	{
        ignore_user_abort(true);
        set_time_limit(0);
        if (! $data = $this->request->getRawInput()) return $this->pageNotFound();

        if ($sheet === 'announce' && array_key_exists('_summary_', $data)) {
            $model = new PagesModel();
            return $this->successfulResponse($model->saveData($idPage, [
                'summary' => $data['_summary_'], 'updated_at' => meDate()
            ]));
        }

        helper('model');
        if (! $modelName = getClassModelName($sheet)) return $this->pageNotFound();
        if (enum_exists($modelName, false)) return $this->pageNotFound();

        $model = new $modelName();
        return $this->successfulResponse($model->setData((int) $idPage, $data));
    }

    //Tree Catalog TinyMCE
    public function getCatalog(string $lang = APP_DEFAULT_LOCALE, int $id = 0, int $nodeId = 0): string
    {
        $data = [];
        if ($id && $lang && $this->request->isAJAX()) {
            $lang = strtolower($lang);
            if (! isset(SUPPORTED_LOCALES[$lang])) { $lang = APP_DEFAULT_LOCALE; }
            $onlyPath = false;

            if ($this->request->is('put')) {
                if ($getData = $this->request->getRawInput()) {
                    $onlyPath = isset($getData['only-folder']);
                }
            }
            $data = CatVar::dataForHtmlEditor($lang, $nodeId, $onlyPath);
        }
        return $this->successfulResponse($data, true);
    }

    protected function getPage(string $lang, int $id): ?object
    {
        if (isset($id) && $id && isset($lang) && $lang) {
            $tree = new TreeGet(CatVar::TREE_ID);
            $data = $tree->getNode($id, $lang, '', 'object');
            if ($data) {
                $parentId = (int) $data->{'parent'};
                if ($parentId !== 0) {
                    $data->{'breadcrumb'} = $tree->getLink($parentId, $lang, CatVar::CURRENT_LINK, true, false, false);
                } else {
                    $data->{'breadcrumb'} = '<span class="h6" style="vertical-align:middle"><ol class="breadcrumb">' .
                        '<li class="breadcrumb-item icon"><a href="' . CatVar::CURRENT_LINK . 
                        '" asp-lazy="home" class="toolbtn"></a></li></ol></span>';
                }
                $sheetModel = new SheetModel();
                $data->{'model'} = $sheetModel->getDataPage((int) $data->{'layout_id'}, (int) $data->id);

                return $data;
            }
        }
        return null;
    }
}