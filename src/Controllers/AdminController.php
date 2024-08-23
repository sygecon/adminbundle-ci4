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
	protected const VALID_HASH = '19ceb94e';

	protected $locale = APP_DEFAULT_LOCALE;

	protected $session;

	public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger) 
	{
		parent::initController($request, $response, $logger);

		$this->session = Services::session();
		if (! $user = auth()->user()) { throw PageNotFoundException::forPageNotFound(); }

		$this->setLocale($user); 
		$this->genHash();
		Locale::setDefault($this->locale);
	}

	/** * Destructor */
    public function __destruct() 
    {
        $this->session->close();
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
		return '<a href="' . $this->request->getServer('HTTP_REFERER') . 
			'" class="btn btn-outline-secondary" asp-lazy="chevron-double-left" title="' . 
			lang('Admin.goBack') . '"></a><span class="h5">' . $text . '</span>';
	}
}
