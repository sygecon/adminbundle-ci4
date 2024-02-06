<?php 
namespace Sygecon\AdminBundle\Config;

use Sygecon\AdminBundle\Config\Paths;

final class PageTypes 
{
    // Имя объединенного файла темы, *.js *.css
    public const THEME_MINIFY_FILE_NAME = 'theme.min';

    /* Params data List */
    public const FORM_LIST_FIELDS = 'form_list_items_';

    public const FORM_LIST_TYPE_SEND_ALL_LANG = ['page'];

    /* File type Icons */
    public const FILE_ICON = [
        'pdf'   => '/images/icons/file-formats-1/svg/pdf.svg',
        'media' => '/images/icons/file-formats-3/svg/video.svg',
        'video' => '/images/icons/file-formats-3/svg/video.svg',
        'audio' => '/images/icons/file-formats-3/svg/audio.svg',
        'doc'   => '/images/icons/file-formats-1/svg/doc.svg',
        'css'   =>  '/images/icons/file-formats-1/svg/css.svg',
        'scss'  => '/images/icons/file-formats-1/svg/css.svg',
        'arc'   => '/images/icons/file-formats-3/svg/arc.svg',
        'js'    => '/images/icons/file-formats-3/svg/code.svg',
        'code'  => '/images/icons/file-formats-3/svg/code.svg',
        'image' => '/images/icons/file-formats-3/svg/image.svg',
        'image_media' => '/images/icons/file-formats-3/svg/video.svg',
        'page'  => '/images/icons-main/icons/link-45deg.svg'
    ];

    public const FILE_PATH = [
        'image' => Paths::ROOT_PUBLIC_PATH . DIRECTORY_SEPARATOR . 'images',
        'image_media' => Paths::ROOT_PUBLIC_PATH,
        'media' => Paths::ROOT_PUBLIC_PATH . DIRECTORY_SEPARATOR . 'media',
        'video' => Paths::ROOT_PUBLIC_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . 'video',
        'audio' => Paths::ROOT_PUBLIC_PATH . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . 'audio',
        'css'   => Paths::ROOT_PUBLIC_PATH . DIRECTORY_SEPARATOR . 'css',
        'scss'  => Paths::ROOT_PUBLIC_PATH . DIRECTORY_SEPARATOR . 'scss',
        'js'    => 'js',
        'file'  => Paths::ROOT_PUBLIC_PATH . DIRECTORY_SEPARATOR . 'files',
        'files' => Paths::ROOT_PUBLIC_PATH . DIRECTORY_SEPARATOR . 'files',
        'pdf'   => Paths::ROOT_PUBLIC_PATH . DIRECTORY_SEPARATOR . 'files',
        'doc'   => Paths::ROOT_PUBLIC_PATH . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'doc',
        'xls'   => Paths::ROOT_PUBLIC_PATH . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'doc',
        'arc'   => 'backup',
        'code'  => 'files' . DIRECTORY_SEPARATOR . 'source'
    ];

    public const FILE_HIDE = [
        'js'    => 'theme.min',
        'css'   => 'theme.min',
    ];

    public const FILE_FILTER_EXT = [
        'image' => ['svg', 'svgz', 'jpeg', 'jpg', 'png', 'gif', 'ico', 'bmp', 'webp', 'xbm', 'tif', 'tiff', 'pjp', 'jfif', 'pjpeg', 'avif'],
        'image_pageicon' => ['svg', 'svgz', 'jpeg', 'jpg', 'png', 'gif', 'webp', 'xbm', 'pjpeg'],
        'media' => ['ogm', 'avi', 'mpg', 'mpeg', 'wmv', 'asx', 'mp4', 'm4v', 'ogv', 'mov', 'webm', 'weba', 'opus', 'flac', 'm4a', 'ogg', 'wav', 'oga', 'mid', 'mp3', 'aiff', 'wma', 'au'],
        'video' => ['ogm', 'avi', 'mpg', 'mpeg', 'wmv', 'asx', 'mp4', 'm4v', 'ogv', 'mov', 'webm'],
        'audio' => ['weba', 'opus', 'flac', 'm4a', 'ogg', 'wav', 'oga', 'mid', 'mp3', 'aiff', 'wma', 'au'],
        'image_media' => ['svg', 'svgz', 'jpeg', 'jpg', 'png', 'gif', 'ico', 'bmp', 'webp', 'xbm', 'tif', 'tiff', 'pjp', 'jfif', 'pjpeg', 'avif', 'ogm', 'avi', 'mpg', 'mpeg', 'wmv', 'asx', 'mp4', 'm4v', 'ogv', 'mov', 'webm', 'weba', 'opus', 'flac', 'm4a', 'ogg', 'wav', 'oga', 'mid', 'mp3', 'aiff', 'wma', 'au'],
        'pdf'   => ['pdf'],
        'js'    => ['js'],
        'json'  => ['json'],
        'css'   => ['css'],
        'scss'  => ['scss'],
        'arc'   => ['zip', 'rar', '7z', '7zip', 'tar', 'gzip', 'bz', 'bz2', 'bzip', 'bzip2'],
        'doc'   => ['xsl', 'xbl', 'txt', 'text', 'htm', 'xslt', 'ehxtml', 'html', 'ics', 'shtml', 'xml', 'css', 'scss', 'shtm', 'csv', 'tpl', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'docm', 'xlsm', 'pptm', 'pdf', 'pot', 'potx', 'odt', 'ott', 'odm', 'ods', 'ots', 'otg', 'odp', 'otp', 'odf', 'psw', 'rtf', 'cmo', 'odx-d'],
        'code'  => ['php', 'js', 'sphp', 'sh', 'c', 'inc', 'pas', 'pl']
    ];

    public const FILE_DEFAULT_VARIABLES = [
        'page'  => ['id'=>'', 'href'=>'', 'text'=>''],
        'link'  => ['href'=>'', 'title'=>'', 'text'=>''],
        'image' => ['src'=>'', 'alt'=>'', 'title'=>''],
        'pdf'   => ['src'=>'', 'poster'=> PageTypes::FILE_ICON['pdf'], 'text'=>'', 'title'=>''],
        'css'   => ['src'=>'', 'poster'=> PageTypes::FILE_ICON['css']],
        'js'    => ['src'=>'', 'poster'=> PageTypes::FILE_ICON['css']],
        'media' => ['src'=>'', 'poster'=> PageTypes::FILE_ICON['media'], 'width'=>'360', 'height'=>'300', 'title'=>''],
        'video' => ['src'=>'', 'poster'=> PageTypes::FILE_ICON['video'], 'width'=>'360', 'height'=>'300', 'title'=>''],
        'audio' => ['src'=>'', 'poster'=> PageTypes::FILE_ICON['audio'], 'title'=>'']
    ];
}