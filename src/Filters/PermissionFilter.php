<?php

declare(strict_types=1);

namespace Sygecon\AdminBundle\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Auth Rate-Limiting Filter.
 *
 * Provides rated limiting intended for Auth routes.
 */
class PermissionFilter implements FilterInterface
{
    /**
     * Intened for use on auth form pages to restrict the number
     * of attempts that can be generated. Restricts it to 10 attempts
     * per minute, which is what auth0 uses.
     *
     * @see https://auth0.com/docs/troubleshoot/customer-support/operational-policies/rate-limit-policy/database-connections-rate-limits
     *
     * @param array|null $arguments
     *
     * @return RedirectResponse|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! $request instanceof IncomingRequest) { return; }

        helper('setting');

        /** @var Session $authenticator */
        $authenticator = auth('session')->getAuthenticator();

        if ($authenticator->loggedIn()) {
            if (setting('Auth.recordActiveDate')) {
                $authenticator->recordActiveDate();
            }

            // Block inactive users when Email Activation is enabled
            $user = $authenticator->getUser();

            if ($user->isBanned()) {
                $error = $user->getBanMessage() ?? lang('Auth.logOutBannedUser');
                $authenticator->logout();

                return redirect()
                    ->to(config('Auth')->logoutRedirect())
                    ->with('error', $error);
            }

            if ($user !== null && ! $user->isActivated()) {
                $authenticator->logout();

                return redirect()->route('login')
                    ->with('error', lang('Auth.activationBlocked'));
            }

            if (empty($arguments)) { return; }
            foreach ($arguments as $permission) {
                if ($user->can($permission)) { return; }
            }

            //return redirect()->back()->with('error', 'You do not have permissions to access that page.');
            return redirect()->to('error/show403'); 
        }

        return redirect()->route('login');
    }

    /**
     * We don't have anything to do here.
     *
     * @param Response|ResponseInterface $response
     * @param array|null                 $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
        // Nothing required
    }
}
