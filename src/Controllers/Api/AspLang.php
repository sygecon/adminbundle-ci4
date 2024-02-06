<?php
namespace Sygecon\AdminBundle\Controllers\Api;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;
use Sygecon\AdminBundle\Models\Component\LanguageModel as BaseModel;

final class AspLang extends Controller
{
    use ResponseTrait;

    public function me_list(): ResponseInterface 
    {
        if ($data = cache('Language_All_Json')) { return $this->respond($data, 200); }
        $model = new BaseModel();
        $data = $model->getAll();
        if ($data) {
            cache()->save('Language_All_Json', jsonEncode($data, false), 40320);
        }
        return $this->respond($data, 200);
    }

    public function me_get(string $name = ''): ResponseInterface 
    {
        if (! $name) { 
            $config = config('App');
            $name = $config->defaultLocale; 
        }
        if (! $data = cache('Language_Name_' . $name)) {
            $model = new BaseModel();
            $data = jsonEncode($model->builder()->where('name', $name)->get()->getRow(), false);
            cache()->save('Language_Name_' . $name, $data, 40320);
        }
        return $this->respond($data, 200);
    }
}