<?php
namespace Sygecon\AdminBundle\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\Config\Services;
use Myth\Auth\Entities\User;
use Myth\Auth\Models\UserModel;

class MeSeeder extends Seeder
{
    public function run()
	{
        $db = \Config\Database::connect();
        if ($db) {
            $data = [
                [
                    'name'  => 'ru',
                    'title' => 'Русский',
                    'icon' => 'faf-russia'
                ],
                [
                    'name'  => 'en',
                    'title' => 'English',
                    'icon' => 'faf-england'
                ],
                [
                    'name'  => 'de',
                    'title' => 'Deutsche',
                    'icon' => 'faf-germany'
                ],
                [
                    'name'  => 'fr',
                    'title' => 'Français'
                ],
                // [
                //     'name'  => 'it',
                //     'title' => 'Italiano'
                // ],
                // [
                //     'name'  => 'tr',
                //     'title' => 'Türk'
                // ],
                // [
                //     'name'  => 'ch',
                //     'title' => '中國人'
                // ],
            ];
            $builder = $db->table('language');
            foreach($data as $field) {
                $builder->insert($field);
            }
        }

        $authorize = Services::authorization();
        if ($authorize) {
            // Role
            $authorize->createGroup('admin', 'Администраторы. Полный доступ к настройкам');
            $authorize->createGroup('member', 'Вебмастер - редактор страниц');
            $authorize->createGroup('client', 'Клиенты компании');
            // Permission
            $authorize->createPermission('back-office', 'Доступ к панели администрирования');
            $authorize->createPermission('manage-user', 'Создавать, удалять или изменять пользователей');
            $authorize->createPermission('role-permission', 'Редактировать и определять разрешения для роли');
            $authorize->createPermission('menu-permission', 'Создавать, удалять или изменять меню');
            $authorize->createPermission('content-manager', 'Администрирование сайта и размещение информации');
            $authorize->createPermission('client', 'Права клиента - пользователя');
            // Assign Permission to role
            $authorize->addPermissionToGroup('back-office', 'admin');
            $authorize->addPermissionToGroup('manage-user', 'admin');
            $authorize->addPermissionToGroup('role-permission', 'admin');
            $authorize->addPermissionToGroup('menu-permission', 'admin');
            $authorize->addPermissionToGroup('back-office', 'member');
            $authorize->addPermissionToGroup('client', 'client');
        }

        $users = new UserModel();
        if ($users) {
            // User
            $users->save(new User([
                'email'    => 'admin@gmail.com',
                'username' => 'admin',
                'password' => 'admin',
                'active'   => '1',
            ]));
        }
        
	}
}
