<?php
namespace Sygecon\AdminBundle\Controllers\Api;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;
use Sygecon\AdminBundle\Config\Paths;
use RuntimeException;

use function trim;
use function is_file;
use function is_readable;
use function readfile;
use function helper;

final class AspPhoto extends Controller
{
    use ResponseTrait;

    protected int $id;

    public function __construct() {
        helper('auth');
        $this->id = (int) user_id();
        if (!isset($this->id) || $this->id === 0) { 
            throw new RuntimeException('Failed! You do not have access to this page.'); 
        }
    }

    public function me_avatar(int $userId = 0): ResponseInterface 
    {
        $fn = '';
        $path = Paths::AVATAR;
        if ($userId === 0) { $userId = $this->id; }
        if (!$fn = $this->imgExists($path . $userId)) { 
            $fn = $this->imgExists($path . 'icons' . DIRECTORY_SEPARATOR . 'man-user'); 
        }
        return $this->respond($this->loader($fn), 200); 
    }

    public function me_image(...$paths): ResponseInterface 
    {
        // The route is defined as:
        // $routes->get('users/(:num)/gallery(:any)', 'Galleries::showUserGallery/$1/$2');
        // Generate the URI to link to user ID 15, gallery 12:
        // <a href="php= url_to('Galleries::showUserGallery', 15, 12) =php">View Gallery</a>
        // Result: 'http://example.com/users/15/gallery/12'

        $path = Paths::userImages($this->id) . $this->pathFromArgs($paths);
        return $this->respond($this->loader($this->imgExists($path)), 200);
    }

    // Темы
    public function me_theme(...$paths): ResponseInterface 
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
            return $this->respond($this->loader($result), 200);
        }
        return $this->respond($this->loader(''), 200);
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