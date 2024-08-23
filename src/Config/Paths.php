<?php 
namespace Sygecon\AdminBundle\Config;

final class Paths
{
    public const ROOT_PUBLIC_PATH   = 'assets';
    public const SYSTEM_PUBLIC_PATH = 'control';
    
    public const MODEL  = self::SYSTEM_PUBLIC_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR;

    public const CLASS_TITLE = self::SYSTEM_PUBLIC_PATH . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'Titles.json';

    public const ICONS = FCPATH . 'images' . DIRECTORY_SEPARATOR . 'fa-icons' . DIRECTORY_SEPARATOR;

    // images pageicon
    public const PAGE_ICONS = self::ROOT_PUBLIC_PATH . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'catalog' . DIRECTORY_SEPARATOR . 'icons';
    
    // Импорт HTML data
    public const IMPORT = WRITEPATH . 'import' . DIRECTORY_SEPARATOR;
    
    // Путь к папке с Аватарами пользователей
    public const AVATAR = WRITEPATH . 'base' . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR;
    
    // Запрещенные названия Классов
    public const FORBID_CLASS_NAMES = [
		'user', 'model', 'boot', 'tmpls', 'install', 'errors', 'search', 
        'block_layers', 'block_resources', 'basecontroller', SLUG_ADMIN
	];

    // Путь к папке с ресурсами пользователя
    public static function byUserID(int $id = 0): string 
    {
        if (! $id) { return FCPATH; }
        return WRITEPATH . 'base' . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR;
    }
}