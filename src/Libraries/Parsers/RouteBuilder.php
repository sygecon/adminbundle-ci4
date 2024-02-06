<?php 
namespace Sygecon\AdminBundle\Libraries\Parsers;

//use CodeIgniter\Config\Services;
//use ReflectionClass;

use function count;
use function in_array;
use function is_countable;
use function preg_match_all;
use function sprintf;

class RouteBuilder 
{
    protected string $namespace = 'App';
    protected string $classNamespace = 'Controllers';

    protected string $routesFile = APPPATH . 'Config' . DIRECTORY_SEPARATOR . 'Boot' . DIRECTORY_SEPARATOR . 'routes.php';
    
    protected ?string $controller = null;

    public array $finderControls = [];

    private string $code = '';

    private string $url;

    public array $listLink = [];

    public array $methods;

    private array $options;

    private ?string $controllerMethod = null;

    /**
     * @param string[] $namespaces namespaces to search
     */
    public function __construct(string $url = '/', array $methods = ['get'], array $options = [], bool $isGroup = false) {
        $this->isGroup = $isGroup;
        $this->options = $options;
        $this->url = toUrl($url);
        $this->validateMethods($methods);
        $this->class = '';
        $this->groupClass = '';
        $this->code = '';
        // if (file_exists($this->routesFile)) {
        //     $this->code = file_get_contents($this->routesFile);
        // }
    }

    /**
     * @param class-string $controller
     */
    public function setController(string $controller): void
    {
        $this->controller = $controller;
    }

    public function setControllerMethod(string $controllerMethod): void
    {
        $this->controllerMethod = '\\' . $controllerMethod . $this->getArgs();
    }

    /**
     * Returns the path like `/$1/$2` for placeholders
     */
    private function getArgs(): string
    {
        $matches = [];
        preg_match_all('/\(.+?\)/', $this->url, $matches);
        $count = is_countable($matches[0]) ? count($matches[0]) : 0;
        $args = '';
        if ($count > 0) { for ($i = 1; $i <= $count; $i++) { $args .= '/$' . $i; } }
        return $args;
    }

    /**
     * @param string[] $methods
     */
    private function validateMethods(array $methods): void
    {
        $validMethods = ['get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'cli'];
        foreach ($methods as $method) {
            if (! in_array($method, $validMethods, true)) {
                if ($method === 'add') { return; }
                return;
            }
        }
    }

    private function varExport($expression): string
    {
        $export = var_export($expression, true);
        $patterns = [
            '/array \(/'                            => '[',
            '/^([ ]*)\)(,?)$/m'                     => '$1]$2',
            "/=>[ ]?\n[ ]+\\[/"                     => '=> [',
            "/([ ]*)(\\'[^\\']+\\') => ([\\[\\'])/" => '$1$2 => $3',
        ];
        $export = preg_replace(array_keys($patterns), array_values($patterns), $export);
        if ($export === null) { return ''; }
        return $export;
    }

    private function asCode(): string
    {
        assert(
            $this->controllerMethod !== null,
            'You must set $controllerMethod with setControllerMethod().'
        );

        $code = '';

        foreach ($this->methods as $method) {
            if ($this->options === []) {
                $code .= sprintf(
                    "\$routes->%s('%s', '%s');",
                    $method,
                    $this->url,
                    $this->controllerMethod,
                ) . "\n";

                continue;
            }

            $code .= sprintf(
                "\$routes->%s('%s', '%s', %s);",
                $method,
                $this->url,
                $this->controllerMethod,
                $this->varExport($this->options)
            ) . "\n";
        }

        return $code;
    }

    public function asGroupCode(string $name = '', array $option = [], array $routes = []): string
    {
        $options = ['controller' => $this->controller];
        $options = array_merge($options, $this->options);
        $options = str_replace(
            ["\n", '  ', ',]', '\\\\', "[ '", ', ]'],
            [' ', '', ']', '\\', "['", ']'],
            $this->varExport($options)
        );
        if ($option) {
            $options = array_merge($options, str_replace(
                ["\n", '  ', ',]'],
                ['', '', ']'],
                $option
            ));
        }

        $code = sprintf(
            "\$routes->group('%s', %s, static function (\$routes) {",
            $name,
            $options
        ) . "\n";

        $routeCode = '';

        foreach ($routes as $route) {
            $routeCode .= $this->asCode();
        }

        $routeCode = preg_replace('/^/m', '    ', $routeCode);
        $code .= $routeCode;

        $code .= '});' . "\n";

        return $code;
    }

    private function findInside(string $text = ''): array 
    {
        $res = [];
        if (! $text) { return $res; }
        
        $m = [];
        $success = preg_match_all(
            '|' . preg_quote('public', '/') . '\s+' . preg_quote('function', '/') . '\s+([^\.)]+)'. preg_quote('(', '/') . '+([^\.)]+)'. preg_quote(')', '/') . '+|i'
        , $text, $m);
        if (! $success || ! isset($m[2])) { return $res; }

        foreach ($m[1] as $i => $var) {
            $res[$i]['name'] = trim($var);
        }

        foreach ($m[2] as $i => $var) {
            $res[$i]['param'] = trim($var);
        }
        return $res;
    }
    
}

