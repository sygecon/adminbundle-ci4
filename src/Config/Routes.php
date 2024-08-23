<?php
use Sygecon\AdminBundle\Config\AccessControl;

/**
 * User profile
**/
$routes->addRedirect('profile', 'user');

/**
 * Me Captcha
**/
$routes->post('api/captcha/get', 
    '\Sygecon\AdminBundle\Controllers\Api\AspCaptcha::meAnchor'
);

/**
 * Administrative panel Resources
**/
$routes->group('api', ['filter' => AccessControl::FILTER['direction']],
    function ($routes) {
        $routes->group('photo', ['namespace' => 'Sygecon\AdminBundle\Controllers\Api'], 
            function ($routes) {
                $routes->get('(:segment)', 'AspPhoto::me_$1');
                $routes->get('(:segment)/(:any)', 'AspPhoto::me_$1/$2');
            }
        );
        $routes->group('direct', [
            'filter' => AccessControl::FILTER['direction'],
            'namespace' => 'Sygecon\AdminBundle\Controllers\Api'
        ], function ($routes) {
            $routes->get('lang/(:segment)', 'AspLang::me_$1');
            $routes->get('lang/(:segment)/(:segment)', 'AspLang::me_$1/$2');

            $routes->get('nav/(:segment)', 'AspNav::me_$1');
            $routes->get('nav/(:segment)/(:segment)', 'AspNav::me_$1/$2');

            $routes->get('slug/pages/(:segment)/(:segment)', 'AspSlug::me_pages/$1/$2');
            $routes->match(['get', 'put'], 'slug/linkpages', 'AspSlug::me_links');

            $routes->get('layout/list', 'AspLayout::me_list');
            $routes->get('layout/(:segment)/(:num)', 'AspLayout::me_$1/$2');

            $routes->get('control/(:segment)', 'AspControl::me_$1');

            $routes->match(['get', 'post'], 'fm/(:segment)', 'AspFm::me_$1');

            $routes->get('icons/(:segment)', 'AspIcons::me_$1');

            $routes->get('seo/(:segment)', 'AspSeo::me_$1');

            $routes->get('photo/(:segment)', 'AspPhoto::me_$1');
            $routes->get('photo/(:segment)/(:num)', 'AspPhoto::me_$1/$2');

            $routes->match(['get', 'post'], 'resource/(:segment)/(:num)/(:segment)', 'AspResource::me_$1/$2/$3');

        });    
    }
);

/**
 * User routes.
**/
$routes->group('user', [
    //'filter'    => AccessControl::FILTER['direction'],
    'namespace' => 'Sygecon\AdminBundle\Controllers\Users',
], function ($routes) {
    $routes->get('password-reset', 'PasswordControl::reset');
    $routes->get('change-password', 'PasswordControl::index/on');
    $routes->get('set-password', 'PasswordControl::index');
    $routes->post('set-password', 'PasswordControl::update');

    $routes->get('/', 'Profile');
    $routes->get('/(:segment)', 'Profile::index/$1');
});

/**
 * Administration Panel
**/
$routes->group(SLUG_ADMIN, ['filter' => AccessControl::FILTER['direction']],
    function ($routes) {
        // $routes->group('/', ['namespace' => 'Sygecon\AdminBundle\Controllers'], 
        //     function ($routes) {
        //         $routes->get('/', 'ManagePartitions');
        //     }
        // );
        $routes->addRedirect('/', '/' . SLUG_ADMIN . '/catalog/' . APP_DEFAULT_LOCALE);

        // Пользователи
        $routes->group('user/staff', [
            'filter'    => AccessControl::FILTER['staff'],
            'namespace' => 'Sygecon\AdminBundle\Controllers\Users',
            ], function ($routes) {
                $routes->get('', 'Manage::index');
                $routes->get('(:num)', 'Manage::index/$1');
                $routes->get('(:segment)/(:num)', 'Manage::$1/$2');
                $routes->get('(:num)/(:segment)', 'Manage::$2/$1');
                $routes->post('', 'Manage::create');
                $routes->post('(:num)/(:segment)', 'Manage::set_$2/$1');
                $routes->put('(:num)', 'Manage::update/$1');
                $routes->delete('(:num)', 'Manage::delete/$1');
            }
        );

        $routes->group('user/clients', [
            'filter'    => AccessControl::FILTER['clients'],
            'namespace' => 'Sygecon\AdminBundle\Controllers\Users',
            ], function ($routes) {
                $routes->get('', 'Manage::index/0/clients');
                $routes->get('(:num)', 'Manage::index/$1/clients');
                $routes->get('(:segment)/(:num)', 'Manage::$1/$2');
                $routes->get('(:num)/(:segment)', 'Manage::$2/$1');
                $routes->post('(:num)/(:segment)', 'Manage::set_$2/$1');
                $routes->put('(:num)', 'Manage::update/$1');
                $routes->delete('(:num)', 'Manage::delete/$1');
            }
        );

        $routes->group('user', [
            'filter'    => AccessControl::FILTER['users'],
            'namespace' => 'Sygecon\AdminBundle\Controllers\Users',
            ], function ($routes) {
                $routes->get('permission', 'Permission::index');
                $routes->get('group', 'Group::index');
                $routes->get('group/edit/(:num)', 'Group::edit/$1');
            }
        ); 
        
        $routes->addRedirect('user', '/user');

        // Шаблоны
        $routes->group('template', [
            'filter'    => AccessControl::FILTER['frontend'],
            'namespace' => 'Sygecon\AdminBundle\Controllers\Template'
        ], function ($routes) {
            // Блоки
            $routes->get('block', 'Block::index');
            $routes->get('block/(:num)', 'Block::index/$1');
            $routes->get('block/(:segment)/(:num)', 'Block::$1/$2');
            $routes->match(['get', 'put'], 'block/(:num)/getcat', 'Block::getCatalog/$1');
            $routes->put('block/(:num)', 'Block::update/$1');
            $routes->post('block', 'Block::create');
            $routes->delete('block/(:num)', 'Block::delete/$1');
            // Переменные
            $routes->get('variables', 'GlobalVariables::index');
            $routes->get('variables/(:segment)', 'GlobalVariables::index/$1');
            $routes->put('variables', 'GlobalVariables::update');
            $routes->put('variables/(:segment)', 'GlobalVariables::update/$1');
            // Макеты
            $routes->get('layout', 'Layout::index');
            $routes->get('layout/(:num)', 'Layout::index/$1');
            $routes->put('layout/(:num)', 'Layout::update/$1');
            $routes->put('layout/(:segment)/(:num)', 'Layout::$1/$2');
            $routes->post('layout', 'Layout::create');
            $routes->delete('layout/(:num)', 'Layout::delete/$1');
            //$routes->put('layout/(:num)/(:segment)', 'Layout::$2/$1');
            // Темы
            $routes->get('theme', 'Theme::index');
            $routes->get('theme/(:num)', 'Theme::index/$1');
            $routes->get('theme/(:segment)', 'Theme::$1');
            $routes->get('theme/(:num)/resource/(:segment)', 'Theme::resource/$1/$2');
            $routes->post('theme/(:num)/resource/(:segment)', 'Theme::resource/$1/$2');
            $routes->post('theme', 'Theme::create');
            $routes->post('theme/(:num)', 'Theme::create/$1');
            $routes->put('theme/(:num)', 'Theme::update/$1');
            $routes->put('theme/(:num)/(:segment)', 'Theme::$2/$1');
            $routes->delete('theme/(:num)', 'Theme::delete/$1');
            $routes->delete('theme/(:num)/(:segment)', 'Theme::delete/$1/$2');
        }); 
        
        // Структурные компоненты
        $routes->group('component', [
            'filter'    => AccessControl::FILTER['backend'],
            'namespace' => 'Sygecon\AdminBundle\Controllers\Component'
        ], function ($routes) {
            // Управление языками
            $routes->get('language', 'Language::index');
            $routes->get('language/(:segment)/(:num)', 'Language::$1/$2');
            $routes->post('language', 'Language::create');
            $routes->put('language/(:num)', 'Language::update/$1');
            $routes->delete('language/(:num)', 'Language::delete/$1');
            // Таблицы
            $routes->get('sheet', 'Sheet::index');
            $routes->get('sheet/(:num)', 'Sheet::index/$1');
            $routes->get('sheet/(:segment)/(:num)', 'Sheet::$1/$2');
            $routes->post('sheet', 'Sheet::create');
            $routes->put('sheet/(:num)', 'Sheet::update/$1');
            $routes->delete('sheet/(:num)', 'Sheet::delete/$1');
            // Связи между каталогами
            $routes->get('relationship', 'Relationship::index');
            $routes->get('relationship/(:segment)/(:num)', 'Relationship::$1/$2');
            $routes->post('relationship', 'Relationship::create');
            $routes->put('relationship/(:num)', 'Relationship::update/$1');
            $routes->delete('relationship/(:num)', 'Relationship::delete/$1');

            $routes->get('control', 'Control::index');
            $routes->get('control/(:segment)', 'Control::index/$1');
            //$routes->put('control', 'Control::update');
            $routes->put('control/(:segment)', 'Control::update/$1');
            $routes->delete('control/(:segment)', 'Control::delete/$1');
            //$routes->post('control/(:segment)', 'Control::update/$1');
            $routes->post('control', 'Control::create');

            $routes->get('routing', 'Routing::index');
            $routes->put('routing', 'Routing::update');

            $routes->get('redirect', 'Redirect::index');
            $routes->put('redirect', 'Redirect::update');
            // Карта сайта
            $routes->get('structure', 'Structure::index');
            $routes->get('structure/apply', 'Structure::applyData');
            $routes->put('structure', 'Structure::update');
        }); 
        
        // Каталог страниц
        $routes->group('catalog', [
            'filter'    => AccessControl::FILTER['catalog'],
            'namespace' => 'Sygecon\AdminBundle\Controllers\Catalog'
        ], function ($routes) {
            // $routes->get('/', 'Pages::index');
            $routes->addRedirect('/', '/' . SLUG_ADMIN . '/catalog/' . APP_DEFAULT_LOCALE);

            $routes->group('page', ['namespace' => 'Sygecon\AdminBundle\Controllers\Catalog'], 
                function ($routes) {
                    $routes->match(['get', 'put'], '(:segment)/(:num)/getcat', 'PageEditor::getCatalog/$1/$2');
                    $routes->match(['get', 'put'], '(:segment)/(:num)/getcat/(:num)', 'PageEditor::getCatalog/$1/$2/$3');
                    $routes->get('(:segment)/(:num)/get', 'PageEditor::getModel/$1/$2');
                    $routes->get('(:segment)/(:num)', 'PageEditor::index/$1/$2');
                    $routes->put('(:segment)/(:num)/(:segment)/(:num)', 'PageEditor::update/$4/$3/$1/$2');
                }
            );

            // $routes->get('(:num)/edit', 'Pages::edit/$1');
            $routes->get('(:segment)/(:num)/edit', 'Pages::edit/$1/$2');
            $routes->get('(:segment)/(:num)/(:num)/edit', 'Pages::edit/$1/$3');

            // $routes->get('parent', 'Pages::parent');
            // $routes->get('(:num)/parent', 'Pages::parent/$1');
            $routes->get('(:segment)/(:num)/parent', 'Pages::parent/$2');
            
            $routes->get('(:segment)', 'Pages::index/$1');
            $routes->get('(:segment)/(:num)', 'Pages::index/$1/$2');
            $routes->get('(:segment)/(:num)/(:num)', 'Pages::index/$1/$2/$3');

            // $routes->put('(:num)', 'Pages::update/$1');
            // $routes->put('(:segment)', 'Pages::update/$1');
            $routes->put('(:segment)/(:num)', 'Pages::update/$1/$2');
            $routes->put('(:segment)/(:num)/(:num)', 'Pages::update/$1/$3/$2');

            // $routes->post('/', 'Pages::create');
            $routes->post('(:segment)', 'Pages::create/$1');
            // $routes->post('(:num)', 'Pages::create/$1');
            $routes->post('(:segment)/(:num)', 'Pages::create/$1/$2');
            $routes->post('(:segment)/(:num)/(:num)', 'Pages::create/$1/$3');

            // $routes->delete('(:num)', 'Pages::delete/$1');
            // $routes->delete('(:segment)', 'Pages::delete/0/$1');
            $routes->delete('(:segment)/(:num)', 'Pages::delete/$2');
            $routes->delete('(:segment)/(:num)/(:num)', 'Pages::delete/$3');
        }); 
        
        // Меню
        $routes->group('navigation/menu', [
            'filter'    => AccessControl::FILTER['frontend'],
            'namespace' => 'Sygecon\AdminBundle\Controllers\Navigation'
        ], function ($routes) {
            $routes->get('/', 'Menu::index');
            $routes->get('(:num)', 'Menu::index/$1');
            $routes->post('/', 'Menu::create');
            $routes->delete('(:num)', 'Menu::delete/$1');
            $routes->put('(:num)', 'Menu::update/$1');
        });  

        $routes->group('navigation/custom-panels', [
            'filter'    => AccessControl::FILTER['backend'],
            'namespace' => 'Sygecon\AdminBundle\Controllers\Navigation'
        ], function ($routes) {
            $routes->get('/', 'CustomPanel::index');
            $routes->get('(:num)', 'CustomPanel::index/$1');
            $routes->post('/', 'CustomPanel::create');
            $routes->delete('(:num)', 'CustomPanel::delete/$1');
            $routes->put('(:num)', 'CustomPanel::update/$1');
        });

        // Импорт
        $routes->group('import', [
            'filter'    => AccessControl::FILTER['import'],
            'namespace' => 'Sygecon\AdminBundle\Controllers\Import',
            ], function ($routes) {
                // Текстовые данные
                $routes->get('data', 'TextData::index');
                $routes->get('data/(:num)', 'TextData::view/$1');
                $routes->get('data/(:num)/(:segment)', 'TextData::$2/$1');
                $routes->put('data/(:num)', 'TextData::update/$1');
                $routes->post('data', 'TextData::create');
                $routes->delete('data/(:num)', 'TextData::delete/$1');
                
                // Текстовые данные
                $routes->get('resource', 'Resource::index');
                $routes->get('resource/(:num)', 'TextData::view/$1');
                $routes->get('resource/(:num)/(:segment)', 'Resource::$2/$1');
                $routes->put('resource/(:num)', 'Resource::update/$1');
                $routes->post('resource', 'Resource::create');
                $routes->delete('resource/(:num)', 'Resource::delete/$1');
            }
        );
    }
);
