<?php 
namespace Sygecon\AdminBundle\Config;

final class Paths
{
    public const FILTER_CLASS_NAME = [
        'basecontroller', 'errors', 'search', SLUG_ADMIN
    ];

    public const AVATAR = WRITEPATH . 'base' . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR;
    
    public const MODEL =  'control' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR;

    public const CLASS_TITLE = 'control' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'Titles.json';

    // images pageicon
    public const PAGE_ICONS = 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'catalog' . DIRECTORY_SEPARATOR . 'icons';
    
    public const ICONS = FCPATH . 'images' . DIRECTORY_SEPARATOR . 'fa-icons' . DIRECTORY_SEPARATOR;
    
    // import HTML data
    public const IMPORT = WRITEPATH . 'import' . DIRECTORY_SEPARATOR;
    
    public const ROOT_PUBLIC_PATH = 'assets';

    public static function userImages(int $id = 0): string 
    {
        return self::user($id) . self::ROOT_PUBLIC_PATH . DIRECTORY_SEPARATOR . 'images';
    } 

    public static function userMedia(int $id = 0): string 
    {
        return self::user($id) . self::ROOT_PUBLIC_PATH . DIRECTORY_SEPARATOR . 'media';
    }

    public static function user(int $id = 0): string 
    {
        if (! $id) { return FCPATH; }
        return WRITEPATH . 'base' . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR;
    }
}