<?php 
/**
 * @author  Aspada.ru
 * @license http://www.opensource.org/licenses/bsd-license.php BSD 3-Clause License
 */
namespace Sygecon\AdminBundle\Libraries\HTML; 

final class WebDoc 
{
    private const HEADER = [
        'Accept: application/json,text/html;q=0.9,text/plain;q=0.8,text/xml,application/xml,application/xhtml+xml', // Prefer HTML format
		'Accept-Charset: utf-8', // Prefer UTF-8 encoding
    ];

    public static function httpCode(string $url = ''): int
    {
		if(! self::getScheme($url)) { return 0; }
        return self::getCode(get_headers($url));
    }

	/**
	 * @return @string
	 */
	public static function load(string $url = ''): string
	{
		if(! $scheme = self::getScheme($url)) { return ''; }
		if ($scheme !== 'http' && $scheme !== 'https') { return ''; }
		if(extension_loaded('curl')) { return self::loadCurl($url); }
		if(ini_get('allow_url_fopen')) { return self::loadFile($url); }
		return '';
	}

	/**
	 * @return @bool
	 */
	public static function downloadRemoteFile(string $remouteUrl = '', string $rootPublicPath = ''): bool
	{
		if (! $remouteUrl) {return false; }
		if (! $rootPublicPath) {return false; }
		if (! self::getScheme($remouteUrl)) { return false; }

		$fileName = FCPATH . castingPath($rootPublicPath, true) . DIRECTORY_SEPARATOR .  castingPath(strtolower(parse_url($remouteUrl, PHP_URL_PATH)), true);
		$dir = dirname($fileName);
        if (! is_dir($dir)) { 
			mkdir($dir, 0755, true);
			if (! is_dir($dir)) { return false; }
		}

		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remouteUrl);
        curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        curl_close($ch);
		if (! $data) { return false; }

		if (! file_put_contents($fileName, $data)) { return false; }
		return true;
	}

	/**
	 * cURL implementation of load
	 */
	private static function loadCurl(string $url): string
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, self::HEADER);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.3; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $doc = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

		if ($httpCode != 200) { return ''; }
        if ($error) { return ''; }

		return $doc;
	}

	/**
	 * fopen implementation of load
	 */
	private static function loadFile(string $url): string
	{
		// There is no guarantee this request will be fulfilled
		$context = stream_context_create(['http' => [
			'header' => self::HEADER,
			'ignore_errors' => true // Always fetch content
		]]);

        $doc = file_get_contents($url, false, $context, 0);

        if(self::getCode($http_response_header) != 200) { return ''; }

		return $doc;
	}

    private static function getCode(?array $headers): int
    {
        if(! $headers || ! isset($headers[0])) { return 400; }
        $parts = explode(' ', $headers[0], 3);
        if (! isset($parts[1])) { return 400; }
        return (int) trim($parts[1]);
    }

	private static function getScheme(string $url): string
    {
		if(filter_var($url, FILTER_VALIDATE_URL) === false) { return ''; }
		return strtolower(parse_url($url, PHP_URL_SCHEME));
    }
}
