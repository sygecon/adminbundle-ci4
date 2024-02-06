<?php namespace Sygecon\AdminBundle\Models\Navigation;

use App\Models\Boot\BaseModel as Model;

class MenuModel extends Model
{
    protected $table = 'navigation';
    protected $columnsFindAll = 'id, name, title';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function create(array $data = []): int {
        if (isset($data['name'])) {
            if ($id = $this->model->insert($data)) {
                helper('files');
                createFile(templateFile(PATH_NAVIGATION . DIRECTORY_SEPARATOR . $data['name']), 
                    '<?php ' . "\n\t" . '$__NavConfig = [' . "\n" . 
                    "\t\t//'data' => null,\n" .
                    "\t\t'get' => ['parent' => null, 'recursive' => false, ],\n" .
                    "\t\t// Template\n" .
                    "\t\t'template' => [" .
                    "\t\t\t'prepend' => '" . '<div itemscope="" itemtype="https://schema.org/SiteNavigationElement" id="nav-menu" class="w3-card-2 menu navmenu-hide">' . "',\n" .
                    "\t\t\t'append' => '</div>',\n" .
                    "\t\t\t'activeClass' => 'active',\n" .
                    "\t\t\t'exactActiveClass' => 'exact-active',\n" .
                    "\t\t\t'wrapperTagName' => 'ul',\n" .
                    "\t\t\t'wrapperAttributes' => '',\n" .
                    "\t\t\t'wrapperClass' => '',\n" .
                    "\t\t\t'parentTagName' => 'li',\n" .
                    "\t\t\t'parentAttributes' => '',\n" .
                    "\t\t\t'parentClass' => 'nav',\n" .
                    "\t\t\t'link' => '" . '<a href="{url}" itemprop="url"{class}><span itemprop="name">{title}</span></a>' . "',\n" .
                    "\t\t\t'linkClass' => '',\n" .
                    "\t\t\t'activeClassOnParent' => true,\n" .
                    "\t\t\t'activeClassOnLink' => false,\n" .
                    "\t\t\t'linkActive' => 'javascript:void(0)',\n" .
                    "\t\t];\n" .
                    "\t]\n"
                );
                return (int) $id;
            }
        }
        return 0;
    }

}