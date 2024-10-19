<?php
declare(strict_types=1);  
namespace Sygecon\AdminBundle\Controllers\Users;

use Sygecon\AdminBundle\Controllers\AdminController;
use Sygecon\AdminBundle\Models\UserModel;
use Throwable;

final class PasswordControl extends AdminController
{
    private const ERROR_REDIRECT    = 'error/show404';

    protected $helpers = ['form'];

    public function index(string $isChange = 'off') 
    {
        if (! auth()->loggedIn()) {
            return redirect()->to(self::ERROR_REDIRECT);
        }
        if ($isChange === 'off' && ! auth()->user()->requiresPasswordReset()) {
            return redirect()->to(self::ERROR_REDIRECT);
        }
        if (! isset($_SESSION[self::CURRENT_URI_ID])) return redirect()->to(self::ERROR_REDIRECT);

        return $this->build('set_password', [
            'head' => [
                'title' => lang('Admin.changePasswordTitle'),
                'icon'  => 'person-lock'
            ],
            'previous_url' => '/' . $_SESSION[self::CURRENT_URI_ID],
            'old_input' => ($isChange === 'off' ? false : true)
        ], 'User');
    }

    public function update(): string
    {
        if (! isset($_SESSION[self::CURRENT_URI_ID])) {
            return $this->formingResponse('', true, self::ERROR_REDIRECT);
        }
        if (! $user = auth()->user()) {
            return $this->formingResponse('', true, self::ERROR_REDIRECT);
        }
        if (! $data = $this->postTokenValid($this->request->getJSON(true))) {
            return $this->formingResponse('', true, self::ERROR_REDIRECT);
        }
        
        $isChange       = (bool) isset($data['old_password']);
        $previousUrl    = $_SESSION[self::CURRENT_URI_ID];

        if (! isset($data['password']) || ! $data['password']) {
            return $this->formingResponse('Error! Password cannot be empty.');
        }
        if (! isset($data['password_confirm']) || ! $data['password_confirm']) {
            return $this->formingResponse('Error! Password cannot be empty.');
        }
        
        if ($isChange) {
            if (! isset($data['old_password']) || ! $data['old_password']) {
                return $this->formingResponse('Error! ' . lang('Auth.invalidPassword'), true, $previousUrl);
            }
            if (! $this->checkPassword($data['old_password'])) {
                return $this->formingResponse('Error! ' . lang('Auth.invalidPassword'), true, $previousUrl);
            }
            if ($data['password'] === $data['old_password']) { 
                return $this->formingResponse('Error! The password is identical to the existing one.');
            }
            unset($data['old_password']);
        } else {
            if ($this->checkPassword($data['password'])) {
                return $this->formingResponse('Error! The password is identical to the existing one.');
            }
            $user->requiresPasswordReset();
        }
        
        // Success!
        $model = new UserModel();
        if (false === $model->update((int) $user->id, $data)) {
            return $this->formingResponse('Error! Passphrase is not valid.');
        }
        // Remove force password reset flag
        $user->undoForcePasswordReset();
        // logout user and print login via new password 
        if (false === $isChange) {
            $previousUrl = 'login';
            auth()->logout();
        }
        return $this->formingResponse(lang('Auth.passwordChangeSuccess'), false, $previousUrl);
    }

    public function reset()
    {
        if (! $user = auth()->user()) return redirect()->to(self::ERROR_REDIRECT);

		if ($this->request->isAJAX()) {
			try {
				$user->forcePasswordReset();
                // logout user and print login via new password
				return $this->successfulResponse('ok');
			} catch (Throwable $th) { 
				return $this->pageNotFound();
			}
		}
		return $this->pageNotFound();
	}

    /**
     * Private functions
     */

    private function checkPassword(string $password = ''): bool
	{
		if (! $password) return false;
        $result = auth()->check([
			'email'    => auth()->user()->email,
			'password' => $password,
		]);
		return $result->isOK();
	} 

    private function formingResponse(string $message = '', bool $isError = true, string $slug = ''): string
	{
		$response = [
            'status' => $isError ? '404' : '200',
            'message' => $message
        ];
        if ($slug) $response['slug'] = $slug;
		return jsonEncode($response, false);
	}

    // private function currentUrl(): string
    // {
    //     $uri    = $this->request->getUri();
    //     $path   = trim($uri->getPath(), '/');
    //     if (! $pos = strpos($path, '/')) return $path;
    //     $str = substr($path, 0, $pos);
    //     if ($str === 'index' || $str === 'index.php' || $str === 'index.html') {
    //         $path = substr($path, ++$pos);
    //     }
    //     return $path;
    // }
}