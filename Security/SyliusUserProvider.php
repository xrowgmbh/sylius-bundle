<?php

namespace xrow\syliusBundle\Security;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\NonceExpiredException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class SyliusUserProvider implements AuthenticationProviderInterface
{
    private $userProvider;

    public function __construct(UserProviderInterface $userProvider)
    {
        $this->userProvider = $userProvider;
    }

    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            return;
        }

        $username = $token->getUsername();
        if ('' === $username || null === $username) {
            $username = 'NONE_PROVIDED';
        }

        try {
            $user = $this->userProvider->loadUserByUsername($username);
        } catch (UsernameNotFoundException $e) {
            throw new AuthenticationException('The SyluisUser authentication failed.');
        }

        $authenticatedToken = new UsernamePasswordToken($user, $token->getCredentials(), $this->providerKey, $user->getRoles());
        $authenticatedToken->setAttributes($token->getAttributes());

        return $authenticatedToken;
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof UsernamePasswordToken && $this->providerKey === $token->getProviderKey();
    }
}