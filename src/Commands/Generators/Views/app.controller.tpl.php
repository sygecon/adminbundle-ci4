<@php 
declare(strict_types=1);
namespace {namespace};

use {useStatement};

<?php if ($type === 'controller'): ?>

use CodeIgniter\API\ResponseTrait;
<?php endif ?>
use CodeIgniter\HTTP\ResponseInterface;

final class {class} extends {extends}
{
<?php if ($type === 'controller'): ?>
    use ResponseTrait;
<?php endif ?>
    protected $modelName    = '';
    protected $layout       = '';
    protected $cacheTime    = 60;
    protected $modelOptions = [];
    protected $helpers      = []; // 'setting'

<?php if ($type === 'controller'): ?>
    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    public function index()
    {
        //
    }

    /**
     * Return the properties of a resource object
     *
     * @return mixed
     */
    public function show($id = null)
    {
        //
    }

    /**
     * Return a new resource object, with default properties
     *
     * @return mixed
     */
    public function new()
    {
        //
    }

    /**
     * Create a new resource object, from "posted" parameters
     *
     * @return mixed
     */
    public function create()
    {
        //
    }

    /**
     * Return the editable properties of a resource object
     *
     * @return mixed
     */
    public function edit($id = null)
    {
        //
    }

    /**
     * Add or update a model resource, from "posted" properties
     *
     * @return mixed
     */
    public function update($id = null)
    {
        //
    }

    /**
     * Delete the designated resource object from the model
     *
     * @return mixed
     */
    public function delete($id = null)
    {
        //
    }
<?php elseif ($type === 'presenter'): ?>
    /**
     * Present a view of resource objects
     *
     * @return mixed
     */
    public function index()
    {
        //
    }

    /**
     * Present a view to present a specific resource object
     *
     * @param mixed $id
     *
     * @return mixed
     */
    public function show($id = null)
    {
        //
    }

    /**
     * Present a view to present a new single resource object
     *
     * @return mixed
     */
    public function new()
    {
        //
    }

    /**
     * Process the creation/insertion of a new resource object.
     * This should be a POST.
     *
     * @return mixed
     */
    public function create()
    {
        //
    }

    /**
     * Present a view to edit the properties of a specific resource object
     *
     * @param mixed $id
     *
     * @return mixed
     */
    public function edit($id = null)
    {
        //
    }

    /**
     * Process the updating, full or partial, of a specific resource object.
     * This should be a POST.
     *
     * @param mixed $id
     *
     * @return mixed
     */
    public function update($id = null)
    {
        //
    }

    /**
     * Present a view to confirm the deletion of a specific resource object
     *
     * @param mixed $id
     *
     * @return mixed
     */
    public function remove($id = null)
    {
        //
    }

    /**
     * Process the deletion of a specific resource object
     *
     * @param mixed $id
     *
     * @return mixed
     */
    public function delete($id = null)
    {
        //
    }
<?php else: ?>
    
    /**
     * Return the properties of a page
     * @return ResponseInterface
    */
    public function index(): ResponseInterface
    {
        //$this->layuot = 'about';
        $cacheName = $this->cacheName();
        if ($foo = cache($cacheName)) { return $this->respond($foo, 200); }

        if (! $foo = $this->initModel()
            ->getPage(true)
            ->build()
        ) { 
            return $this->pageNotFound(); 
        }

        if ($foo && $this->cacheTime) { cache()->save($cacheName, $foo, $this->cacheTime); }
        return $this->respond($foo, 200);
    }
    
    /**
     * Return the properties of a list page
     * @return ResponseInterface
     */
    public function view(string ...$slug): ResponseInterface
    {
        //$this->layuot = 'about_list';
        $cacheName = $this->cacheName();
        if ($foo = cache($cacheName)) { return $this->respond($foo, 200); }

        if (! $foo = $this->initModel()
            ->getChildPages()
            ->build()
        ) { 
            return $this->pageNotFound(); 
        }
        
        if ($foo && $this->cacheTime) { cache()->save($cacheName, $foo, $this->cacheTime); }
        return $this->respond($foo, 200);
    }

<?php endif ?>
}
