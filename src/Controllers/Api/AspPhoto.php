<?php
namespace Sygecon\AdminBundle\Controllers\Api;

use CodeIgniter\Controller;
use Sygecon\AdminBundle\Config\Paths;
use RuntimeException;

use function trim;
use function is_file;
use function is_readable;
use function readfile;
use function helper;

final class AspPhoto extends Controller
{
    protected int $id;

    public function __construct() {
        helper('auth');
        $this->id = (int) user_id();
        if (! isset($this->id) || $this->id === 0) { 
            throw new RuntimeException('Failed! You do not have access to this page.'); 
        }
    }

    public function me_avatar(int $userId = 0) 
    {
        $fn = '';
        $path = Paths::AVATAR;
        if ($userId === 0) { $userId = $this->id; }
        if (!$fn = $this->imgExists($path . $userId)) { 
            $fn = $this->imgExists($path . 'icons' . DIRECTORY_SEPARATOR . 'man-user'); 
        }
        return $this->loader($fn); 
    }

    public function me_image(...$paths) 
    {
        if (isset($this->id) === false || ! $this->id) return $this->loader();
        // The route is defined as:
        // $routes->get('users/(:num)/gallery(:any)', 'Galleries::showUserGallery/$1/$2');
        // Generate the URI to link to user ID 15, gallery 12:
        // <a href="php= url_to('Galleries::showUserGallery', 15, 12) =php">View Gallery</a>
        // Result: 'http://example.com/users/15/gallery/12'
        $path = Paths::byUserID($this->id) . Paths::ROOT_PUBLIC_PATH . DIRECTORY_SEPARATOR . 'images';
        $path .= $this->pathFromArgs($paths);
        return $this->loader($this->imgExists($path));
    }

    // Темы
    public function me_theme(...$paths) 
    {
        $len = count($paths);
        if ($len > 1) {
            $path = FCPATH . 'themes'. DIRECTORY_SEPARATOR . trim($paths[0], ' /\\');
            unset($paths[0]);
            $name = $this->pathFromArgs($paths);
            if (! $result = $this->imgExists($path . $name)) {
                if (! $result = $this->imgExists($path . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . $name)) {
                    $result = $this->imgExists($path . DIRECTORY_SEPARATOR . 'images' . $name);
                }
            }
            return $this->loader($result);
        }
        return $this->loader('');
    }

    //
    private function pathFromArgs(array $args = []): string 
    {
        $path = '';
        foreach ($args as $slug) { $path .= DIRECTORY_SEPARATOR . trim($slug, ' /\\'); }
        return castingPath($path);
    }

    private function imgExists($fn): string 
    {
        $allowedExts = ['.webp', '.gif', '.jpeg', '.ico', '.svgz', '.bmp', '.xbm', '.pjp', '.jfif', '.pjpeg', '.avif', '.jpg', '.png', '.svg'];
        foreach ($allowedExts as $ext) { 
            $file = $fn . $ext;
            if (is_file($file) && is_readable($file)) { return $file; } 
        }
        return '';
    }

    private function loader(string $fn = '') 
    {
        if ($fn) { return readfile($fn); }
        return readfile(FCPATH . 'images' . DIRECTORY_SEPARATOR . 'no-image.webp');
    }
}