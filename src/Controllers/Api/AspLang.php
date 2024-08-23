<?php
namespace Sygecon\AdminBundle\Controllers\Api;

use CodeIgniter\Controller;
use Sygecon\AdminBundle\Models\Component\LanguageModel as BaseModel;

final class AspLang extends Controller
{
    public function me_list(): string 
    {
        if ($foo = cache('Language_All_Json')) return $this->successfulResponse($foo);
        $model = new BaseModel();
        $data = $model->getAll();
        if ($data) cache()->save('Language_All_Json', $data, 40320);
        return $this->successfulResponse($data);
    }

    public function me_get(string $name = ''): array 
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
        return $data;
    }

    protected function successfulResponse(mixed $response): string
	{
		if (! $this->request->isAJAX()) return $response;
		return jsonEncode(['status' => 200, 'message' => $response], false);
	}
}