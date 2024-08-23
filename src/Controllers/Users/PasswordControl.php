<?php
declare(strict_types=1);  
namespace Sygecon\AdminBundle\Controllers\Users;

use Sygecon\AdminBundle\Controllers\AdminController;
use Throwable;

final class PasswordControl extends AdminController
{
    private const ERROR_REDIRECT = 'error/show404';

    protected $helpers = ['setting', 'url', 'form'];

    public function index(string $isChange = 'off') 
    {
        if (! auth()->loggedIn()) {
            return redirect()->to(self::ERROR_REDIRECT);
        }
        if ($isChange === 'off' && ! auth()->user()->requiresPasswordReset()) {
            return redirect()->to(self::ERROR_REDIRECT);
        }

        if (! session('previous_url')) {
            $_SESSION['previous_url'] = previous_url();
        }

        return $this->build('set_password', [
            'head' => [
                'title' => lang('Admin.changePasswordTitle'),
                'icon'  => 'person-lock'
            ],
            'old_input' => ($isChange === 'off' ? false : true)
        ], 'User');
    }

    public function update() 
    {
        
        if (! auth()->loggedIn()) {
            if (session('previous_url')) { unset($_SESSION['previous_url']); }
            return redirect()->to(self::ERROR_REDIRECT);
        }
        $data = $this->request->getPost();
        //$data = ($this->request->is('json') ? $this->request->getJSON(true) : $this->request->getRawInput());
        if (! $data = $this->postTokenValid($data)) {
            if (session('previous_url')) { unset($_SESSION['previous_url']); }
            return redirect()->to(self::ERROR_REDIRECT);
        }
        if (! $data = $this->postTokenValid($data)) {
            if (session('previous_url')) { unset($_SESSION['previous_url']); }
            return redirect()->to(self::ERROR_REDIRECT);
        }
        $isChange = isset($data['old_password']);
        $url = ($isChange ? 'user/change-password' : 'user/set-password');

        if (! $data['password']) {
            return redirect()->to($url)->withInput()->with('error', 'Error! Password cannot be empty.');
        }
        try {
            if (! $this->validateData($data, $this->getValidationRules())) {
                return redirect()->to($url)->withInput()->with('errors', $this->validator->getErrors());
            }
        } catch (Throwable $th) {
            return redirect()->to($url)->withInput()->with('error', 'Error! Passphrase is not valid.');
        }

        if ($isChange) {
            if (! $this->oldPassword($data['old_password'])) {
                return redirect()->to($url)->withInput()->with('error', lang('Auth.invalidPassword'));
            }  
            if ($data['password'] == $data['old_password']) { 
                return redirect()->to($url)->withInput()->with('error', 'Error! The password is identical to the existing one.'); 
            }
        } else {
            if (! auth()->user()->requiresPasswordReset()) {
                if (session('previous_url')) { unset($_SESSION['previous_url']); }
                return redirect()->to(self::ERROR_REDIRECT);
            }
            if ($this->oldPassword($data['password'])) {
                return redirect()->to($url)->withInput()->with('error', 'Error! The password is identical to the existing one.');
            }
        }
        if (session('previous_url')) { unset($_SESSION['previous_url']); }
        // Success!
        try {
            $users = model('UserModel');
            $user = auth()->user()->fill(['password' => $data['password']]);
            $users->save($user);
            // Remove force password reset flag
            auth()->user()->undoForcePasswordReset();
            // logout user and print login via new password
            auth()->logout();
            
            return redirect()->to(config('Auth')->logoutRedirect())->with('message', lang('Auth.passwordChangeSuccess'));
        } catch (Throwable $th) {
            $error = $th->getMessage();
            helper('path');
            baseWriteFile('logs/Errors/User-Set-Password.log', date('H:i:s d.m.Y') . ' => ' . auth()->user()->username . ' - ' . $error); 
            return redirect()->to($url)->withInput()->with('error', $error);
        }
        //return jsonEncode($result, false);
    }

    public function reset()
    {
        if (! auth()->loggedIn()) return redirect()->to(self::ERROR_REDIRECT);

        $user = auth()->user();
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
     * Returns the rules that should be used for validation.
     *
     * @return array<string, array<string, array<string>|string>>
     * @phpstan-return array<string, array<string, string|list<string>>>
     */
    protected function getValidationRules(): array
    {
        return setting('Validation.setPassword') ?? [
            'password' => [
                'label' => 'Auth.password',
                'rules' => 'required|strong_password',
            ],
            'password_confirm' => [
                'label' => 'Auth.passwordConfirm',
                'rules' => 'required|matches[password]',
            ]
        ];
    }

    protected function oldPassword(string $password = ''): bool
	{
		if (!$password) { return false; }
        $result = auth()->check([
			'email'    => auth()->user()->email,
			'password' => $password,
		]);
		if(!$result->isOK()) { return false; }
		return true;
	} 
}