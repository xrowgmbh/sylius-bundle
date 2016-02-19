<?php

namespace xrow\syliusBundle\Security;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class SyliusUserFirewallListener implements ListenerInterface
{
    /**
     * @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var \Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface
     */
    protected $authenticationManager;

    /**
     * @param \Symfony\Component\Security\Core\SecurityContextInterface                      $securityContext       The security context.
     * @param \Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface $authenticationManager The authentication manager.
     */
    public function __construct(TokenStorageInterface $tokenStorage, AuthenticationManagerInterface $authenticationManager)
    {
        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event The event.
     */
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $username = $request->get('username');
        $password = $request->get('password');
        die($password);
        // Maybe load an user
        $token = new UsernamePasswordToken($user,
                                           $user->getPassword(),
                                           $this->providerKey,
                                           $user->getRoles());

        try {
            $authToken = $this->authenticationManager->authenticate($token);
            $this->tokenStorage->setToken($authToken);
            return;
        } catch (AuthenticationException $failed) {
            $token = $this->tokenStorage->getToken();
            if ($token instanceof UsernamePasswordToken && $this->providerKey === $token->getProviderKey()) {
                $this->tokenStorage->setToken(null);
            }
        }
        // By default deny authorization
        $response = new Response();
        $response->setStatusCode(Response::HTTP_FORBIDDEN);
        $event->setResponse($response);
    }
}