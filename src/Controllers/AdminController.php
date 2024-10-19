<?php

namespace Sygecon\AdminBundle\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\Config\Services;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Shield\Entities\User;
use Locale;

abstract class AdminController extends Controller 
{
	protected const VALID_HASH 		= '25011f03';

	protected const PREVIOUS_URI_ID = '_ci_asp_direct_previous_uri';
	protected const CURRENT_URI_ID  = '_ci_asp_direct_current_uri';
	protected const NOT_KEEP_URI  	= [
		'register'          => 1, 'login'             => 1,
        'logout'            => 1, 'force_reset'       => 1,
        'permission_denied' => 1, 'group_denied'      => 1,
		'user/change-password' => 1, 'user/set-password' => 1, 'user/password-reset' => 1
	];

	protected $locale = APP_DEFAULT_LOCALE;

	protected $session;

	public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger) 
	{
		parent::initController($request, $response, $logger);

		$this->session = Services::session();
		if (! $user = auth()->user()) { throw PageNotFoundException::forPageNotFound(); }
		
		$this->setLocale($user); 
		$this->genHash();
		$this->keeperLinks();
		Locale::setDefault($this->locale);
	}

	/** * Destructor */
    public function __destruct() 
    {
        if (isset($this->session)) $this->session->close();
    }

	protected function setLocale(User $user): void
	{
		if ($langId = $user->lang_id) {
			$this->locale = langNameFromId($langId);
			return;
		}
		$config = new \Config\App();
		$this->locale = $config->defaultLocale;
	}

	protected function genHash(): void
	{
		if ($this->request->isAJAX()) { return; }
		$configSecurity = new \Config\Security();
		if ($configSecurity->regenerate === true) { 
			$security = Services::security();
			$security->generateHash(); 
		}
	}

	protected function build(string $page = 'index', array $data = [], string $path = ''): string 
	{
		$data['valid_hash'] = self::VALID_HASH;
		$data['locale'] = $this->locale;
		if (! isset($data['head'])) { $data['head'] = []; }
		if (! isset($data['head']['h1'])) { $data['head']['h1'] = '&nbsp;'; }

		$path = str_replace('/', '\\', trim($path, ' /\\'));
		if ($path) { $path .= '\\'; }

		return view('Sygecon\AdminBundle\Views\Pages\\' . $path . 'asp_' . $page, $data);
	}

	protected function successfulResponse(mixed $response = null, bool $IsEncode = false): string
	{
        if ($this->request->isAJAX()) {
			return (string) jsonEncode(['status' => 200, 'message' => $response], false);
		}
		if ($IsEncode === true) return (string) jsonEncode($response, false);
		if (is_array($response) === true) return (string) jsonEncode($response, false);
		return (string) $response;
	}

	protected function pageNotFound(): string 
    {
        return (string) jsonEncode(['status'  => 404, 'message' => lang('Admin.error.notFindPage')], false);
    }

	protected function postTokenValid(array $data = []): array 
	{
		if ($data) { 
			$security = Services::security();
			$tokenName = $security->getTokenName();
			if (isset($data[$tokenName]) && $token = $data[$tokenName]) {
				unset($data[$tokenName]);
				$hash = $security->getHash();
				if (isset($token, $hash) && hash_equals($hash, $token)) { return $data; }
				session()->setFlashdata('error', 'Past Data not valid!');
				return [];
			}
		}
		return $data;
	}

	protected function postDataValid(array $postData = [], int $maxName = 32, int $maxTitle = 255, int $maxDesc = 255): array 
	{
		$data = $this->postTokenValid($postData);
		if ($data) { 
			if (isset($data['name'])) {
				$str = checkUrl($data['name']);
				if (!isset($data['class'])) { $str = strtolower($str); }
				if (mb_strlen($str) < 2) {
					unset($data['name']);
				} else {
					$data['name'] = (!$maxName ? $str : mb_strimwidth($str, 0, $maxName, ''));
				}
			}
			if (isset($data['title'])) {
				$str = mb_ucfirst(cleaningText($data['title']));
				if (!$str && isset($data['name'])) { $str = $data['name']; }
				$data['title'] = (!$maxTitle ? $str : mb_strimwidth($str, 0, $maxTitle, ''));
			}
			if (isset($data['description'])) {
				$str = mb_ucfirst(cleaningText($data['description']));
				if (!$str && isset($data['title'])) { $str = $data['title']; }
				$data['description'] = (!$maxDesc ? $str : mb_strimwidth($str, 0, $maxDesc, ''));
			}
		}
		return $data;
	}

	protected function genLinkHome(string $text): string
	{
		if (isset($_SESSION[self::PREVIOUS_URI_ID])) {
			$previusUri = '/' . $_SESSION[self::PREVIOUS_URI_ID];
		} else {
			$previusUri = $this->request->getServer('HTTP_REFERER', FILTER_SANITIZE_URL);
		}
		
		return '<a href="' . $previusUri . 
			'" class="btn btn-outline-secondary" asp-lazy="chevron-double-left" title="' . 
			lang('Admin.goBack') . '"></a><span class="h5">' . $text . '</span>';
	}

	protected function keeperLinks(): void
	{
		$currentUri = strtolower(ltrim($this->request->getServer('REQUEST_URI', FILTER_SANITIZE_URL), '/'));
		if (true === isset(self::NOT_KEEP_URI[$currentUri])) return;
		if ($pos = strpos($currentUri, '/')) {
			$path = substr($currentUri, 0, $pos);
			if ($path === 'api' || $path === 'error' || $path === 'http:' || $path === 'https:' || $path === 'file:' || $path === 'ftp:') return;
			if ($path === 'index' || $path === 'php' || $path === 'index.php' || $path === 'index.html') {
				$currentUri = substr($path, ++$pos);
			}
		}
		if (true === isset($_SESSION[self::CURRENT_URI_ID])) {
			$previusUri = $_SESSION[self::CURRENT_URI_ID];
			if ($previusUri === $currentUri) return;

			$_SESSION[self::PREVIOUS_URI_ID] = $previusUri;
		} 
		$_SESSION[self::CURRENT_URI_ID] = $currentUri;
	}
}
