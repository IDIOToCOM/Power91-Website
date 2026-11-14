<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

final class AdminFormAuthenticator extends AbstractLoginFormAuthenticator
{
    public function __construct(
        private UserRepository $users,
        private UrlGeneratorInterface $urls,
        private HideAdminPanel $hideAdminPanel,
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $username = trim((string) $request->request->get('username', ''));
        $password = (string) $request->request->get('password', '');
        $csrfToken = (string) $request->request->get('_csrf_token', '');

        return new Passport(
            new UserBadge($username, function (string $identifier): User {
                if ('' === $identifier) {
                    throw new UserNotFoundException();
                }

                $user = $this->users->findOneByUsername($identifier);
                if (!$user instanceof User || !$user->isAdmin()) {
                    throw new UserNotFoundException();
                }

                return $user;
            }),
            new PasswordCredentials($password),
            [new CsrfTokenBadge('authenticate', $csrfToken)],
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        return new RedirectResponse($this->urls->generate('admin_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($request->hasSession()) {
            $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
            $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, trim((string) $request->request->get('username', '')));
        }

        return new RedirectResponse($this->urls->generate('admin_login'));
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return $this->hideAdminPanel->notFound();
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urls->generate('admin_login');
    }
}
