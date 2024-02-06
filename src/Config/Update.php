<?php 
namespace Sygecon\AdminBundle\Config;

final class Update
{
    public const WORK_FOLDER    = WRITEPATH . 'update' . DIRECTORY_SEPARATOR;
    public const VENDOR_FOLDER  = ROOTPATH . 'vendor' . DIRECTORY_SEPARATOR;
    public const LOG_FILE       = self::WORK_FOLDER . 'log.json';

    public const DOWNLOAD_LINK = 'https://github.com/%s/archive/refs/tags/%s.zip';

    public const FILTER_CONFIG = [
        'DEARule' => 1
    ];

    public const FILTER_FRAMEWORK_CONFIG = [
        'Boot' => 1, 'Cache.php' => 1,
        'App.php' => 1, 'Autoload.php' => 1, 
        'Database.php' => 1, 'Email.php' => 1, 
        'Filters.php' => 1, 'Generators.php' => 1, 
        'Routes.php' => 1, 'Routing.php' => 1, 
        'Security.php' => 1, 'Validation.php' => 1,
        'Cookie.php' => 1, 'Encryption.php' => 1, 
        'Publisher.php' => 1, 'Session.php' => 1, 'View.php' => 1
    ];

    public const FILTER_FILENAME = [
        // '.htaccess' => 1,
        'index.html' => 1,
        '.env' => 1,
        'favicon.ico' => 1,
        'robots.txt' => 1,
    ];

    public static $autoloader   = [
        'sygecon/wp-adminka' => 'public',
        'codeigniter4/framework' => 'system',
        'codeigniter4/translations' => 'lang',
        'codeigniter4/settings' => '',
        'codeigniter4/shield' => '',
        'datamweb/codeigniter-dea-rule' => 'config',
        'datamweb/codeigniter-multi-captcha' => '',
        'psr/http' => '',
        'kint-php/kint' => '',
        'laminas/laminas-escaper' => '',
        'matthiasmullie/path-converter' => '',
        'matthiasmullie/minify' => '',
        'vitejs/vite' => '',
        'vitejs/vite-plugin-vue' => 1,
        'tattersoftware/codeigniter4-themes' => ''
    ];

    public static function pathWithVendor(string $path): string
    {
        return self::VENDOR_FOLDER . trim(
            str_replace('/', DIRECTORY_SEPARATOR, self::normalizeUri($path))
        , DIRECTORY_SEPARATOR);
    }

    public static function normalizeUri(string $uri): string
    {
        return strtolower(trim(trim(str_replace('\\', '/', $uri)), '."/'));
    }

}