<?php
namespace Sygecon\AdminBundle\Controllers\Api;

use CodeIgniter\Controller;
use Config\App;

final class AspCaptcha extends Controller
{
    private const MAX_ATTEMPTS  = 200;

    public function meAnchor(): string 
    {
        $response = ['status' => 404];

        try {
            if ('post' !== strtolower($this->request->getMethod())) {
                return jsonEncode($response, false);
            }
            $data = $this->request->getJSON();

            if (! $data || isset($data->current_host) === false || ! $data->current_host) {
                return jsonEncode($response, false);
            }
                
            $host       = trim(strtolower(strip_tags($data->current_host)));
            $attempts   = (int) trim(strip_tags($data->attempts));
            $today      = meDate(trim(strip_tags($data->date)));
        
            if ($attempts < self::MAX_ATTEMPTS) {
                $config = new App();
                $meHost = strtolower(trim(parse_url($config->baseURL, PHP_URL_HOST), ' /'));
                if ($meHost === $host && $today === meDate()) $response['status'] = 200;
            }
            return jsonEncode($response, false);
        } catch (\Throwable $th) {
            return jsonEncode($response, false);
        }
    }
}